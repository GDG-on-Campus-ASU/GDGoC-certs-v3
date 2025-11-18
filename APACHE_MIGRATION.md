# Migration from Nginx + PHP-FPM to Apache + mod_php

## Overview

This document explains the migration from a multi-container setup using Nginx and PHP-FPM to a single-container setup using Apache with mod_php.

## Problem Statement

The previous Docker setup used two separate containers:
1. **nginx** - Web server in a separate container
2. **php** - PHP-FPM processor in another container

This architecture was causing permission issues with the vendor directory:

```bash
vendor/autoload.php not found — running composer install...
Installing dependencies from lock file
Verifying lock file contents can be installed on current platform.
Package operations: 82 installs, 0 updates, 0 removals

In Filesystem.php line 261:
                                                                 
  /var/www/html/vendor does not exist and could not be created:  
                                                                 

composer install failed
```

### Root Causes

1. **Container Separation**: Nginx and PHP-FPM running in separate containers created complex volume sharing scenarios
2. **Permission Conflicts**: Different user contexts between containers caused permission issues
3. **Volume Mounting**: Host mounts and named volumes had conflicting ownership
4. **Complexity**: Managing two containers added unnecessary complexity for a monolithic application

## Solution: Apache + mod_php

The solution consolidates the web server and PHP runtime into a single container using Apache with mod_php.

### Architecture Changes

**Before:**
```
┌─────────────┐      ┌──────────────┐
│   Nginx     │─────▶│   PHP-FPM    │
│ (Container) │      │ (Container)  │
└─────────────┘      └──────────────┘
       │                     │
       └─────────┬───────────┘
                 │
          ┌──────▼──────┐
          │   Volumes   │
          └─────────────┘
```

**After:**
```
┌────────────────────────┐
│  Apache + mod_php      │
│  (Single Container)    │
└────────────────────────┘
           │
    ┌──────▼──────┐
    │   Volumes   │
    └─────────────┘
```

### Key Changes

#### 1. Dockerfile

**Changed base image:**
```dockerfile
# Before
FROM php:8.3-fpm AS builder
FROM php:8.3-fpm

# After
FROM php:8.3-apache AS builder
FROM php:8.3-apache
```

**Added Apache configuration:**
```dockerfile
# Enable Apache modules
RUN a2enmod rewrite headers

# Create Apache virtual host configuration
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
    \n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    \n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf
```

**Changed user from appuser to www-data:**
```dockerfile
# Before
COPY --from=builder --chown=appuser:appuser /var/www/html /var/www/html

# After
COPY --from=builder --chown=www-data:www-data /var/www/html /var/www/html
```

**Updated port exposure:**
```dockerfile
# Before
EXPOSE 9000

# After
EXPOSE 80
```

**Changed default command:**
```dockerfile
# Before
CMD ["php-fpm"]

# After
CMD ["apache2-foreground"]
```

#### 2. docker-compose.yml

**Removed nginx service entirely** and updated the php service:

```yaml
# Before
services:
  php:
    # PHP-FPM Service
    ...
    healthcheck:
      test: ["CMD-SHELL", "php-fpm -t || exit 1"]
  
  nginx:
    image: nginx:alpine
    ports:
      - "${APP_PORT:-8000}:80"
    volumes:
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      php:
        condition: service_healthy

# After
services:
  php:
    # PHP + Apache Service (combined web server and PHP runtime)
    ports:
      - "${APP_PORT:-8000}:80"
    healthcheck:
      test: ["CMD-SHELL", "curl -f http://localhost/ || exit 1"]
```

#### 3. docker-entrypoint.sh

**Updated user references from appuser to www-data:**

```bash
# Before
chown -R appuser:appuser /var/www/html
gosu appuser php artisan migrate

# After
chown -R www-data:www-data /var/www/html
gosu www-data php artisan migrate
```

**Updated command handling:**

```bash
# Before
if [ "$1" = "php-fpm" ]; then
  exec "$@"
else
  exec gosu appuser "$@"
fi

# After
if [ "$1" = "apache2-foreground" ]; then
  exec "$@"
else
  exec gosu www-data "$@"
fi
```

### Benefits

1. ✅ **Simplified Architecture**: One container instead of two
2. ✅ **No Permission Issues**: Single user context (www-data) for all operations
3. ✅ **Easier to Maintain**: Fewer moving parts to manage
4. ✅ **Better Performance**: No FastCGI communication overhead
5. ✅ **Consistent Ownership**: All files owned by the same user
6. ✅ **Reduced Complexity**: Fewer volume mounts to configure

### CI/CD Environment Considerations

Added SSL verification workarounds for restricted CI/CD environments:

```dockerfile
# Configure git to not verify SSL for composer (only needed in restricted CI/CD environments)
RUN git config --global http.sslVerify false

# Install PHP dependencies with SSL verification disabled for CI/CD environments
ENV COMPOSER_DISABLE_SSL_VERIFICATION=1
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Install Node dependencies with SSL verification disabled for CI/CD environments
RUN npm config set strict-ssl false && npm ci
```

**Note**: These workarounds should only be used in trusted CI/CD environments. For production deployments, ensure proper SSL certificates are configured.

## Migration Steps

For existing deployments, follow these steps to migrate:

### 1. Backup Current State

```bash
# Backup database
docker compose exec postgres pg_dump -U gdgoc_user gdgoc_certs > backup.sql

# Or for MySQL
docker compose exec mysql mysqldump -u gdgoc_user -p gdgoc_certs > backup.sql

# Backup volumes
docker run --rm -v gdgoc-certs-v3_postgres_data:/data -v $(pwd):/backup alpine tar czf /backup/postgres_data.tar.gz /data
```

### 2. Stop All Services

```bash
docker compose down
```

### 3. Update Repository

```bash
git pull origin main
```

### 4. Rebuild Images

```bash
# Remove old images (optional but recommended)
docker compose down --rmi all -v

# Build new images
docker compose build --no-cache
```

### 5. Start Services

```bash
docker compose up -d
```

### 6. Verify

```bash
# Check all services are running
docker compose ps

# Check PHP/Apache logs
docker compose logs php

# Test the application
curl http://localhost:8000
```

### 7. Restore Data (if needed)

```bash
# Restore database if needed
cat backup.sql | docker compose exec -T postgres psql -U gdgoc_user gdgoc_certs
```

## Troubleshooting

### Container Won't Start

```bash
# Check logs
docker compose logs php

# Verify the image was built correctly
docker images | grep gdgoc

# Rebuild if necessary
docker compose build --no-cache php
```

### Permission Errors

The new setup should eliminate permission errors, but if they occur:

```bash
# Ensure vendor directory exists
docker compose exec php ls -la vendor/

# Check ownership
docker compose exec php ls -la /var/www/html/

# Recreate the container
docker compose down
docker compose up -d
```

### Apache Not Responding

```bash
# Check if Apache is running
docker compose exec php ps aux | grep apache

# Check Apache configuration
docker compose exec php apache2ctl -t

# Restart the service
docker compose restart php
```

## Compatibility Notes

### Configuration Files

The following configuration files are **no longer needed**:
- `docker/nginx/default.conf` - Nginx configuration
- `docker/php-fpm/www.conf` - PHP-FPM pool configuration

The Apache virtual host is now configured directly in the Dockerfile.

### Environment Variables

All environment variables remain the same. No changes needed to `.env` file.

### Port Mappings

Port mapping remains the same:
- Host port: `${APP_PORT:-8000}` (configurable via `.env`)
- Container port: `80` (Apache default)

## Performance Considerations

### Apache vs Nginx + PHP-FPM

**Apache with mod_php:**
- ✅ Simpler configuration
- ✅ Lower latency (no FastCGI overhead)
- ✅ Better for monolithic applications
- ⚠️ Higher memory per request (process-based)

**Nginx + PHP-FPM:**
- ✅ Better for serving static files
- ✅ Lower memory footprint (event-driven)
- ✅ Better for microservices architecture
- ⚠️ More complex configuration
- ⚠️ Permission management challenges

For this application, Apache with mod_php is more suitable because:
1. It's a monolithic Laravel application
2. Laravel handles asset compilation (Vite)
3. Simplicity and reliability are priorities
4. Permission issues were causing deployment problems

## Security Notes

### SSL Verification in Production

The Dockerfile includes SSL verification workarounds for CI/CD environments:

```dockerfile
RUN git config --global http.sslVerify false
ENV COMPOSER_DISABLE_SSL_VERIFICATION=1
RUN npm config set strict-ssl false
```

**Important**: These settings are for build-time only and don't affect runtime security. However, for maximum security in production:

1. Use proper SSL certificates in your CI/CD environment
2. Remove these workarounds if possible
3. Use trusted package registries
4. Enable dependency scanning

### User Context

Apache runs as `www-data` user (default), which is:
- A non-root user (secure)
- Standard for Debian-based PHP images
- Well-supported by Laravel and Composer

## Additional Resources

- [Apache HTTP Server Documentation](https://httpd.apache.org/docs/)
- [PHP mod_php Documentation](https://www.php.net/manual/en/install.unix.apache2.php)
- [Docker Official PHP Images](https://hub.docker.com/_/php)
- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)

## Support

If you encounter issues with this migration:

1. Check this document's Troubleshooting section
2. Review the [DOCKER_REFERENCE.md](DOCKER_REFERENCE.md) for common Docker commands
3. Check [DEPLOYMENT.md](DEPLOYMENT.md) for detailed deployment instructions
4. Open an issue on GitHub with:
   - Error messages
   - Output of `docker compose logs php`
   - Output of `docker compose ps`
