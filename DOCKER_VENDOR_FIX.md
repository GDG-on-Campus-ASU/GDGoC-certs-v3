# Docker Vendor Directory Creation Fix

## Problem

When running `docker compose up`, the application fails with the following error:

```bash
vendor/autoload.php not found — running composer install...
Installing dependencies from lock file
Verifying lock file contents can be installed on current platform.
Package operations: 82 installs, 0 updates, 0 removals

In Filesystem.php line 261:
                                                                 
  /var/www/html/vendor does not exist and could not be created:  
                                                                 

composer install failed
```

## Root Cause

The issue occurs in the `docker-entrypoint.sh` script when:

1. **Silent Permission Failures**: The script attempted to fix permissions with `chown`, but errors were suppressed with `2>/dev/null`
2. **Insufficient Fallback**: When the initial permission fixes failed, the script continued without ensuring the vendor directory was writable
3. **Cryptic Error**: When composer install ran as fallback, it failed with an unclear error message

The underlying causes can be:
- Host directory mounted over the container's vendor directory with incompatible permissions
- Container not running as root initially, preventing permission fixes
- Filesystem or mount restrictions (SELinux, AppArmor, etc.)

## Solution

The fix improves the `docker-entrypoint.sh` script with progressive permission handling and better error detection:

### 1. Ownership Fixing (Lines 29-39)

**Before:**
```sh
chown -R appuser:appuser /var/www/html 2>/dev/null || {
  err "Warning: Could not set ownership. Continuing anyway..."
}
```

**After:**
```sh
if ! chown -R appuser:appuser /var/www/html 2>&1; then
  err "Warning: Could not set ownership on /var/www/html. Attempting to fix vendor directory only..."
  chown -R appuser:appuser /var/www/html/vendor 2>&1 || {
    err "ERROR: Could not set ownership on vendor directory."
    err "This is likely due to running the container without sufficient privileges."
    err "Make sure the container is running as root initially, or fix host directory permissions."
    chmod -R 777 /var/www/html/vendor 2>/dev/null || true
  }
fi
```

**Benefits:**
- Detects when chown fails instead of suppressing errors
- Attempts targeted fix on vendor directory if full chown fails
- Falls back to world-writable permissions (777) as last resort
- Provides clear error messages about what failed and why

### 2. Progressive Permission Setting (Lines 41-56)

**New code:**
```sh
# Set permissions for writable directories
if ! chmod -R 775 storage bootstrap/cache 2>&1; then
  err "Warning: Could not set permissions on storage/bootstrap directories."
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
```

**Benefits:**
- Tries restrictive permissions (775) first for better security
- Falls back to permissive (777) if needed for compatibility
- Treats vendor directory specially since it's critical for composer

### 3. Pre-Composer Writability Check (Lines 80-89)

**New code:**
```sh
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
```

**Benefits:**
- Fails fast with clear error if vendor is not writable
- Prevents the cryptic "vendor does not exist and could not be created" error
- Attempts one final fix before giving up

### 4. Enhanced Error Messages (Lines 102-105)

**New code:**
```sh
err "If the error is about vendor directory not being created, this could mean:"
err "  1. The container is not running as root (check Dockerfile)"
err "  2. There's a filesystem mount issue (check docker-compose.yml volumes)"
err "  3. SELinux or AppArmor is blocking the operation (check host security)"
```

**Benefits:**
- Provides specific troubleshooting steps
- Identifies common causes
- Directs users to check specific configurations

## How to Apply This Fix

### For New Deployments

Simply clone the repository and start the services:

```bash
git clone <repository-url>
cd GDGoC-certs-v3
docker compose up -d
```

### For Existing Deployments

1. Pull the latest changes:
   ```bash
   git pull origin main
   ```

2. Stop existing containers:
   ```bash
   docker compose down
   ```

3. Rebuild the image (recommended):
   ```bash
   docker compose build --no-cache
   ```

4. Start the services:
   ```bash
   docker compose up -d
   ```

5. Verify the services are running:
   ```bash
   docker compose ps
   docker compose logs php
   ```

## Troubleshooting

### If You Still See Permission Errors

1. **Check that the container runs as root initially:**
   ```bash
   docker compose exec php whoami
   ```
   The entrypoint should start as root, then switch to `appuser`.

2. **Check volume mounts in docker-compose.yml:**
   ```yaml
   volumes:
     - .:/var/www/html
     - vendor:/var/www/html/vendor  # Named volume for vendor
   ```

3. **Check SELinux/AppArmor status:**
   ```bash
   # For SELinux
   sestatus
   
   # For AppArmor
   aa-status
   ```

4. **Manually fix host directory permissions (if needed):**
   ```bash
   sudo chown -R $USER:$USER .
   chmod -R 775 storage bootstrap/cache
   ```

### If Composer Install Fails During Build

The error in the problem statement suggests composer might fail during image build (not at runtime). If you see SSL or network errors during build:

1. **Check network connectivity:**
   ```bash
   docker build --progress=plain .
   ```

2. **Use cache server or mirrors (if available):**
   Add to your Dockerfile before composer install:
   ```dockerfile
   RUN composer config -g repo.packagist composer https://packagist.org
   ```

3. **Disable SSL verification (only for development):**
   ```dockerfile
   ENV COMPOSER_DISABLE_SSL_VERIFICATION=1
   ```

## Testing the Fix

After applying the fix, verify it works:

```bash
# Start services
docker compose up -d

# Check PHP service logs
docker compose logs php | grep -i vendor

# Verify vendor directory exists and is populated
docker compose exec php ls -la vendor/ | head -20

# Check autoload.php exists
docker compose exec php test -f vendor/autoload.php && echo "OK" || echo "MISSING"

# Test the application
curl http://localhost:8000
```

## Key Benefits of This Fix

1. ✅ **Better error detection** - Permission failures are caught instead of ignored
2. ✅ **Progressive fallback** - Multiple strategies tried before giving up
3. ✅ **Clear error messages** - Users get actionable guidance when issues occur
4. ✅ **Fail-fast validation** - Checks writability before running composer
5. ✅ **Maintains security** - Tries restrictive permissions first, falls back only when needed

## Related Documentation

- [VENDOR_DIRECTORY_FIX.md](VENDOR_DIRECTORY_FIX.md) - Previous fix for related issues
- [DOCKER_REFERENCE.md](DOCKER_REFERENCE.md) - General Docker setup documentation
- [Docker Compose Volumes](https://docs.docker.com/compose/compose-file/volumes/)

## Additional Notes

This fix focuses on the **entrypoint script** runtime behavior. If the Docker image build itself fails (as mentioned in the original error), you may need to:

1. Check your internet connection and DNS resolution
2. Verify composer.lock is up to date
3. Consider using composer cache volumes during build
4. Review any SSL/certificate issues in your environment

The entrypoint script now provides better diagnostics when vendor directory issues occur at runtime, helping identify whether the problem is in the image build or the runtime environment.
