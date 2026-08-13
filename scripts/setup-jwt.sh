#!/usr/bin/env bash
# Generate Lexik JWT key pair for the API (step 1 of deployment).
#
# Usage:
#   ./scripts/setup-jwt.sh              # prompt for passphrase
#   JWT_PASSPHRASE='secret' ./scripts/setup-jwt.sh
#   ./scripts/setup-jwt.sh --force      # overwrite existing keys
#
# Output:
#   api/config/jwt/private.pem
#   api/config/jwt/public.pem

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
JWT_DIR="${ROOT_DIR}/api/config/jwt"
PRIVATE_KEY="${JWT_DIR}/private.pem"
PUBLIC_KEY="${JWT_DIR}/public.pem"
FORCE=0

for arg in "$@"; do
  case "$arg" in
    --force) FORCE=1 ;;
    -h|--help)
      sed -n '2,12p' "$0"
      exit 0
      ;;
    *)
      echo "Unknown option: $arg" >&2
      echo "Usage: $0 [--force]" >&2
      exit 1
      ;;
  esac
done

if ! command -v openssl >/dev/null 2>&1; then
  echo "Error: openssl is required." >&2
  exit 1
fi

if [[ -f "$PRIVATE_KEY" || -f "$PUBLIC_KEY" ]]; then
  if [[ "$FORCE" -ne 1 ]]; then
    echo "JWT keys already exist in api/config/jwt/." >&2
    echo "Use --force to regenerate (invalidates all existing user sessions)." >&2
    exit 1
  fi
  echo "Backing up existing keys..."
  ts="$(date +%Y%m%d-%H%M%S)"
  [[ -f "$PRIVATE_KEY" ]] && cp "$PRIVATE_KEY" "${PRIVATE_KEY}.bak.${ts}"
  [[ -f "$PUBLIC_KEY" ]] && cp "$PUBLIC_KEY" "${PUBLIC_KEY}.bak.${ts}"
fi

if [[ -z "${JWT_PASSPHRASE:-}" ]]; then
  read -r -s -p "JWT passphrase (min 12 chars): " JWT_PASSPHRASE
  echo
  read -r -s -p "Confirm passphrase: " JWT_PASSPHRASE_CONFIRM
  echo
  if [[ "$JWT_PASSPHRASE" != "$JWT_PASSPHRASE_CONFIRM" ]]; then
    echo "Error: passphrases do not match." >&2
    exit 1
  fi
fi

if [[ ${#JWT_PASSPHRASE} -lt 12 ]]; then
  echo "Error: passphrase must be at least 12 characters." >&2
  exit 1
fi

mkdir -p "$JWT_DIR"
chmod 700 "$JWT_DIR"

echo "Generating private key..."
openssl genrsa -aes256 \
  -out "$PRIVATE_KEY" \
  -passout pass:"$JWT_PASSPHRASE" \
  4096

echo "Generating public key..."
openssl rsa -pubout \
  -in "$PRIVATE_KEY" \
  -out "$PUBLIC_KEY" \
  -passin pass:"$JWT_PASSPHRASE"

chmod 600 "$PRIVATE_KEY"
chmod 644 "$PUBLIC_KEY"

echo ""
echo "JWT keys created:"
echo "  ${PRIVATE_KEY}"
echo "  ${PUBLIC_KEY}"
echo ""
echo "Set this in your root .env (for docker-compose.prod.yml):"
echo "  JWT_PASSPHRASE=<your-passphrase>"
echo ""
echo "Never commit private.pem (already in .gitignore)."
