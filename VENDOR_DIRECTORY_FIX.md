# Vendor Directory Permission Fix

## Problem Statement

When running the application with Docker Compose, users encountered the following error:

```
/var/www/html/vendor does not exist and could not be created

In Filesystem.php line 261:
  /var/www/html/vendor does not exist and could not be created
```

This error occurred during the container startup when the `docker-entrypoint.sh` script attempted to run `composer install`.

## Root Cause Analysis

The issue was caused by a permission conflict between the Docker image and the volume mounts in docker-compose.yml:

1. **Docker Image Build**: 
   - The Dockerfile builds PHP dependencies using `composer install` 
   - Dependencies are installed in `/var/www/html/vendor` as the root user during build
   - Then the image switches to a non-root user (`appuser` with UID 1000)

2. **Volume Mount Conflict**:
   - `docker-compose.yml` mounted the entire host directory: `.:/var/www/html`
   - This mount **overwrites** the `/var/www/html/vendor` directory from the Docker image
   - The mounted directory is owned by the host user, not `appuser`

3. **Permission Denied**:
   - When the entrypoint script runs as `appuser` and tries to create `/var/www/html/vendor`
   - It fails because `appuser` doesn't have write permissions to the mounted host directory

## Solution

The fix uses Docker **named volumes** to preserve the vendor and node_modules directories:

### Changes to docker-compose.yml

Added named volumes for `vendor` and `node_modules` to all PHP-based services:

```yaml
services:
  php:
    volumes:
      - .:/var/www/html
      - ./storage:/var/www/html/storage
      - vendor:/var/www/html/vendor          # Named volume
      - node_modules:/var/www/html/node_modules  # Named volume

volumes:
  vendor:
    driver: local
  node_modules:
    driver: local
```

This was applied to three services:
- `php` (main PHP-FPM service)
- `queue-worker` (background job processor)
- `scheduler` (scheduled task runner)

### Changes to Dockerfile

Updated the permissions to ensure `appuser` owns the vendor directory:

```dockerfile
# Set permissions
RUN chown -R appuser:appuser /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/vendor
```

### Changes to Redis Installation

Updated PECL Redis installation to handle network issues better:

```dockerfile
# Install Redis extension
RUN pecl channel-update pecl.php.net && \
    pecl install redis && \
    docker-php-ext-enable redis
```

### Changes to docker-entrypoint.sh

Removed attempts to create or chmod the vendor directory in the entrypoint script:

```sh
# Before (caused permission errors):
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache vendor
chmod 777 vendor 2>/dev/null || {
  err "Warning: Could not set permissions on vendor directory. Composer may fail."
}

# After (relies on named volume):
# Note: vendor directory is managed by Docker named volume and created in Dockerfile
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
```

**Why this change is necessary:**
- The entrypoint script runs as the non-root `appuser`
- When `appuser` tried to create or chmod the vendor directory, it failed due to permission issues
- The vendor directory is already created in the Dockerfile with proper permissions
- The named volume preserves this directory, so the entrypoint doesn't need to manage it
- Removing these operations prevents the error while maintaining proper functionality

## How It Works

1. **Named volumes override host mounts**: When both a host mount and a named volume target the same path, the named volume takes precedence for that specific subdirectory.

2. **Preservation of built artifacts**: The vendor directory built during the Docker image creation is copied to the named volume and preserved.

3. **Proper permissions**: The vendor directory is owned by `appuser`, so the entrypoint script can successfully run composer commands.

4. **Persistent dependencies**: Dependencies persist across container restarts without being overwritten by the host mount.

## Benefits

1. ✅ **No permission errors**: The `appuser` can read and write to the vendor directory
2. ✅ **Faster startup**: Dependencies from the Docker image are reused
3. ✅ **Consistent behavior**: Works the same in development and production
4. ✅ **Reduced disk usage**: Dependencies aren't duplicated on the host filesystem
5. ✅ **Security**: Maintains non-root user security best practices

## Testing the Fix

To test the fix after applying these changes:

```bash
# Clean up any existing volumes
docker compose down -v

# Rebuild the image (if network issues with PECL, see Troubleshooting)
docker compose build --no-cache

# Start services
docker compose up -d

# Check that services started successfully
docker compose ps

# Verify vendor directory exists and has correct permissions
docker compose exec php ls -la /var/www/html/vendor

# Check logs for any errors
docker compose logs php
```

## Troubleshooting

### PECL Network Issues

If you encounter DNS resolution errors when building the image:

```
Cannot retrieve channel.xml for channel "pecl.php.net"
```

This is a temporary network restriction. The Redis extension is optional for basic functionality. You can:

1. Wait and retry the build later when network access is available
2. Use a different network connection
3. For testing only, temporarily comment out the Redis extension installation

### Resetting Volumes

If you need to completely reset the vendor directory:

```bash
# WARNING: This removes ALL volumes including database data
docker compose down -v

# Rebuild and restart
docker compose build --no-cache
docker compose up -d
```

### Checking Volume Contents

To inspect what's in the vendor volume:

```bash
docker compose exec php ls -la /var/www/html/vendor
docker volume inspect gdgoc-certs-v3_vendor
```

### Nginx "Host Not Found" Error

If you see nginx errors like:
```
nginx: [emerg] host not found in upstream "php" in /etc/nginx/conf.d/default.conf:13
```

This occurs when nginx starts before PHP-FPM is fully initialized on the Docker network. This has been fixed by:

1. Adding a health check to the PHP service
2. Configuring nginx to wait for PHP to be healthy before starting

To verify:
```bash
# Check service health status
docker compose ps

# Wait for services to be healthy
docker compose up -d --wait

# Check logs if issues persist
docker compose logs nginx
docker compose logs php
```

### Storage and Bootstrap/Cache Permission Errors

If you see Laravel migration errors like:
```
The stream or file "/var/www/html/storage/logs/laravel.log" could not be opened in append mode: Failed to open stream: Permission denied
The /var/www/html/bootstrap/cache directory must be present and writable
```

This has been fixed by:

1. **Removing redundant storage mounts** - The explicit `./storage:/var/www/html/storage` mounts were causing permission conflicts
2. **Automatic permission fixes** - The entrypoint script now ensures directories exist with 777 permissions for maximum CI compatibility
3. **Explicit permissions in Dockerfile** - Storage and bootstrap/cache are explicitly set with proper permissions
4. **CI workflow integration** - Added explicit permission-fixing step in docker-test.yml before migrations

**For local development:**
Simply restart the containers - the entrypoint script handles permissions automatically:
```bash
docker compose restart php queue-worker scheduler
```

**For CI/CD environments:**
The docker-test.yml workflow sets permissions on the HOST before mounting to avoid "Operation not permitted" errors:
```yaml
- name: Set Laravel directory permissions
  run: |
    # Create directories on host with proper permissions before they're mounted
    mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
    chmod -R 777 storage bootstrap/cache
```

**Why this approach?**
- The container runs as non-root user `appuser` (UID 1000)
- Host-mounted directories are owned by the CI runner user
- `appuser` cannot chmod host-owned files (results in "Operation not permitted")
- Setting permissions on the host BEFORE mounting solves this issue

If issues persist, check the entrypoint logs:
```bash
docker compose logs php | grep -i permission
```

Or manually fix permissions on the host:
```bash
chmod -R 777 storage bootstrap/cache
```

## Migration Guide

For existing deployments, follow these steps:

1. **Backup your data** (especially database volumes)
2. Pull the latest changes with this fix
3. Stop the containers: `docker compose down`
4. **Optional but recommended**: Remove vendor volume: `docker volume rm gdgoc-certs-v3_vendor`
5. Rebuild the image: `docker compose build --no-cache`
6. Start the services: `docker compose up -d`
7. Verify everything works: `docker compose ps` and `docker compose logs`

## References

- [Docker Compose Volume Documentation](https://docs.docker.com/compose/compose-file/volumes/)
- [Docker Volume Best Practices](https://docs.docker.com/storage/volumes/)
- [Laravel Docker Deployment](https://laravel.com/docs/deployment)

## Additional Fix (November 2025) - Revision 2

### Root Cause of Persistent Issue
The initial fix didn't fully resolve the problem because of how Docker Compose volume mounts interact:

1. **Volume Mount Order**: When docker-compose mounts `.:/var/www/html`, it replaces the entire directory with the host directory
2. **Host Directory Ownership**: The host directory is owned by the host user (not UID 1000), so when mounted, `/var/www/html` becomes owned by the host user
3. **Named Volume Limitation**: Even though `vendor:/var/www/html/vendor` is mounted as a named volume, the parent directory is still owned by the host user
4. **Permission Denied**: The `appuser` (UID 1000) cannot create or modify files in directories owned by the host user

The previous fix attempted to set ownership in the Dockerfile, but this was overridden by the host mount.

### Final Solution: Root Entrypoint with gosu

The correct solution is to run the entrypoint script as root, fix all permissions, then switch to `appuser` for executing the application:

**Changes to Dockerfile:**
1. Install `gosu` package for secure user switching
2. Remove `USER appuser` directive - let the container start as root
3. The entrypoint script will handle the user switch

```dockerfile
# Install gosu for secure user switching
RUN apt-get update && apt-get install -y --no-install-recommends \
    ...
    gosu \
    && rm -rf /var/lib/apt/lists/*

# Note: We don't switch to non-root user yet to allow entrypoint script to fix permissions
# The entrypoint script will handle permission fixes and then exec as appuser
```

**Changes to docker-entrypoint.sh:**
The script now:
1. Runs as root initially
2. Creates all necessary directories (including vendor)
3. Fixes ownership to `appuser:appuser` for all files
4. Sets appropriate permissions
5. Attempts composer install if vendor/autoload.php is missing
6. Uses `gosu` to exec the final command as `appuser`

```sh
# Running as root to fix permissions before switching to appuser
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
mkdir -p vendor

# Fix ownership - change all files to appuser:appuser
chown -R appuser:appuser /var/www/html 2>/dev/null || {
  err "Warning: Could not set ownership. Continuing anyway..."
}

# Set permissions for writable directories
chmod -R 775 storage bootstrap/cache vendor 2>/dev/null || {
  err "Warning: Could not set all permissions. Some features may not work correctly."
}

# ... composer install if needed ...

# Execute the main command as appuser for security
exec gosu appuser "$@"
```

### Why This Solution Works

1. **Root Privileges**: Running as root allows fixing permissions on host-mounted directories
2. **gosu for Security**: After fixing permissions, `gosu` properly switches to `appuser` and execs the command, maintaining security
3. **Handles All Cases**: Works whether vendor exists in the image, in the named volume, or needs to be installed
4. **No Restart Loop**: If composer install fails, provides clear instructions instead of exiting immediately

## Summary

This comprehensive fix resolves multiple Docker deployment issues:

1. **Vendor Directory Permissions** - Uses Docker named volumes to preserve built dependencies and maintain proper ownership
2. **Root Entrypoint with gosu** - Entrypoint runs as root to fix permissions from host mounts, then securely switches to `appuser` using gosu
3. **Automatic Permission Fixing** - All permission issues are resolved on container startup by the root-level entrypoint
4. **Graceful Fallback** - If vendor is missing, attempts composer install with clear error messages and instructions
5. **Nginx Startup Race Condition** - Adds PHP health checks to ensure nginx starts only when PHP-FPM is ready
6. **Storage/Cache Permissions** - Removes redundant mounts and adds automatic permission fixes in the entrypoint script

The solution follows Docker best practices by:
- Running the application as a non-root user (`appuser`) for security
- Using the entrypoint to handle permission fixes before switching users
- Utilizing `gosu` for clean process replacement when switching users
- Maintaining proper ownership across host mounts and named volumes

All permission issues are automatically resolved on container startup, whether you're using host mounts for development or named volumes for production, ensuring a smooth deployment experience.
