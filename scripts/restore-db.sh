#!/usr/bin/env bash
# Restauration MySQL de production — NE S'EXÉCUTE JAMAIS sans confirmation.
# N'est pas prévue pour cron. N'est pas appelée par backup-db.sh.
# N'efface pas le volume Docker mysql_data et ne recrée pas la base :
# le dump est importé dans la base existante (tables écrasées si le dump
# contient DROP TABLE / CREATE TABLE).

set -euo pipefail
# Ne jamais activer set -x : le mot de passe MySQL passerait dans les logs.

umask 077

readonly COMPOSE_FILE_NAME="docker-compose.prod.yml"
readonly MYSQL_SERVICE="mysql"
readonly CONFIRM_PHRASE="RESTORE"

log_info() { printf '[INFO] %s\n' "$*"; }
log_ok() { printf '[OK] %s\n' "$*"; }
log_err() { printf '[ERROR] %s\n' "$*" >&2; }
log_warn() { printf '[WARN] %s\n' "$*" >&2; }

die() {
  log_err "$*"
  exit 1
}

usage() {
  cat <<'EOF'
Usage:
  ./scripts/restore-db.sh /chemin/vers/petanque-YYYY-MM-DD_HH-MM-SS.sql.gz

Restaure un dump gzip dans la base MySQL de production EXISTANTE.

Cette commande :
  - n'est PAS automatique (absente du cron) ;
  - exige un terminal interactif ;
  - exige de taper exactement RESTORE après l'avertissement ;
  - n'accepte aucun flag du type --yes / --force ;
  - n'efface pas le volume Docker mysql_data ;
  - ne supprime ni ne recrée la base (CREATE/DROP DATABASE absents du dump).

ATTENTION : le contenu actuel des tables sera remplacé par celui du dump.
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

  set +e
  gzip -dc "${file}" | awk '
    BEGIN { header = 0; schema = 0; footer = 0 }
    /MySQL dump|MariaDB dump/ { header = 1 }
    /CREATE TABLE|DROP TABLE/ { schema = 1 }
    /Dump completed/ { footer = 1 }
    END {
      if (!header) { print "en-tête mysqldump manquant" > "/dev/stderr"; exit 1 }
      if (!schema) { print "aucune instruction CREATE TABLE / DROP TABLE" > "/dev/stderr"; exit 1 }
      if (!footer) { print "pied de dump (Dump completed) manquant" > "/dev/stderr"; exit 1 }
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

ensure_mysql_ready() {
  local ping_status
  if ! compose exec -T "${MYSQL_SERVICE}" true >/dev/null 2>&1; then
    die "Le service Compose « ${MYSQL_SERVICE} » n'est pas démarré."
  fi
  set +e
  compose exec -T "${MYSQL_SERVICE}" \
    sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqladmin ping -h 127.0.0.1 -uroot --silent' >/dev/null 2>&1
  ping_status=$?
  set -e
  if [[ "${ping_status}" -ne 0 ]]; then
    die "MySQL ne répond pas dans le conteneur « ${MYSQL_SERVICE} »."
  fi
}

# --- main ---
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:${PATH:-}"

case "${1:-}" in
  -h|--help)
    usage
    exit 0
    ;;
  ""|--yes|--force|-y|--i-understand-this-will-overwrite-data)
    usage >&2
    die "Indiquez le chemin du fichier .sql.gz. Aucune option de forçage n'est acceptée."
    ;;
esac

if [[ "${#}" -ne 1 ]]; then
  usage >&2
  die "Indiquez exactement un chemin de fichier .sql.gz (voir --help)."
fi

if [[ ! -t 0 ]] || [[ ! -t 1 ]]; then
  die "La restauration exige un terminal interactif (stdin et stdout). Elle ne peut pas être lancée depuis un cron ou un pipe."
fi

command -v docker >/dev/null 2>&1 || die "docker est introuvable dans PATH."
command -v gzip >/dev/null 2>&1 || die "gzip est introuvable dans PATH."
docker compose version >/dev/null 2>&1 || die "docker compose (plugin) est introuvable."

PROJECT_ROOT="$(resolve_project_root)"
COMPOSE_FILE="${PROJECT_ROOT}/${COMPOSE_FILE_NAME}"

BACKUP_FILE="$1"
if [[ "${BACKUP_FILE}" != /* ]]; then
  BACKUP_FILE="$(pwd)/${BACKUP_FILE}"
fi

verify_backup_file "${BACKUP_FILE}"
ensure_mysql_ready

MYSQL_DATABASE="$(compose exec -T "${MYSQL_SERVICE}" sh -c 'printf %s "$MYSQL_DATABASE"' | tr -d '\r')"
MYSQL_DATABASE="${MYSQL_DATABASE:-petanque}"
if [[ ! "${MYSQL_DATABASE}" =~ ^[A-Za-z0-9_]+$ ]]; then
  die "Nom de base invalide. Restauration annulée."
fi

cat <<EOF

================================================================
  ATTENTION — RESTAURATION DESTRUCTIVE
================================================================

  Fichier :  ${BACKUP_FILE}
  Projet :   ${PROJECT_ROOT}
  Compose :  ${COMPOSE_FILE_NAME}  (projet petanque-stats-prod)
  Service :  ${MYSQL_SERVICE}
  Base :     ${MYSQL_DATABASE}

  Cette opération va ÉCRASER les données actuellement présentes
  dans la base « ${MYSQL_DATABASE} » (DROP/CREATE TABLE du dump).

  Ce qui n'est PAS fait :
  - suppression du volume Docker mysql_data
  - DROP / CREATE DATABASE
  - suppression d'autres backups

  Arrêtez l'API pendant la restauration si des écritures sont
  possibles (recommandé) :
    docker compose -f ${COMPOSE_FILE_NAME} stop api

================================================================

EOF

log_warn "Pour continuer, tapez exactement : ${CONFIRM_PHRASE}"
printf 'Confirmation : '
read -r confirmation

if [[ "${confirmation}" != "${CONFIRM_PHRASE}" ]]; then
  die "Confirmation incorrecte. Restauration annulée, aucune donnée modifiée."
fi

log_info "Import en cours (ne pas interrompre)…"

set +e
gzip -dc "${BACKUP_FILE}" | compose exec -T "${MYSQL_SERVICE}" sh -c '
  MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql \
    -uroot \
    --default-character-set=utf8mb4 \
    "$MYSQL_DATABASE"
'
pipe_status=("${PIPESTATUS[@]}")
set -e
gzip_status="${pipe_status[0]:-1}"
restore_status="${pipe_status[1]:-1}"

if [[ "${gzip_status}" -ne 0 ]]; then
  die "Lecture gzip a échoué (code ${gzip_status}). L'état de la base peut être incomplet — ne pas relancer sans diagnostic."
fi
if [[ "${restore_status}" -ne 0 ]]; then
  die "mysql a échoué pendant l'import (code ${restore_status}). L'état de la base peut être incomplet."
fi

log_ok "Restauration terminée dans la base « ${MYSQL_DATABASE} »."
log_info "Si l'API a été arrêtée : docker compose -f ${COMPOSE_FILE_NAME} start api"
