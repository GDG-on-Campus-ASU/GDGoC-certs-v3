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
- **Database**: PostgreSQL 16
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
   git clone https://github.com/KirolosMFahem/GDGoC-certs-v3.git
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

1. **Start Docker containers**
   ```bash
   docker-compose up -d
   ```

2. **Run migrations**
   ```bash
   docker-compose exec php php artisan migrate
   ```

3. **Seed the database** (creates default superadmin)
   ```bash
   docker-compose exec php php artisan db:seed
   ```

4. **Access the application**
   - URL: http://localhost:8000
   - Default admin credentials:
     - Email: admin@example.com
     - Password: password
   - **⚠️ Important**: [Change the default credentials](docs/CHANGING_SUPERADMIN_CREDENTIALS.md) after first login!

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

GitHub Actions workflow is configured to:
- Run tests on push/PR to main and develop branches
- Set up PostgreSQL for testing
- Build assets
- Run migrations

## Deployment

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

The GDGoC Certificate Generation Platform is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).

