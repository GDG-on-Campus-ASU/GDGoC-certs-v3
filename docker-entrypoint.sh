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

# Ensure PHP-FPM log file location exists
touch storage/logs/php-fpm.log 2>/dev/null || true

# Ensure vendor directory exists - critical for composer autoload
# This handles cases where the named volume is empty on first run
mkdir -p vendor

# Fix ownership - change all files to appuser:appuser
# This ensures appuser can read/write all application files
# This is necessary because host mounts may have different ownership
if ! chown -R appuser:appuser /var/www/html 2>&1; then
  err "Warning: Could not set ownership on /var/www/html. Attempting to fix vendor directory only..."
  # If full chown fails, at least try to fix the vendor directory
  chown -R appuser:appuser /var/www/html/vendor 2>&1 || {
    err "ERROR: Could not set ownership on vendor directory."
    err "This is likely due to running the container without sufficient privileges."
    err "Make sure the container is running as root initially, or fix host directory permissions."
    # Try to make vendor world-writable as last resort
    chmod -R 777 /var/www/html/vendor 2>/dev/null || true
  }
fi

# Set permissions for writable directories
if ! chmod -R 775 storage bootstrap/cache 2>&1; then
  err "Warning: Could not set permissions on storage/bootstrap directories."
  # Try individual directories
  chmod -R 777 storage 2>/dev/null || true
  chmod -R 777 bootstrap/cache 2>/dev/null || true
fi

# Ensure vendor is writable for composer
if ! chmod -R 775 vendor 2>&1; then
  err "Warning: Could not set 775 permissions on vendor. Trying 777..."
  chmod -R 777 vendor 2>/dev/null || {
    err "ERROR: Could not set any permissions on vendor directory."
    err "Composer install will likely fail."
  }
fi

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
  
  # Verify vendor directory is writable before attempting composer install
  if [ ! -w vendor ]; then
    err "ERROR: vendor directory exists but is not writable!"
    err "Trying to fix permissions one more time..."
    chmod 777 vendor 2>/dev/null || {
      err "FATAL: Cannot make vendor directory writable even as root."
      err "This may be a filesystem or mount issue."
      exit 1
    }
  fi
  
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
    err ""
    err "If the error is about vendor directory not being created, this could mean:"
    err "  1. The container is not running as root (check Dockerfile)"
    err "  2. There's a filesystem mount issue (check docker-compose.yml volumes)"
    err "  3. SELinux or AppArmor is blocking the operation (check host security)"
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

# Execute the main command
# For php-fpm, run as root since it manages its own user privileges
# For other commands (like artisan), run as appuser for security
if [ "$1" = "php-fpm" ]; then
  exec "$@"
else
  exec gosu appuser "$@"
fi