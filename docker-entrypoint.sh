#!/bin/sh
set -eu

err() { printf '%s\n' "$*" >&2; }

if ! command -v composer >/dev/null 2>&1; then
  err "composer not found in PATH. Make sure composer is copied into the final image."
  exit 1
fi

cd /var/www/html || exit 1

# Ensure writable directories exist with correct permissions
# Note: vendor directory is managed by Docker named volume and created in Dockerfile
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
# Set permissions to ensure directories are writable
# Using 777 for maximum compatibility in dev/CI environments where user IDs may vary
chmod -R 777 storage bootstrap/cache 2>/dev/null || {
  err "Warning: Could not set permissions on storage/bootstrap/cache. This may cause write errors."
  err "If running in CI, ensure proper permissions are set before running artisan commands."
}

if [ ! -f ./vendor/autoload.php ]; then
  err "vendor/autoload.php not found — running composer install..."
  
  # Ensure vendor directory exists and is writable
  if [ ! -d ./vendor ]; then
    err "Creating vendor directory..."
    mkdir -p ./vendor || {
      err "Failed to create vendor directory. Checking permissions..."
      ls -la /var/www/html/ | head -20
      err "Current user: $(whoami) ($(id))"
      exit 1
    }
  fi
  
  composer install --no-dev --optimize-autoloader --no-interaction --no-scripts || {
    err "composer install failed"
    err "Checking vendor directory permissions..."
    ls -la ./vendor 2>/dev/null || err "vendor directory does not exist or is not accessible"
    err "Checking /var/www/html ownership..."
    ls -la /var/www/ | grep html
    exit 1
  }
fi

if [ -n "${POSTGRES_HOST:-}" ] && [ -n "${POSTGRES_USER:-}" ] && [ -n "${POSTGRES_DB:-}" ]; then
  PGHOST="${POSTGRES_HOST}"
  PGPORT="${POSTGRES_PORT:-5432}"
  err "Waiting for Postgres at ${PGHOST}:${PGPORT}..."
  if command -v pg_isready >/dev/null 2>&1; then
    until pg_isready -h "$PGHOST" -p "$PGPORT" -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" >/dev/null 2>&1; do
      err "Postgres not ready yet; sleeping 1s..."
      sleep 1
    done
  else
    until (exec 3<>"/dev/tcp/${PGHOST}/${PGPORT}") >/dev/null 2>&1; do
      err "Postgres TCP not ready; sleeping 1s..."
      sleep 1
    done
  fi
  err "Postgres is available."
fi

if [ "${MIGRATE_ON_START:-false}" = "true" ]; then
  err "Running migrations (MIGRATE_ON_START=true)..."
  php artisan migrate --force || {
    err "php artisan migrate failed"
    exit 1
  }
fi

exec "$@"