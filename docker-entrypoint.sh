#!/bin/sh
set -eu

err() { printf '%s\n' "$*" >&2; }

if ! command -v composer >/dev/null 2>&1; then
  err "composer not found in PATH. Make sure composer is copied into the final image."
  exit 1
fi

cd /var/www/html || exit 1

# Running as root to fix permissions before switching to appuser
# This allows us to handle permission issues from host mounts

# Ensure writable directories exist
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Ensure vendor directory exists - critical for composer autoload
# This handles cases where the named volume is empty on first run
mkdir -p vendor

# Fix ownership - change all files to appuser:appuser
# This ensures appuser can read/write all application files
# This is necessary because host mounts may have different ownership
chown -R appuser:appuser /var/www/html 2>/dev/null || {
  err "Warning: Could not set ownership. Continuing anyway..."
}

# Set permissions for writable directories
chmod -R 775 storage bootstrap/cache vendor 2>/dev/null || {
  err "Warning: Could not set all permissions. Some features may not work correctly."
}

# Check if vendor/autoload.php exists - this should be present from the Docker image
if [ ! -f ./vendor/autoload.php ]; then
  err "ERROR: vendor/autoload.php not found!"
  err ""
  err "This usually means the Docker image was not built correctly or needs to be rebuilt."
  err ""
  err "To fix this issue:"
  err "  1. Stop all containers: docker compose down -v"
  err "  2. Rebuild the image: docker compose build --no-cache"
  err "  3. Start the services: docker compose up -d"
  err ""
  err "The vendor directory should be populated during the Docker build process,"
  err "not at runtime. If you continue to see this error after rebuilding,"
  err "there may be an issue with the Docker build itself."
  err ""
  err "Diagnostic information:"
  err "  Current user: $(whoami) ($(id))"
  err "  Working directory: $(pwd)"
  ls -la /var/www/html/ | head -20 >&2
  err ""
  err "Attempting composer install as a fallback..."
  
  # Attempt composer install as fallback
  # Since we're running as root and have fixed permissions, this should work
  if composer install --no-dev --optimize-autoloader --no-interaction --no-scripts; then
    err "Composer install succeeded as fallback."
    # Fix ownership of newly installed vendor files
    chown -R appuser:appuser ./vendor 2>/dev/null || true
  else
    err ""
    err "Composer install failed. This confirms the image needs to be rebuilt."
    err "Follow the steps above to rebuild the image properly."
    exit 1
  fi
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
  # Run migrations as appuser
  gosu appuser php artisan migrate --force || {
    err "php artisan migrate failed"
    exit 1
  }
fi

# Execute the main command as appuser for security
# gosu is like sudo but better for Docker - it replaces the current process
exec gosu appuser "$@"