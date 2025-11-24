# GDGoC Certificates - Deployment Guide

This document provides instructions for deploying the GDGoC Certificate Generation Platform. You can choose between Docker-based deployment (recommended) or traditional deployment without Docker.

## Table of Contents

- [Deployment Without Docker](#deployment-without-docker)
  - [Prerequisites (Non-Docker)](#prerequisites-non-docker)
  - [Server Setup](#server-setup)
  - [Application Installation](#application-installation)
  - [Web Server Configuration](#web-server-configuration)
  - [Process Management](#process-management)
  - [SSL/TLS Configuration](#ssltls-configuration)
- [Docker Deployment](#docker-deployment)
  - [Prerequisites (Docker)](#prerequisites-docker)
  - [Architecture Overview](#architecture-overview)
  - [Local Development Setup](#local-development-setup)
  - [Production Deployment](#production-deployment)
- [NGINX Proxy Manager Configuration](#nginx-proxy-manager-configuration)
- [CI/CD Setup](#cicd-setup)
- [Troubleshooting](#troubleshooting)

---

## Deployment Without Docker

This section covers deploying the application on a traditional server without containerization.

### Prerequisites (Non-Docker)

- **Operating System**: Ubuntu 22.04 LTS (or similar Linux distribution)
- **PHP**: 8.2 or higher with the following extensions:
  - `php-cli`, `php-fpm`, `php-mbstring`, `php-xml`, `php-curl`
  - `php-zip`, `php-bcmath`, `php-gd`, `php-intl`
  - `php-pgsql` (for PostgreSQL) or `php-mysql` (for MySQL)
  - `php-redis` (optional, for Redis cache/queue)
- **Composer**: 2.0+
- **Node.js**: 20.x LTS with npm
- **Database**: PostgreSQL 14+ (recommended) or MySQL 8.0+
- **Web Server**: Nginx (recommended) or Apache
- **Redis**: 6.0+ (optional, recommended for production)
- **wkhtmltopdf**: Required for PDF certificate generation
- **Git**: For cloning the repository
- **Supervisor**: For managing queue workers and scheduler

### Server Setup

#### 1. Update System Packages

```bash
sudo apt update && sudo apt upgrade -y
```

#### 2. Install PHP and Required Extensions

```bash
# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.3 and extensions (PHP 8.2+ is supported, but 8.3 is recommended)
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mbstring php8.3-xml \
    php8.3-curl php8.3-zip php8.3-bcmath php8.3-gd php8.3-intl \
    php8.3-pgsql php8.3-redis
```

#### 3. Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### 4. Install Node.js

```bash
# Using NodeSource repository
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

#### 5. Install PostgreSQL (or MySQL)

**PostgreSQL (Recommended):**

```bash
sudo apt install -y postgresql postgresql-contrib

# Create database and user
# ⚠️ IMPORTANT: Replace 'your_secure_password' with a strong, unique password!
sudo -u postgres psql <<EOF
CREATE USER gdgoc_user WITH PASSWORD 'your_secure_password';
CREATE DATABASE gdgoc_certs OWNER gdgoc_user;
GRANT ALL PRIVILEGES ON DATABASE gdgoc_certs TO gdgoc_user;
EOF
```

**MySQL (Alternative):**

```bash
sudo apt install -y mysql-server

# Secure installation
sudo mysql_secure_installation

# Create database and user
# ⚠️ IMPORTANT: Replace 'your_secure_password' with a strong, unique password!
sudo mysql <<EOF
CREATE DATABASE gdgoc_certs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gdgoc_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON gdgoc_certs.* TO 'gdgoc_user'@'localhost';
FLUSH PRIVILEGES;
EOF
```

#### 6. Install Redis (Recommended for Production)

```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

#### 7. Install wkhtmltopdf (for PDF Generation)

```bash
sudo apt install -y wkhtmltopdf

# Verify installation
wkhtmltopdf --version
```

> **Note**: The Ubuntu package version of wkhtmltopdf may have limited functionality (e.g., missing Qt patches for headers/footers). For advanced PDF features, consider downloading the patched version from the [official wkhtmltopdf releases](https://wkhtmltopdf.org/downloads.html).

#### 8. Install Nginx

```bash
sudo apt install -y nginx
sudo systemctl enable nginx
```

#### 9. Install Supervisor (for Queue Workers)

```bash
sudo apt install -y supervisor
sudo systemctl enable supervisor
```

### Application Installation

#### 1. Create Application Directory

```bash
sudo mkdir -p /var/www/gdgoc-certs
sudo chown $USER:$USER /var/www/gdgoc-certs
```

#### 2. Clone the Repository

```bash
cd /var/www/gdgoc-certs
git clone https://github.com/GDG-on-Campus-ASU/GDGoC-certs-v3.git .
```

#### 3. Install PHP Dependencies

```bash
composer install --optimize-autoloader --no-dev
```

#### 4. Install Node.js Dependencies and Build Assets

```bash
npm ci
npm run build
```

#### 5. Configure Environment

```bash
cp .env.example .env
```

Edit `.env` with your production settings:

```env
APP_NAME="GDGoC Certs"
APP_ENV=production
APP_DEBUG=false
APP_KEY=
APP_URL=https://certs.your-domain.com

# Database Configuration (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gdgoc_certs
DB_USERNAME=gdgoc_user
DB_PASSWORD=your_secure_password

# Redis Configuration (recommended for production)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Domain Configuration
DOMAIN_PUBLIC=certs.your-domain.com
DOMAIN_ADMIN=admin.certs.your-domain.com
VALIDATION_DOMAIN=certs.your-domain.com

# PDF Generation (wkhtmltopdf binary path)
# Default path on Ubuntu: /usr/bin/wkhtmltopdf
# SNAPPY_PDF_BINARY=/usr/bin/wkhtmltopdf
```

#### 6. Generate Application Key

```bash
php artisan key:generate
```

#### 7. Set Directory Permissions

```bash
sudo chown -R www-data:www-data /var/www/gdgoc-certs
sudo chmod -R 755 /var/www/gdgoc-certs
sudo chmod -R 775 /var/www/gdgoc-certs/storage
sudo chmod -R 775 /var/www/gdgoc-certs/bootstrap/cache
```

#### 8. Run Database Migrations

```bash
php artisan migrate --force
```

#### 9. Seed the Database (Optional)

```bash
php artisan db:seed
```

#### 10. Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Web Server Configuration

#### Nginx Configuration

Create a new Nginx site configuration:

```bash
sudo nano /etc/nginx/sites-available/gdgoc-certs
```

Add the following configuration:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name certs.your-domain.com admin.certs.your-domain.com;
    root /var/www/gdgoc-certs/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site and restart Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/gdgoc-certs /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### Apache Configuration (Alternative)

If using Apache instead of Nginx:

```bash
sudo apt install -y apache2 libapache2-mod-php8.3
sudo a2enmod rewrite
```

Create a virtual host configuration:

```bash
sudo nano /etc/apache2/sites-available/gdgoc-certs.conf
```

Add the following:

```apache
<VirtualHost *:80>
    ServerName certs.your-domain.com
    ServerAlias admin.certs.your-domain.com
    DocumentRoot /var/www/gdgoc-certs/public

    <Directory /var/www/gdgoc-certs/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/gdgoc-certs-error.log
    CustomLog ${APACHE_LOG_DIR}/gdgoc-certs-access.log combined
</VirtualHost>
```

Enable the site:

```bash
sudo a2ensite gdgoc-certs
sudo systemctl reload apache2
```

### Process Management

#### Queue Worker Configuration (Supervisor)

Create a Supervisor configuration for the queue worker:

```bash
sudo nano /etc/supervisor/conf.d/gdgoc-queue-worker.conf
```

Add the following:

```ini
[program:gdgoc-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/gdgoc-certs/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/gdgoc-certs/storage/logs/queue-worker.log
stopwaitsecs=3600
```

#### Scheduler Configuration (Cron)

Add the Laravel scheduler to crontab:

```bash
sudo crontab -e -u www-data
```

Add the following line:

```cron
* * * * * cd /var/www/gdgoc-certs && php artisan schedule:run >> /dev/null 2>&1
```

#### Start Supervisor Processes

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start gdgoc-queue-worker:*
```

### SSL/TLS Configuration

Use Certbot to obtain and configure SSL certificates:

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain certificates (for Nginx)
sudo certbot --nginx -d certs.your-domain.com -d admin.certs.your-domain.com

# For Apache
sudo certbot --apache -d certs.your-domain.com -d admin.certs.your-domain.com
```

Certbot will automatically configure SSL and set up auto-renewal.

### Maintenance Commands (Non-Docker)

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan optimize

# Run migrations
php artisan migrate --force

# Restart queue workers after code changes
sudo supervisorctl restart gdgoc-queue-worker:*

# View Laravel logs
tail -f /var/www/gdgoc-certs/storage/logs/laravel.log

# View queue worker logs
tail -f /var/www/gdgoc-certs/storage/logs/queue-worker.log
```

### Updating the Application (Non-Docker)

```bash
cd /var/www/gdgoc-certs

# Pull latest changes
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev
npm ci
npm run build

# Run migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan optimize

# Restart queue workers
sudo supervisorctl restart gdgoc-queue-worker:*
```

---

## Docker Deployment

This section covers deploying the application using Docker and Docker Compose (recommended for most use cases).

### Prerequisites (Docker)

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
git clone https://github.com/GDG-on-Campus-ASU/GDGoC-certs-v3.git
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
git clone https://github.com/GDG-on-Campus-ASU/GDGoC-certs-v3.git .
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

The CI/CD pipeline consists of two main workflows:

#### Docker Deployment Test (`.github/workflows/docker-test.yml`)

Runs automatically on **every Pull Request** to test Docker deployment:

1. **Docker Build Test**: Verifies the Dockerfile builds successfully
2. **Docker Compose Test**: 
   - Starts all services (PostgreSQL, Redis, PHP, Nginx)
   - Waits for services to be healthy
   - Runs database migrations
   - Tests application health endpoint
3. **Security Scan**: Scans Docker image for vulnerabilities using Trivy

This ensures that Docker deployments work correctly before merging to main.

#### Production Deployment (`.github/workflows/deploy.yml`)

Runs automatically on every push to `main` branch:

1. Runs tests on every push to `main` branch
2. Builds and pushes Docker image to Docker Hub (if tests pass)
3. Connects to production server via SSH
4. Pulls the latest Docker image
5. Restarts services with `docker compose up -d`
6. Runs Laravel optimizations

### Testing Docker Changes Locally

Before pushing Docker-related changes, you can test them locally:

```bash
# Test Docker build
docker build -t gdgoc-certs:test .

# Test Docker Compose deployment
docker compose up -d
docker compose ps
docker compose logs -f

# Test application health
curl http://localhost:8000

# Clean up
docker compose down -v
```

The Docker test workflow will automatically run these checks on every PR that modifies:
- `Dockerfile`
- `docker-compose.yml`
- Files in `docker/` directory
- PHP files
- Composer or NPM dependencies

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

Storage and bootstrap/cache permissions are automatically managed by the Docker entrypoint script. On each container start, the script ensures these directories exist with proper permissions (777 for maximum CI compatibility).

If Laravel migrations fail with "Permission denied" or "directory must be present and writable" errors:

**For local development:**
```bash
docker compose restart php
```

**For CI/CD environments:**
The docker-test.yml workflow sets permissions on the host BEFORE mounting directories to avoid "Operation not permitted" errors:
```yaml
- name: Set Laravel directory permissions
  run: |
    # Create directories on host with proper permissions before they're mounted
    mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
    chmod -R 777 storage bootstrap/cache
```

**Why on the host?** The container runs as non-root user `appuser`, so it cannot change permissions of host-mounted directories. Setting permissions on the host before mounting prevents "Operation not permitted" errors.

If you need to manually fix permissions in local development:
```bash
# On host (recommended for CI)
chmod -R 777 storage bootstrap/cache

# Or inside container (works for non-mounted dirs only)
docker compose exec php chmod -R 777 storage bootstrap/cache
```

**Changes made:**
- Removed redundant `./storage:/var/www/html/storage` mounts that caused permission conflicts
- Added automatic permission fixes (777) in the entrypoint script for CI compatibility
- Added explicit permission-fixing step in CI workflows before migrations
- Set explicit permissions in the Dockerfile for these directories

### Vendor Directory Issues

If you encounter errors about `/var/www/html/vendor` not being accessible or unable to be created:

```bash
# The vendor directory uses a named Docker volume to prevent permission conflicts
# If you need to reset it:
docker compose down -v  # WARNING: This removes all volumes including database data
docker compose build --no-cache
docker compose up -d
```

The application uses Docker named volumes for `vendor` and `node_modules` to:
- Preserve dependencies installed during the Docker build
- Avoid permission conflicts between the host and container users
- Enable the non-root `appuser` to manage PHP and Node.js dependencies

### Nginx Startup Issues

If nginx fails to start with "host not found in upstream" errors:

```bash
# Check if PHP service is healthy
docker compose ps php

# Wait for all services to become healthy
docker compose up -d --wait

# View nginx logs
docker compose logs nginx

# Restart services in correct order
docker compose restart php nginx
```

The nginx service now waits for PHP-FPM to be healthy before starting. This prevents DNS resolution errors when nginx tries to connect to the PHP upstream server.

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

The GDGoC Certificate Generation Platform is open-source software licensed under the [AGPL license](https://opensource.org/licenses/AGPL).
