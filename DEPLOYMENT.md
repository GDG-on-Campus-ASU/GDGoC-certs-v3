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
  - `php-pgsql` (for PostgreSQL)
  - `php-redis` (optional, for Redis cache/queue)
- **Composer**: 2.0+
- **Node.js**: 20.x LTS with npm
- **Database**: PostgreSQL 14+
- **Web Server**: Apache with mod_php (recommended)
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

#### 5. Install PostgreSQL

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

#### 8. Install Apache

```bash
sudo apt install -y apache2 libapache2-mod-php8.3
sudo a2enmod rewrite
sudo systemctl enable apache2
```

#### 9. Install Supervisor (for Queue Workers)

```bash
sudo apt install -y supervisor
sudo systemctl enable supervisor
```

### Application Installation

#### 1. Clone the Repository

```bash
cd /var/www
sudo git clone https://github.com/GDG-on-Campus-ASU/GDGoC-certs-v3.git
cd GDGoC-certs-v3
```

#### 2. Install PHP Dependencies

```bash
composer install --optimize-autoloader --no-dev
```

#### 3. Install Node.js Dependencies and Build Assets

```bash
npm ci
npm run build
```

#### 4. Configure Environment

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

#### 5. Generate Application Key

```bash
php artisan key:generate
```

#### 6. Set Directory Permissions

```bash
sudo chown -R www-data:www-data /var/www/GDGoC-certs-v3
sudo chmod -R 755 /var/www/GDGoC-certs-v3
sudo chmod -R 775 /var/www/GDGoC-certs-v3/storage
sudo chmod -R 775 /var/www/GDGoC-certs-v3/bootstrap/cache
```

#### 7. Run Database Migrations

```bash
php artisan migrate --force
```

#### 8. Seed the Database (Optional)

```bash
php artisan db:seed
```

#### 9. Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Web Server Configuration

#### Apache Configuration

Create a virtual host configuration:

```bash
sudo nano /etc/apache2/sites-available/gdgoc-certs.conf
```

Add the following:

```apache
<VirtualHost *:80>
    ServerName certs.your-domain.com
    ServerAlias admin.certs.your-domain.com
    DocumentRoot /var/www/GDGoC-certs-v3/public

    <Directory /var/www/GDGoC-certs-v3/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/gdgoc-certs-error.log
    CustomLog ${APACHE_LOG_DIR}/gdgoc-certs-access.log combined
</VirtualHost>
```

Enable the site and disable the default site:

```bash
sudo a2dissite 000-default
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
command=php /var/www/GDGoC-certs-v3/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/GDGoC-certs-v3/storage/logs/queue-worker.log
stopwaitsecs=3600
```

#### Scheduler Configuration (Cron)

Add the Laravel scheduler to crontab:

```bash
sudo crontab -e -u www-data
```

Add the following line:

```cron
* * * * * cd /var/www/GDGoC-certs-v3 && php artisan schedule:run >> /dev/null 2>&1
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
# Install Certbot for Apache
sudo apt install -y certbot python3-certbot-apache

# Obtain certificates
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
tail -f /var/www/GDGoC-certs-v3/storage/logs/laravel.log

# View queue worker logs
tail -f /var/www/GDGoC-certs-v3/storage/logs/queue-worker.log
```

### Updating the Application (Non-Docker)

```bash
cd /var/www/GDGoC-certs-v3

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
5. **PostgreSQL** (`postgres`) - Database
6. **Redis** (`redis`) - Cache and queue storage

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
# Clone the repository
cd /var/www
git clone https://github.com/GDG-on-Campus-ASU/GDGoC-certs-v3.git
cd GDGoC-certs-v3
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

### 4. External Storage Configuration (Optional)

To store certificates on external storage (AWS S3, Azure Blob Storage, or Google Drive), configure the following environment variables in your `.env` file.

#### AWS S3
```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key-id
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_USE_PATH_STYLE_ENDPOINT=false
```

#### Azure Blob Storage
```env
FILESYSTEM_DISK=azure
AZURE_STORAGE_NAME=your-storage-account-name
AZURE_STORAGE_KEY=your-storage-account-key
AZURE_STORAGE_CONTAINER=your-container-name
AZURE_STORAGE_URL=https://your-storage-account-name.blob.core.windows.net
```

#### Google Drive
```env
FILESYSTEM_DISK=google
GOOGLE_DRIVE_CLIENT_ID=your-client-id
GOOGLE_DRIVE_CLIENT_SECRET=your-client-secret
GOOGLE_DRIVE_REFRESH_TOKEN=your-refresh-token
GOOGLE_DRIVE_FOLDER=your-folder-id
```

### 5. Obtaining Storage Credentials

Here is how to obtain the necessary configuration values for each storage provider.

#### AWS S3

1.  **Create an IAM User**:
    *   Log in to the [AWS Console](https://console.aws.amazon.com/).
    *   Go to **IAM** > **Users** > **Create user**.
    *   Name the user (e.g., `gdgoc-certs-user`).
    *   Attach policies directly: Search for and select `AmazonS3FullAccess` (or create a custom policy for specific bucket access).
    *   Create the user.
2.  **Generate Access Keys**:
    *   Click on the newly created user.
    *   Go to the **Security credentials** tab.
    *   Scroll to **Access keys** and click **Create access key**.
    *   Select **Application running outside AWS**.
    *   Copy the **Access key ID** (`AWS_ACCESS_KEY_ID`) and **Secret access key** (`AWS_SECRET_ACCESS_KEY`).
3.  **Create a Bucket**:
    *   Go to **S3** > **Create bucket**.
    *   Enter a **Bucket name** (`AWS_BUCKET`).
    *   Select an **AWS Region** (`AWS_DEFAULT_REGION`).
    *   Keep other settings as default or adjust as needed.

#### Azure Blob Storage

1.  **Create a Storage Account**:
    *   Log in to the [Azure Portal](https://portal.azure.com/).
    *   Search for **Storage accounts** and click **Create**.
    *   Select your Subscription and Resource Group.
    *   Enter a **Storage account name** (`AZURE_STORAGE_NAME`).
    *   Select Region and Performance/Redundancy options.
    *   Review and Create.
2.  **Get Access Keys**:
    *   Go to your new Storage Account resource.
    *   In the left menu, under **Security + networking**, click **Access keys**.
    *   Copy **Key 1** (or Key 2) -> This is your `AZURE_STORAGE_KEY`.
3.  **Create a Container**:
    *   In the left menu, under **Data storage**, click **Containers**.
    *   Click **+ Container**.
    *   Enter a **Name** (`AZURE_STORAGE_CONTAINER`).
    *   Set Public access level (usually "Private" for internal use, or "Blob" if files need to be public).

#### Google Drive

1.  **Create a Project & Enable API**:
    *   Go to the [Google Cloud Console](https://console.cloud.google.com/).
    *   Create a new project.
    *   Go to **APIs & Services** > **Library**.
    *   Search for **Google Drive API** and enable it.
2.  **Configure OAuth Consent Screen**:
    *   Go to **APIs & Services** > **OAuth consent screen**.
    *   Select **External** (unless you are in a Google Workspace organization).
    *   Fill in the App Name and User Support Email.
    *   Add `../auth/drive.file` to **Scopes**.
    *   Add your email to **Test users**.
3.  **Create Credentials**:
    *   Go to **APIs & Services** > **Credentials**.
    *   Click **Create Credentials** > **OAuth client ID**.
    *   Application type: **Web application**.
    *   Name: `GDGoC Certs Drive`.
    *   **Authorized redirect URIs**: Add `https://developers.google.com/oauthplayground` (we will use this to generate the refresh token).
    *   Click **Create**.
    *   Copy the **Client ID** (`GOOGLE_DRIVE_CLIENT_ID`) and **Client Secret** (`GOOGLE_DRIVE_CLIENT_SECRET`).
4.  **Generate Refresh Token**:
    *   Go to the [OAuth 2.0 Playground](https://developers.google.com/oauthplayground).
    *   Click the **Settings** (gear icon) in the top right.
    *   Check **Use your own OAuth credentials**.
    *   Enter your **Client ID** and **Client Secret**.
    *   In the left panel "Step 1", scroll to **Drive API v3** and select `https://www.googleapis.com/auth/drive.file`.
    *   Click **Authorize APIs**.
    *   Login with your Google account and allow access.
    *   In "Step 2", click **Exchange authorization code for tokens**.
    *   Copy the **Refresh Token** (`GOOGLE_DRIVE_REFRESH_TOKEN`).
5.  **Get Folder ID**:
    *   Go to Google Drive and open (or create) the folder you want to use.
    *   Look at the URL: `https://drive.google.com/drive/folders/YOUR_FOLDER_ID_IS_HERE`.
    *   Copy that ID string (`GOOGLE_DRIVE_FOLDER`).
    *   **Important**: Share this folder with the email address associated with the project (or ensure the account generating the token has edit access).

### 6. Deploy with Docker Compose

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

### 7. Verify Deployment

```bash
# Check all services are running
docker compose ps

# Check application logs
docker compose logs php
docker compose logs queue-worker
docker compose logs scheduler

# Test application
curl http://localhost:8000

# Test Admin Dashboard (requires login, but check if reachable)
# The dashboard is at /admin/dashboard, but redirects to login if not authenticated.
# You can verify the login page is accessible:
curl -I http://localhost:8000/login

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
- `PRODUCTION_PATH`: Path to application directory (e.g., `/var/www/GDGoC-certs-v3`)

### Deployment Workflow

The CI/CD pipeline consists of two main workflows:

#### Docker Deployment Test (`.github/workflows/docker-test.yml`)

Runs automatically on **every Pull Request** to test Docker deployment:

1. **Environment Setup**:
   - Fixes workspace ownership
   - Configures kernel for Redis (`vm.overcommit_memory=1`)
   - Pre-creates storage and cache directories on the host with 777 permissions to prevent mounting issues
2. **Docker Build Test**: Verifies the Dockerfile builds successfully
3. **Docker Compose Test**: 
   - Starts all services (PostgreSQL, Redis, PHP, Nginx)
   - **Health Checks**:
     - Waits for Docker container healthchecks
     - Explicitly waits for PostgreSQL to be ready (`pg_isready`)
     - Explicitly waits for Redis to be ready (`redis-cli ping`)
   - **Application Setup**:
     - Installs development dependencies (`composer install`)
     - Runs database migrations
   - **Link Accessibility Test**: Verifies that critical routes (/, /login, /register, /admin/dashboard) are accessible and return expected status codes (200 or 302). This ensures no 500 errors are present on key pages.
4. **Security Scan**: Scans Docker image for vulnerabilities using Trivy

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
cd /var/www/GDGoC-certs-v3
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
docker compose ps postgres
docker compose logs postgres
```

### Admin Dashboard 500 Error

If you encounter a 500 error when accessing `/admin/dashboard`:

1. **Check Database Seeder**: Ensure the `DatabaseSeeder` has run and the superadmin user exists.
   ```bash
   docker compose exec php php artisan db:seed
   ```
2. **Verify Superuser Role**: The user must have the `superadmin` role.
   ```bash
   docker compose exec php php artisan tinker
   >>> \App\Models\User::where('email', 'admin@example.com')->first()->role
   ```
   It should output `"superadmin"`.
3. **Check Logs**:
   ```bash
   docker compose logs php
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
