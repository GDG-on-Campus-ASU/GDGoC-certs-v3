# GDGoC Certificates - Docker Deployment Guide

This document provides instructions for deploying the GDGoC Certificate Generation Platform using Docker and Docker Compose.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Architecture Overview](#architecture-overview)
- [Local Development Setup](#local-development-setup)
- [Production Deployment](#production-deployment)
- [NGINX Proxy Manager Configuration](#nginx-proxy-manager-configuration)
- [CI/CD Setup](#cicd-setup)
- [Troubleshooting](#troubleshooting)

## Prerequisites

- Docker Engine 20.10+
- Docker Compose V2 2.0+
- Git
- For production: A server with Docker installed and SSH access

## Architecture Overview

The application consists of the following services:

1. **PHP-FPM** (`php`) - Main application service
2. **Queue Worker** (`queue-worker`) - Processes background jobs
3. **Scheduler** (`scheduler`) - Runs scheduled Laravel tasks
4. **NGINX** (`nginx`) - Internal web server for PHP-FPM routing
5. **PostgreSQL** (`postgres`) - Primary database (default)
6. **MySQL** (`mysql`) - Alternative database option
7. **Redis** (`redis`) - Cache and queue storage

## Local Development Setup

### 1. Clone the Repository

```bash
git clone https://github.com/KirolosMFahem/GDGoC-certs-v3.git
cd GDGoC-certs-v3
```

### 2. Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

Update the `.env` file with your local settings:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration (PostgreSQL by default)
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=gdgoc_certs
DB_USERNAME=gdgoc_user
DB_PASSWORD=secret

# Redis Configuration
REDIS_HOST=redis
REDIS_PORT=6379
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Domain Configuration (for local testing, use localhost)
DOMAIN_PUBLIC=localhost
DOMAIN_ADMIN=localhost
VALIDATION_DOMAIN=localhost
```

### 3. Build and Start Services

```bash
# Build the Docker images
docker compose build

# Start all services
docker compose up -d

# Check service status
docker compose ps
```

### 4. Initialize the Application

```bash
# Generate application key
docker compose exec php php artisan key:generate

# Run database migrations
docker compose exec php php artisan migrate

# (Optional) Seed the database
docker compose exec php php artisan db:seed
```

### 5. Access the Application

- Application: http://localhost:8000
- Public Certificate Validation: http://localhost:8000 (public domain)
- Admin Dashboard: http://localhost:8000 (admin domain)

> **Note**: For proper domain-based routing, you'll need to configure your hosts file or use NGINX Proxy Manager.

## Production Deployment

### 1. Server Requirements

- Ubuntu 20.04+ or similar Linux distribution
- Docker Engine installed
- Docker Compose V2 installed
- Sufficient resources (recommended: 2 CPU cores, 4GB RAM minimum)

### 2. Prepare Production Environment

On your production server:

```bash
# Create application directory
mkdir -p /var/www/gdgoc-certs
cd /var/www/gdgoc-certs

# Clone the repository
git clone https://github.com/KirolosMFahem/GDGoC-certs-v3.git .
git checkout main
```

### 3. Configure Production Environment

Create `.env` file with production settings:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_GENERATED_KEY
APP_URL=https://sudo.certs-admin.certs.gdg-oncampus.dev

# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=gdgoc_certs_prod
DB_USERNAME=gdgoc_prod_user
DB_PASSWORD=STRONG_PASSWORD_HERE

# Redis Configuration
REDIS_HOST=redis
REDIS_PORT=6379
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Domain Configuration
DOMAIN_PUBLIC=certs.gdg-oncampus.dev
DOMAIN_ADMIN=sudo.certs-admin.certs.gdg-oncampus.dev
VALIDATION_DOMAIN=certs.gdg-oncampus.dev

# OAuth/OIDC Configuration
OIDC_CLIENT_ID=your_client_id
OIDC_CLIENT_SECRET=your_client_secret
OIDC_REDIRECT_URI=https://sudo.certs-admin.certs.gdg-oncampus.dev/auth/callback
OIDC_AUTHORIZATION_ENDPOINT=https://your-idp.com/authorize
OIDC_TOKEN_ENDPOINT=https://your-idp.com/token
OIDC_USERINFO_ENDPOINT=https://your-idp.com/userinfo
```

### 4. Deploy with Docker Compose

```bash
# Build and start services
docker compose -f docker-compose.yml up -d --build

# Initialize application
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --force
docker compose exec php php artisan config:cache
docker compose exec php php artisan route:cache
docker compose exec php php artisan view:cache
docker compose exec php php artisan optimize
```

### 5. Verify Deployment

```bash
# Check all services are running
docker compose ps

# Check application logs
docker compose logs php
docker compose logs queue-worker
docker compose logs scheduler

# Test application
curl http://localhost:8000
```

## NGINX Proxy Manager Configuration

The application is designed to run behind NGINX Proxy Manager (NPM) for SSL termination and public-facing routing.

### Public Domain (certs.gdg-oncampus.dev)

1. In NPM, create a new Proxy Host
2. **Domain Names**: `certs.gdg-oncampus.dev`
3. **Scheme**: `http`
4. **Forward Hostname/IP**: `<server-ip>` or `gdgoc-nginx`
5. **Forward Port**: `8000`
6. Enable SSL with Let's Encrypt
7. Enable "Force SSL"

### Admin Domain (sudo.certs-admin.certs.gdg-oncampus.dev)

1. In NPM, create a new Proxy Host
2. **Domain Names**: `sudo.certs-admin.certs.gdg-oncampus.dev`
3. **Scheme**: `http`
4. **Forward Hostname/IP**: `<server-ip>` or `gdgoc-nginx`
5. **Forward Port**: `8000`
6. Enable SSL with Let's Encrypt
7. Enable "Force SSL"

### DNS Configuration

Ensure your DNS records point to your server:

```
certs.gdg-oncampus.dev                    A    <server-ip>
sudo.certs-admin.certs.gdg-oncampus.dev   A    <server-ip>
```

## CI/CD Setup

### GitHub Actions Secrets

Configure the following secrets in your GitHub repository (Settings > Secrets and variables > Actions):

#### Docker Hub Credentials
- `DOCKER_USERNAME`: Your Docker Hub username
- `DOCKER_PASSWORD`: Your Docker Hub password or access token

#### Production Server SSH
- `PRODUCTION_HOST`: Production server IP or hostname
- `PRODUCTION_USER`: SSH username
- `SSH_PRIVATE_KEY`: Private SSH key for authentication
- `SSH_PORT`: SSH port (optional, defaults to 22)
- `PRODUCTION_PATH`: Path to application directory (e.g., `/var/www/gdgoc-certs`)

### Deployment Workflow

The CI/CD pipeline (`.github/workflows/deploy.yml`) automatically:

1. Runs tests on every push to `main` branch
2. Builds and pushes Docker image to Docker Hub (if tests pass)
3. Connects to production server via SSH
4. Pulls the latest Docker image
5. Restarts services with `docker compose up -d`
6. Runs Laravel optimizations

### Manual Deployment

To deploy manually:

```bash
# On production server
cd /var/www/gdgoc-certs
git pull origin main
docker compose pull
docker compose up -d --remove-orphans
docker compose exec php php artisan migrate --force
docker compose exec php php artisan optimize
```

## Service Management

### Viewing Logs

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f php
docker compose logs -f queue-worker
docker compose logs -f scheduler
docker compose logs -f nginx
```

### Restarting Services

```bash
# All services
docker compose restart

# Specific service
docker compose restart php
docker compose restart queue-worker
docker compose restart scheduler
```

### Stopping Services

```bash
# Stop all services
docker compose down

# Stop and remove volumes (WARNING: This deletes all data!)
docker compose down -v
```

### Scaling Queue Workers

To handle more jobs, scale the queue worker:

```bash
docker compose up -d --scale queue-worker=3
```

## Database Management

### PostgreSQL (Default)

```bash
# Access PostgreSQL shell
docker compose exec postgres psql -U gdgoc_user -d gdgoc_certs

# Backup database
docker compose exec postgres pg_dump -U gdgoc_user gdgoc_certs > backup.sql

# Restore database
cat backup.sql | docker compose exec -T postgres psql -U gdgoc_user gdgoc_certs
```

### MySQL (Alternative)

To use MySQL instead of PostgreSQL, update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
```

Then:

```bash
# Access MySQL shell
docker compose exec mysql mysql -u gdgoc_user -p gdgoc_certs

# Backup database
docker compose exec mysql mysqldump -u gdgoc_user -p gdgoc_certs > backup.sql

# Restore database
cat backup.sql | docker compose exec -T mysql mysql -u gdgoc_user -p gdgoc_certs
```

## Troubleshooting

### Service Won't Start

Check logs for errors:
```bash
docker compose logs [service-name]
```

### Permission Issues

Fix storage permissions:
```bash
docker compose exec php chown -R appuser:appuser storage bootstrap/cache
docker compose exec php chmod -R 775 storage bootstrap/cache
```

### Queue Jobs Not Processing

Check queue worker status:
```bash
docker compose logs queue-worker
docker compose restart queue-worker
```

### Database Connection Issues

Verify database service is healthy:
```bash
docker compose ps postgres
docker compose logs postgres
```

### Clear Cache

```bash
docker compose exec php php artisan cache:clear
docker compose exec php php artisan config:clear
docker compose exec php php artisan route:clear
docker compose exec php php artisan view:clear
```

### Rebuild Everything

```bash
docker compose down
docker compose build --no-cache
docker compose up -d
```

## Security Considerations

1. **Environment Variables**: Never commit `.env` files to version control
2. **Database Passwords**: Use strong passwords in production
3. **SSH Keys**: Secure your SSH private keys
4. **Docker Registry**: Consider using a private registry for production images
5. **NGINX Proxy Manager**: Always enable SSL/TLS for production domains
6. **Firewall**: Configure firewall to only expose necessary ports (80, 443)
7. **Non-root User**: The application runs as a non-root user (`appuser`) in containers

## Maintenance

### Update Application

```bash
# Pull latest changes
git pull origin main

# Rebuild and restart
docker compose up -d --build

# Run migrations
docker compose exec php php artisan migrate --force

# Clear and rebuild cache
docker compose exec php php artisan optimize
```

### Monitor Resources

```bash
# Check resource usage
docker stats

# Check disk usage
docker system df
```

## Support

For issues or questions:
- Create an issue on GitHub
- Check the logs: `docker compose logs`
- Review Laravel logs: `storage/logs/laravel.log`

## License

This project is open source software licensed under the MIT license.
