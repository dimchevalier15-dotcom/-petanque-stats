#!/usr/bin/env bash
# Déploiement production : mise à jour Git (fast-forward) puis docker compose up.
# Ne modifie jamais .env, ne supprime jamais de volumes, n'exécute jamais
# docker compose down. Aucun secret n'est affiché.

set -euo pipefail
# Ne jamais activer set -x : des secrets pourraient passer dans les logs.

readonly COMPOSE_FILE_NAME="docker-compose.prod.yml"
readonly API_SERVICE="api"
readonly API_HEALTHY_TIMEOUT=60
readonly API_HEALTHY_INTERVAL=3

PULL_ONLY=0
NO_BUILD=0

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
  ./scripts/deploy.sh
  ./scripts/deploy.sh --no-build
  ./scripts/deploy.sh --pull-only
  ./scripts/deploy.sh --help

Met à jour le dépôt Git (fetch + pull --ff-only), redéploie la stack
de production (docker-compose.prod.yml), puis exécute les migrations
Doctrine en attente. Ne touche pas à .env ni aux volumes.

Options:
  --pull-only   Mise à jour Git uniquement (pas de docker compose)
  --no-build    Recrée/redémarre la stack sans --build
  -h, --help    Affiche cette aide
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

ensure_tools() {
  command -v git >/dev/null 2>&1 || die "git est introuvable dans PATH."
  if [[ "${PULL_ONLY}" -eq 0 ]]; then
    command -v docker >/dev/null 2>&1 || die "docker est introuvable dans PATH."
    docker compose version >/dev/null 2>&1 || die "docker compose (plugin) est introuvable."
  fi
}

ensure_git_repo() {
  local git_root
  if ! git -C "${PROJECT_ROOT}" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    die "Ce n'est pas un clone Git valide (${PROJECT_ROOT})."
  fi
  git_root="$(git -C "${PROJECT_ROOT}" rev-parse --show-toplevel)"
  if [[ "${git_root}" != "${PROJECT_ROOT}" ]]; then
    die "La racine Git (${git_root}) ne correspond pas à la racine du projet (${PROJECT_ROOT})."
  fi
}

ensure_clean_worktree() {
  local dirty
  dirty="$(git -C "${PROJECT_ROOT}" status --porcelain)"
  if [[ -n "${dirty}" ]]; then
    log_err "Le dépôt contient des modifications locales non committées. Déploiement refusé."
    git -C "${PROJECT_ROOT}" status --short >&2
    die "Commitez ou mettez de côté les changements avant de relancer ./scripts/deploy.sh."
  fi
}

current_commit() {
  git -C "${PROJECT_ROOT}" log -1 --oneline
}

update_git() {
  local before after
  before="$(current_commit)"
  log_info "Commit actuellement déployé : ${before}"

  log_info "git fetch origin"
  git -C "${PROJECT_ROOT}" fetch origin

  log_info "git pull --ff-only"
  git -C "${PROJECT_ROOT}" pull --ff-only

  after="$(current_commit)"
  if [[ "${before}" == "${after}" ]]; then
    log_info "Nouveau commit après pull : ${after} (inchangé)"
  else
    log_ok "Nouveau commit après pull : ${after}"
  fi
}

show_compose_ps() {
  log_info "État des services :"
  compose ps
}

dump_compose_logs() {
  log_err "Derniers logs (100 lignes) :"
  compose logs --tail=100 || true
}

api_health() {
  local health
  health="$(compose ps --format '{{if eq .Service "api"}}{{.Health}}{{end}}' 2>/dev/null | tr -d '[:space:]')"
  printf '%s\n' "${health}"
}

run_pending_migrations() {
  local status

  log_info "Vérification des migrations Doctrine…"
  set +e
  compose exec -T "${API_SERVICE}" php bin/console doctrine:migrations:up-to-date --no-interaction --no-ansi >/dev/null 2>&1
  status=$?
  set -e

  if [[ "${status}" -eq 0 ]]; then
    log_ok "Aucune migration en attente."
    return 0
  fi

  log_info "Migrations en attente, exécution (doctrine:migrations:migrate --no-interaction)…"
  if ! compose exec -T "${API_SERVICE}" php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --no-ansi; then
    log_err "Les migrations Doctrine ont échoué."
    return 1
  fi

  log_ok "Migrations Doctrine appliquées."
  return 0
}

wait_for_api_healthy() {
  local elapsed=0
  local health

  log_info "Attente que le service ${API_SERVICE} soit healthy (timeout ${API_HEALTHY_TIMEOUT}s)…"
  while (( elapsed < API_HEALTHY_TIMEOUT )); do
    health="$(api_health)"
    if [[ "${health}" == "healthy" ]]; then
      log_ok "Service ${API_SERVICE} healthy."
      return 0
    fi
    log_info "${API_SERVICE}: ${health:-unknown} (${elapsed}s/${API_HEALTHY_TIMEOUT}s)"
    sleep "${API_HEALTHY_INTERVAL}"
    elapsed=$((elapsed + API_HEALTHY_INTERVAL))
  done

  health="$(api_health)"
  log_err "Le service ${API_SERVICE} n'est pas healthy après ${API_HEALTHY_TIMEOUT}s (état: ${health:-unknown})."
  return 1
}

deploy_stack() {
  local -a up_args
  up_args=(up -d)
  if [[ "${NO_BUILD}" -eq 0 ]]; then
    up_args+=(--build)
    log_info "Rebuild et redéploiement : docker compose -f ${COMPOSE_FILE_NAME} up -d --build"
  else
    log_info "Redéploiement sans build : docker compose -f ${COMPOSE_FILE_NAME} up -d"
  fi

  if ! compose "${up_args[@]}"; then
    log_err "docker compose up a échoué."
    dump_compose_logs
    die "Déploiement échoué."
  fi

  show_compose_ps

  if ! wait_for_api_healthy; then
    show_compose_ps
    dump_compose_logs
    die "Déploiement échoué."
  fi

  if ! run_pending_migrations; then
    show_compose_ps
    dump_compose_logs
    die "Déploiement échoué."
  fi

  show_compose_ps
  log_ok "Deployment successful"
}

parse_args() {
  while [[ "${#}" -gt 0 ]]; do
    case "${1}" in
      -h|--help)
        usage
        exit 0
        ;;
      --pull-only)
        PULL_ONLY=1
        shift
        ;;
      --no-build)
        NO_BUILD=1
        shift
        ;;
      *)
        die "Option inconnue : ${1} (voir --help)"
        ;;
    esac
  done

  if [[ "${PULL_ONLY}" -eq 1 && "${NO_BUILD}" -eq 1 ]]; then
    die "Options incompatibles : --pull-only et --no-build."
  fi
}

# --- main ---
parse_args "$@"

PROJECT_ROOT="$(resolve_project_root)"
COMPOSE_FILE="${PROJECT_ROOT}/${COMPOSE_FILE_NAME}"
cd "${PROJECT_ROOT}"

export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:${PATH:-}"

ensure_tools
ensure_git_repo
ensure_clean_worktree
update_git

if [[ "${PULL_ONLY}" -eq 1 ]]; then
  log_ok "Mise à jour Git terminée (--pull-only, stack Docker inchangée)."
  exit 0
fi

deploy_stack
