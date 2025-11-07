# Docker Quick Reference

This document provides quick reference commands for managing the GDGoC Certificate Platform with Docker.

## Starting & Stopping

```bash
# Start all services
docker compose up -d

# Start specific service
docker compose up -d php

# Stop all services
docker compose down

# Stop and remove volumes (WARNING: deletes data!)
docker compose down -v

# Restart all services
docker compose restart

# Restart specific service
docker compose restart queue-worker
```

## Building & Updating

```bash
# Build images
docker compose build

# Build without cache
docker compose build --no-cache

# Pull latest images
docker compose pull

# Update and restart services
docker compose up -d --build
```

## Viewing Logs

```bash
# View all logs
docker compose logs

# Follow logs (real-time)
docker compose logs -f

# View specific service logs
docker compose logs php
docker compose logs queue-worker
docker compose logs scheduler

# Follow specific service logs
docker compose logs -f php

# View last N lines
docker compose logs --tail=100 php
```

## Running Commands

```bash
# Execute artisan commands
docker compose exec php php artisan migrate
docker compose exec php php artisan cache:clear
docker compose exec php php artisan queue:work

# Access PHP container shell
docker compose exec php sh

# Run composer commands
docker compose exec php composer install
docker compose exec php composer update

# Run npm commands (if needed)
docker compose exec php npm install
docker compose exec php npm run build
```

## Laravel Artisan Commands

```bash
# Migrations
docker compose exec php php artisan migrate
docker compose exec php php artisan migrate:fresh
docker compose exec php php artisan migrate:rollback

# Seeding
docker compose exec php php artisan db:seed

# Cache management
docker compose exec php php artisan config:cache
docker compose exec php php artisan route:cache
docker compose exec php php artisan view:cache
docker compose exec php php artisan cache:clear
docker compose exec php php artisan config:clear
docker compose exec php php artisan route:clear
docker compose exec php php artisan view:clear

# Optimization
docker compose exec php php artisan optimize
docker compose exec php php artisan optimize:clear

# Queue management
docker compose exec php php artisan queue:work
docker compose exec php php artisan queue:restart
docker compose exec php php artisan queue:clear

# Maintenance mode
docker compose exec php php artisan down
docker compose exec php php artisan up
```

## Database Management

```bash
# PostgreSQL
docker compose exec postgres psql -U gdgoc_user -d gdgoc_certs

# MySQL
docker compose exec mysql mysql -u gdgoc_user -p gdgoc_certs

# Backup PostgreSQL
docker compose exec postgres pg_dump -U gdgoc_user gdgoc_certs > backup.sql

# Restore PostgreSQL
cat backup.sql | docker compose exec -T postgres psql -U gdgoc_user gdgoc_certs

# Backup MySQL
docker compose exec mysql mysqldump -u gdgoc_user -p gdgoc_certs > backup.sql

# Restore MySQL
cat backup.sql | docker compose exec -T mysql mysql -u gdgoc_user -p gdgoc_certs
```

## Redis Management

```bash
# Access Redis CLI
docker compose exec redis redis-cli

# Check Redis connection
docker compose exec redis redis-cli ping

# Clear cache
docker compose exec redis redis-cli FLUSHDB

# View all keys
docker compose exec redis redis-cli KEYS '*'
```

## Service Status

```bash
# Check service status
docker compose ps

# View resource usage
docker stats

# Check health status
docker compose ps --filter "health=healthy"
```

## Scaling Services

```bash
# Scale queue workers
docker compose up -d --scale queue-worker=3

# Scale back down
docker compose up -d --scale queue-worker=1
```

## Debugging

```bash
# View service configuration
docker compose config

# Inspect specific service
docker compose config php

# View container details
docker inspect gdgoc-php

# Check container resource usage
docker stats gdgoc-php

# View container processes
docker compose top php
```

## Cleanup

```bash
# Remove stopped containers
docker compose rm

# Remove all unused containers, networks, images
docker system prune

# Remove all unused volumes (WARNING: deletes data!)
docker volume prune

# Remove specific volume
docker volume rm gdgoc-certs-v3_postgres_data

# View disk usage
docker system df
```

## Troubleshooting

```bash
# View logs for errors
docker compose logs php | grep -i error
docker compose logs queue-worker | grep -i failed

# Check container health
docker compose ps
docker inspect --format='{{.State.Health.Status}}' gdgoc-php

# Restart unhealthy service
docker compose restart php

# Rebuild service from scratch
docker compose down
docker compose build --no-cache php
docker compose up -d

# Check port bindings
docker compose port nginx 80

# View environment variables
docker compose exec php env
```

## Production Deployment

```bash
# Initial deployment
git clone <repo>
cd <repo>
cp .env.example .env
# Edit .env
docker compose up -d --build
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --force
docker compose exec php php artisan optimize

# Update deployment
git pull
docker compose pull
docker compose up -d --remove-orphans
docker compose exec php php artisan migrate --force
docker compose exec php php artisan optimize

# Rollback
git checkout <previous-commit>
docker compose up -d --build
docker compose exec php php artisan migrate:rollback
```

## Common Issues

### Service won't start
```bash
docker compose logs <service-name>
docker compose restart <service-name>
```

### Permission issues

Storage and bootstrap/cache permission errors are now automatically fixed on container startup. The entrypoint script ensures these directories exist with correct permissions (775).

If you still encounter permission errors:
```bash
docker compose exec php chmod -R 775 storage bootstrap/cache
```

**Note:** The redundant `./storage` mount has been removed from docker-compose.yml. The storage directory is now managed through the main `.:/var/www/html` mount with automatic permission fixes applied on startup.

### Vendor directory cannot be created
If you see errors like `/var/www/html/vendor does not exist and could not be created`, this is due to permission conflicts between the host mount and the container user. This has been fixed by using named volumes for `vendor` and `node_modules`. To resolve:

```bash
# Remove old vendor volume if it exists
docker compose down -v

# Rebuild and restart
docker compose build --no-cache
docker compose up -d
```

The `vendor` and `node_modules` directories are now stored in Docker named volumes, which:
- Preserve dependencies built in the Docker image
- Prevent permission conflicts with host-mounted files
- Allow the non-root `appuser` to manage dependencies

### Nginx "host not found" error
If you see nginx errors like `host not found in upstream "php"`, this means nginx started before PHP-FPM was ready:

```bash
# Check service health status
docker compose ps

# Wait for all services to be healthy
docker compose up -d --wait

# Check PHP service logs
docker compose logs php
```

The PHP service now has a health check that ensures it's fully initialized before nginx starts. If issues persist:
1. Ensure the PHP container is running: `docker compose ps php`
2. Check for errors in PHP logs: `docker compose logs php`
3. Restart the services: `docker compose restart php nginx`

### Queue jobs not processing
```bash
docker compose logs queue-worker
docker compose restart queue-worker
```

### Database connection issues
```bash
docker compose ps postgres
docker compose logs postgres
docker compose restart postgres
```

### Out of memory
```bash
docker stats
# Increase Docker memory allocation in Docker settings
```

### Port already in use
```bash
# Check what's using the port
sudo lsof -i :8000
# Change APP_PORT in .env
APP_PORT=8080
docker compose up -d
```

## Best Practices

1. **Always check logs when troubleshooting**
   ```bash
   docker compose logs -f
   ```

2. **Regular backups**
   ```bash
   # Schedule regular database backups
   0 2 * * * cd /path/to/app && docker compose exec -T postgres pg_dump -U gdgoc_user gdgoc_certs > /backups/db_$(date +\%Y\%m\%d).sql
   ```

3. **Monitor resource usage**
   ```bash
   docker stats --no-stream
   ```

4. **Keep images updated**
   ```bash
   docker compose pull
   docker compose up -d
   ```

5. **Use .env for configuration**
   - Never commit `.env` files
   - Use `.env.example` as template
   - Keep secrets secure

6. **Health checks**
   ```bash
   # Regularly check service health
   docker compose ps
   ```

## Security Tips

1. **Change default credentials immediately**
2. **Use strong passwords in `.env`**
3. **Keep Docker and images updated**
4. **Limit exposed ports in production**
5. **Use secrets management for sensitive data**
6. **Regular security audits**
7. **Monitor logs for suspicious activity**

## Performance Tips

1. **Use Redis for caching and queues**
2. **Scale queue workers based on load**
3. **Optimize Laravel caching**
   ```bash
   docker compose exec php php artisan optimize
   ```
4. **Monitor database performance**
5. **Use CDN for static assets**
6. **Enable opcache in production**

## Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Laravel Documentation](https://laravel.com/docs)
- [DEPLOYMENT.md](DEPLOYMENT.md) - Complete deployment guide
