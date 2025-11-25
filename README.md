# GDG on Campus (GDGoC) Certificate Generation Platform

A secure, multi-tenant certificate generation platform built with Laravel 11, PostgreSQL, and Docker.

## Features

- **Laravel 11** with Blade templating and Tailwind CSS
- **PostgreSQL** database
- **Docker** containerized development environment
- **Laravel Breeze** authentication with email verification
- **OAuth/OIDC** scaffolding for future integration
- **Gravatar** profile images
- **Multi-tenant** architecture with leader and superadmin roles

## Technology Stack

- **Backend**: Laravel 11
- **Frontend**: Blade + Tailwind CSS
- **Database**: PostgreSQL 18
- **Containerization**: Docker + Docker Compose
- **Web Server**: Nginx
- **PHP**: 8.3

## Quick Start

### Prerequisites

- Docker and Docker Compose
- PHP 8.3+ (for local development without Docker)
- Composer
- Node.js 20+

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/GDG-on-Campus-ASU/GDGoC-certs-v3.git
   cd GDGoC-certs-v3
   ```

2. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

3. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

4. **Generate application key**
   ```bash
   php artisan key:generate
   ```

5. **Build assets**
   ```bash
   npm run build
   ```

### Docker Setup

> **📘 For complete deployment instructions, see [DEPLOYMENT.md](DEPLOYMENT.md)**

This project includes a complete Docker setup with:
- Multi-stage Dockerfile for optimized production images
- Apache web server with mod_php (single container for web and PHP)
- Queue worker for background jobs
- Scheduler for Laravel scheduled tasks
- Redis for caching and queues
- PostgreSQL (default) and MySQL (alternative) databases

1. **Start Docker containers**
   ```bash
   docker compose up -d
   ```

2. **Initialize the application**
   ```bash
   docker compose exec php php artisan key:generate
   docker compose exec php php artisan migrate
   docker compose exec php php artisan db:seed
   ```

3. **Access the application**
   - URL: http://localhost:8000
   - Default admin credentials:
     - Email: admin@example.com
     - Password: password
   - **⚠️ Important**: [Change the default credentials](docs/CHANGING_SUPERADMIN_CREDENTIALS.md) after first login!

4. **View logs**
   ```bash
   # All services
   docker compose logs -f
   
   # Specific service
   docker compose logs -f php
   docker compose logs -f queue-worker
   ```

5. **Stop containers**
   ```bash
   docker compose down
   ```

### Local Development (without Docker)

1. **Set up PostgreSQL** (or use SQLite for development)
   - Update `.env` with your database credentials

2. **Run migrations**
   ```bash
   php artisan migrate
   ```

3. **Seed the database**
   ```bash
   php artisan db:seed
   ```

4. **Start the development server**
   ```bash
   php artisan serve
   ```

5. **Build and watch assets** (in a separate terminal)
   ```bash
   npm run dev
   ```

## User Roles

- **Superadmin**: Full system access (created via seeder)
- **Leader**: Organization leaders (default role for new users)

## Default Superadmin Account

- Email: admin@example.com
- Password: password
- Role: superadmin
- Status: active

**⚠️ Important**: [Change these credentials immediately](docs/CHANGING_SUPERADMIN_CREDENTIALS.md) after first login, especially in production!

## Database Schema

### Users Table

- `id`: Primary key
- `name`: User's full name
- `email`: Unique email address
- `password`: Hashed password (nullable for OAuth users)
- `org_name`: Organization name (nullable, set on first login)
- `role`: Enum ('leader', 'superadmin')
- `status`: Enum ('active', 'suspended', 'terminated')
- `termination_reason`: Text field for termination notes
- `oauth_provider`: OAuth provider name (nullable)
- `oauth_id`: OAuth provider user ID (nullable)
- `email_verified_at`: Timestamp
- `remember_token`: Laravel remember token
- `created_at`, `updated_at`: Timestamps

## Domain-Based Routing

The application uses domain-based routing for multi-tenant functionality:

### Public Domain (Certificate Validation)
- **Domain**: `certs.gdg-oncampus.dev`
- **Purpose**: Public certificate validation and downloads
- **Routes**:
  - `/` - Certificate validation form
  - `/c/{unique_id}` - View certificate details
  - `/c/{unique_id}/download` - Download certificate PDF

### Admin Domain (Dashboard & Management)
- **Domain**: `sudo.certs-admin.certs.gdg-oncampus.dev`
- **Purpose**: Admin dashboard, leader portal, and management
- **Routes**:
  - `/dashboard` - Leader dashboard
  - `/admin` - Superadmin panel
  - `/profile` - User profile management
  - `/auth/*` - OAuth/OIDC authentication

### Configuration

Set the domains in your `.env` file:

```env
DOMAIN_PUBLIC=certs.gdg-oncampus.dev
DOMAIN_ADMIN=sudo.certs-admin.certs.gdg-oncampus.dev
VALIDATION_DOMAIN=certs.gdg-oncampus.dev
```

For local development without domain setup, use `localhost` for all domains.

See [DEPLOYMENT.md](DEPLOYMENT.md) for NGINX Proxy Manager configuration.

## OAuth/OIDC Configuration

The application has scaffolding for OAuth/OIDC authentication. Configuration will be added in a later step.

Environment variables:
```
OIDC_CLIENT_ID=
OIDC_CLIENT_SECRET=
OIDC_REDIRECT_URI=${APP_URL}/auth/callback
OIDC_AUTHORIZATION_ENDPOINT=
OIDC_TOKEN_ENDPOINT=
OIDC_USERINFO_ENDPOINT=
```

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run tests with coverage
php artisan test --coverage
```

## CI/CD

GitHub Actions workflows are configured for:

### Testing (`.github/workflows/tests.yml`)
- Runs tests on push/PR to main and develop branches
- Sets up PHP 8.2, 8.3, and 8.4
- Builds assets with Node.js 20
- Runs migrations and tests

### Deployment (`.github/workflows/deploy.yml`)
- Triggers on push to `main` branch
- Runs full test suite
- Builds and pushes Docker image to Docker Hub
- Deploys to production server via SSH
- Runs optimizations and migrations

**Required GitHub Secrets:**
- `DOCKER_USERNAME` - Docker Hub username
- `DOCKER_PASSWORD` - Docker Hub password/token
- `PRODUCTION_HOST` - Production server IP/hostname
- `PRODUCTION_USER` - SSH username
- `SSH_PRIVATE_KEY` - SSH private key
- `PRODUCTION_PATH` - Application path on server

See [DEPLOYMENT.md](DEPLOYMENT.md) for complete CI/CD setup instructions.

## Deployment

### Production Deployment with Docker

For production deployment with Docker, see the comprehensive guide: **[DEPLOYMENT.md](DEPLOYMENT.md)**

The deployment guide covers:
- **Docker Setup**: Multi-stage builds, services architecture
- **NGINX Proxy Manager**: SSL/TLS configuration for domain-based routing
- **CI/CD Pipeline**: Automated testing, building, and deployment
- **Database Management**: Backups, migrations, PostgreSQL/MySQL
- **Security**: Best practices for production environments
- **Troubleshooting**: Common issues and solutions

#### Quick Production Setup

```bash
# On production server
git clone https://github.com/GDG-on-Campus-ASU/GDGoC-certs-v3.git
cd GDGoC-certs-v3
cp .env.example .env
# Edit .env with production settings
docker compose up -d --build
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --force
docker compose exec php php artisan optimize
```

### Legacy Deployment Branch

A separate `deployment` branch exists with a specialized `.gitignore` that only includes:
- docker-compose.yml
- .env.example
- Dockerfile
- docker/ directory
- README.md
- LICENSE

This branch is managed manually and contains only deployment-specific files.

## Security

- Email verification required for new accounts
- Passwords hashed with bcrypt
- CSRF protection enabled
- Session security configured
- Environment variables for sensitive data

## License

The GDGoC Certificate Generation Platform is open-source software licensed under the [AGPL license](https://opensource.org/licenses/AGPL).

