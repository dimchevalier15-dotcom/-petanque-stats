#!/usr/bin/env bash
# Sauvegarde MySQL de production (InnoDB) via docker compose exec.
# Ne touche jamais au volume Docker ni à la base. Aucun mot de passe n'est
# lu ni affiché côté hôte : mysqldump s'exécute dans le conteneur mysql.

set -euo pipefail
# Ne jamais activer set -x : le mot de passe MySQL passerait dans les logs.

umask 077

readonly RETENTION_DAYS=14
readonly COMPOSE_FILE_NAME="docker-compose.prod.yml"
readonly MYSQL_SERVICE="mysql"
readonly BACKUP_PREFIX="petanque"

log_info() { printf '[INFO] %s\n' "$*"; }
log_ok() { printf '[OK] %s\n' "$*"; }
log_err() { printf '[ERROR] %s\n' "$*" >&2; }

die() {
  log_err "$*"
  exit 1
}

usage() {
  cat <<'EOF'
Usage:
  ./scripts/backup-db.sh
  ./scripts/backup-db.sh --verify /chemin/vers/petanque-YYYY-MM-DD_HH-MM-SS.sql.gz
  ./scripts/backup-db.sh --help

Sauvegarde la base MySQL de production (docker-compose.prod.yml) dans
<racine-projet>/backups/ sans exposer les mots de passe et sans modifier
le volume mysql_data.
EOF
}

resolve_project_root() {
  local script_dir project_root
  script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
  project_root="$(cd "${script_dir}/.." && pwd)"
  if [[ ! -f "${project_root}/${COMPOSE_FILE_NAME}" ]]; then
    die "Fichier ${COMPOSE_FILE_NAME} introuvable à la racine du projet (${project_root})."
  fi
  printf '%s\n' "${project_root}"
}

compose() {
  docker compose --project-directory "${PROJECT_ROOT}" -f "${COMPOSE_FILE}" "$@"
}

verify_backup_file() {
  local file="$1"
  local size awk_status

  [[ -n "${file}" ]] || die "Aucun fichier backup fourni."
  [[ -e "${file}" ]] || die "Fichier introuvable : ${file}"
  [[ -f "${file}" ]] || die "Ce n'est pas un fichier régulier : ${file}"
  [[ "${file}" == *.sql.gz ]] || die "Extension attendue .sql.gz : ${file}"

  size="$(wc -c < "${file}" | tr -d ' ')"
  if [[ "${size}" -le 0 ]]; then
    die "Le fichier de backup est vide : ${file}"
  fi

  gzip -t "${file}" 2>/dev/null || die "Archive gzip invalide : ${file}"

  # Un seul parcours : gzip lit jusqu'à EOF (pas de SIGPIPE) ; awk valide le SQL.
  set +e
  gzip -dc "${file}" | awk '
    BEGIN { header = 0; schema = 0; footer = 0 }
    /MySQL dump|MariaDB dump/ { header = 1 }
    /CREATE TABLE|DROP TABLE/ { schema = 1 }
    /Dump completed/ { footer = 1 }
    END {
      if (!header) {
        print "en-tête mysqldump (MySQL dump) manquant" > "/dev/stderr"
        exit 1
      }
      if (!schema) {
        print "aucune instruction CREATE TABLE / DROP TABLE" > "/dev/stderr"
        exit 1
      }
      if (!footer) {
        print "pied de dump (Dump completed) manquant — dump probablement tronqué" > "/dev/stderr"
        exit 1
      }
      exit 0
    }
  '
  awk_status=$?
  set -e
  if [[ "${awk_status}" -ne 0 ]]; then
    die "Le dump SQL n'a pas passé la vérification de contenu : ${file}"
  fi

  log_ok "Backup valide (${size} octets) : ${file}"
}

ensure_tools() {
  command -v docker >/dev/null 2>&1 || die "docker est introuvable dans PATH."
  command -v gzip >/dev/null 2>&1 || die "gzip est introuvable dans PATH."
  command -v awk >/dev/null 2>&1 || die "awk est introuvable dans PATH."
  docker compose version >/dev/null 2>&1 || die "docker compose (plugin) est introuvable."
}

ensure_mysql_ready() {
  local ping_status
  if ! compose exec -T "${MYSQL_SERVICE}" true >/dev/null 2>&1; then
    die "Le service Compose « ${MYSQL_SERVICE} » n'est pas démarré ou n'est pas accessible. Lancez : docker compose -f ${COMPOSE_FILE_NAME} up -d"
  fi

  # Ping depuis le conteneur : fonctionne même si le port n'est pas publié.
  # MYSQL_PWD n'existe que dans le processus du conteneur, pas sur la ligne de commande hôte.
  set +e
  compose exec -T "${MYSQL_SERVICE}" \
    sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqladmin ping -h 127.0.0.1 -uroot --silent' >/dev/null 2>&1
  ping_status=$?
  set -e
  if [[ "${ping_status}" -ne 0 ]]; then
    die "MySQL ne répond pas dans le conteneur « ${MYSQL_SERVICE} » (mysqladmin ping a échoué)."
  fi
  log_ok "Service MySQL « ${MYSQL_SERVICE} » démarré et accessible."
}

read_mysql_database() {
  local name
  name="$(compose exec -T "${MYSQL_SERVICE}" sh -c 'printf %s "$MYSQL_DATABASE"' | tr -d '\r')"
  if [[ -z "${name}" ]]; then
    name="petanque"
    log_info "MYSQL_DATABASE absent du conteneur, utilisation de la valeur par défaut : ${name}"
  fi
  if [[ ! "${name}" =~ ^[A-Za-z0-9_]+$ ]]; then
    die "Nom de base invalide (caractères non autorisés). Abandon sans exécuter de dump."
  fi
  printf '%s\n' "${name}"
}

cleanup_old_backups() {
  local backup_dir="$1"
  local current_file="$2"
  local old base deleted

  deleted=0
  while IFS= read -r -d '' old; do
    [[ -n "${old}" ]] || continue
    [[ -f "${old}" ]] || continue
    [[ "${old}" == "${backup_dir}"/* ]] || {
      log_err "Refus de supprimer un chemin hors du dossier de backup : ${old}"
      continue
    }
    if [[ "${old}" == "${current_file}" ]]; then
      continue
    fi

    base="$(basename "${old}")"
    if [[ ! "${base}" =~ ^petanque-[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{2}-[0-9]{2}-[0-9]{2}\.sql\.gz$ ]]; then
      log_info "Fichier ignoré (motif non reconnu) : ${base}"
      continue
    fi

    rm -f -- "${old}"
    log_info "Supprimé (rétention ${RETENTION_DAYS} jours) : ${base}"
    deleted=$((deleted + 1))
  done < <(find "${backup_dir}" -maxdepth 1 -type f -name 'petanque-*.sql.gz' -mtime "+${RETENTION_DAYS}" -print0)

  if [[ "${deleted}" -eq 0 ]]; then
    log_info "Aucun backup de plus de ${RETENTION_DAYS} jours à supprimer."
  fi
}

# Variables globales pour le trap EXIT (les locals d'une fonction ne sont
# pas fiables dans un trap déclenché par `exit` / `die`).
BACKUP_TMPFILE=""
BACKUP_ERRFILE=""
BACKUP_LOCK_DIR=""

cleanup_run() {
  if [[ -n "${BACKUP_TMPFILE:-}" ]]; then
    rm -f -- "${BACKUP_TMPFILE}"
  fi
  if [[ -n "${BACKUP_ERRFILE:-}" ]]; then
    rm -f -- "${BACKUP_ERRFILE}"
  fi
  if [[ -n "${BACKUP_LOCK_DIR:-}" && -d "${BACKUP_LOCK_DIR}" && "${BACKUP_LOCK_DIR}" == */.backup.lock.dir ]]; then
    rmdir "${BACKUP_LOCK_DIR}" 2>/dev/null || true
  fi
}

run_backup() {
  local timestamp dest mysql_database backup_dir dump_status gzip_status
  local -a pipe_status

  export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:${PATH:-}"

  ensure_tools
  backup_dir="${PROJECT_ROOT}/backups"
  mkdir -p "${backup_dir}"
  [[ -d "${backup_dir}" ]] || die "Impossible de créer le dossier de backup : ${backup_dir}"

  # Verrou portable (mkdir atomique) — jamais de rm -rf.
  BACKUP_LOCK_DIR="${backup_dir}/.backup.lock.dir"
  if ! mkdir "${BACKUP_LOCK_DIR}" 2>/dev/null; then
    BACKUP_LOCK_DIR=""
    die "Un autre backup est déjà en cours (verrou ${backup_dir}/.backup.lock.dir)."
  fi

  timestamp="$(date +%Y-%m-%d_%H-%M-%S)"
  dest="${backup_dir}/${BACKUP_PREFIX}-${timestamp}.sql.gz"
  # Le fichier temporaire doit finir par .sql.gz (contrôle verify) et rester
  # hors du motif de rétention petanque-YYYY-MM-DD_HH-MM-SS.sql.gz.
  BACKUP_TMPFILE="${backup_dir}/.${BACKUP_PREFIX}-${timestamp}.$$.sql.gz"
  BACKUP_ERRFILE="${backup_dir}/.mysqldump.err.$$"
  trap cleanup_run EXIT

  ensure_mysql_ready
  mysql_database="$(read_mysql_database)"
  log_info "Base cible : ${mysql_database}"
  log_info "Dump en cours vers fichier temporaire…"

  # stdout = dump SQL. MYSQL_PWD évite -p sur la ligne de commande et l'avertissement mysqldump.
  # Les erreurs réelles de mysqldump arrivent dans errfile (sans mot de passe).
  set +e
  compose exec -T "${MYSQL_SERVICE}" sh -c '
    MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysqldump \
      -uroot \
      --single-transaction \
      --quick \
      --lock-tables=false \
      --routines \
      --triggers \
      --events \
      --hex-blob \
      --default-character-set=utf8mb4 \
      --set-gtid-purged=OFF \
      --no-tablespaces \
      "$MYSQL_DATABASE"
  ' 2>"${BACKUP_ERRFILE}" | gzip -c > "${BACKUP_TMPFILE}"
  pipe_status=("${PIPESTATUS[@]}")
  set -e
  dump_status="${pipe_status[0]:-1}"
  gzip_status="${pipe_status[1]:-1}"

  if [[ "${dump_status}" -ne 0 ]]; then
    if [[ -s "${BACKUP_ERRFILE}" ]]; then
      log_err "Sortie mysqldump :"
      cat "${BACKUP_ERRFILE}" >&2
    fi
    die "mysqldump a échoué (code ${dump_status}). Aucun backup n'a été publié, aucune rotation n'a été faite."
  fi
  if [[ "${gzip_status}" -ne 0 ]]; then
    die "gzip a échoué (code ${gzip_status}). Aucun backup n'a été publié, aucune rotation n'a été faite."
  fi

  verify_backup_file "${BACKUP_TMPFILE}"
  mv -f -- "${BACKUP_TMPFILE}" "${dest}"
  BACKUP_TMPFILE=""
  chmod 600 "${dest}" 2>/dev/null || true

  log_ok "Backup créé : ${dest}"

  cleanup_old_backups "${backup_dir}" "${dest}"
  cleanup_run
  trap - EXIT
  log_ok "Sauvegarde terminée avec succès."
}

# --- main ---
PROJECT_ROOT="$(resolve_project_root)"
COMPOSE_FILE="${PROJECT_ROOT}/${COMPOSE_FILE_NAME}"

case "${1:-}" in
  -h|--help)
    usage
    exit 0
    ;;
  --verify)
    [[ "${#}" -eq 2 ]] || die "Usage : $0 --verify /chemin/vers/fichier.sql.gz"
    ensure_tools
    verify_backup_file "$2"
    exit 0
    ;;
  "")
    run_backup
    ;;
  *)
    die "Option inconnue : $1 (voir --help)"
    ;;
esac
