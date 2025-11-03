# Multi-stage build for optimized production image
# Stage 1: Build dependencies
FROM php:8.3-fpm AS builder

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    default-libmysqlclient-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    git \
    curl \
    nodejs \
    npm \
    zlib1g-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions including Redis, BCMath, PDO MySQL
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    pdo_mysql \
    gd \
    zip \
    opcache \
    bcmath

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy dependency files first for better caching
COPY composer.json composer.lock ./
COPY package.json package-lock.json ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Install Node dependencies
RUN npm ci

# Copy application files
COPY . .

# Build assets
RUN npm run build

# Stage 2: Production image
FROM php:8.3-fpm

# Install runtime dependencies only
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq5 \
    libpq-dev \
    default-mysql-client \
    default-libmysqlclient-dev \
    libpng16-16 \
    libpng-dev \
    libjpeg62-turbo \
    libjpeg-dev \
    libfreetype6 \
    libfreetype6-dev \
    libzip4 \
    libzip-dev \
    libicu72 \
    libonig5 \
    curl \
    zlib1g \
    zlib1g-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    pdo_mysql \
    gd \
    zip \
    opcache \
    bcmath

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Set working directory
WORKDIR /var/www/html

# Create non-root user for running the application
RUN groupadd -g 1000 appuser && \
    useradd -u 1000 -g appuser -s /bin/bash -m appuser

# Copy application from builder
COPY --from=builder --chown=appuser:appuser /var/www/html /var/www/html

# Set permissions
RUN chown -R appuser:appuser /var/www/html/storage /var/www/html/bootstrap/cache

# Switch to non-root user
USER appuser

# Expose port
EXPOSE 9000

CMD ["php-fpm"]
