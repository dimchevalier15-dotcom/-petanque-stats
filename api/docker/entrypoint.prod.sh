#!/bin/sh
set -eu

if [ ! -f /app/vendor/autoload_runtime.php ]; then
  echo "vendor/autoload_runtime.php is missing. Rebuild the API image." >&2
  exit 1
fi

if [ ! -f /app/config/jwt/private.pem ] || [ ! -f /app/config/jwt/public.pem ]; then
  echo "JWT keys are missing in /app/config/jwt. Generate them on the host (docs/deployment.md) then restart." >&2
  exit 1
fi

exec "$@"
