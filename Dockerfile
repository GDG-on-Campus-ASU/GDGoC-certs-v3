# Multi-stage build for optimized production image
# Stage 1: Build dependencies
FROM php:8.3-fpm-alpine AS builder

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    mysql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    nodejs \
    npm

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
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

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
RUN npm ci --only=production

# Copy application files
COPY . .

# Build assets
RUN npm run build

# Stage 2: Production image
FROM php:8.3-fpm-alpine

# Install runtime dependencies only
RUN apk add --no-cache \
    postgresql-dev \
    mysql-dev \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip \
    curl

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
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Set working directory
WORKDIR /var/www/html

# Create non-root user for running the application
RUN addgroup -g 1000 appuser && \
    adduser -u 1000 -G appuser -s /bin/sh -D appuser

# Copy application from builder
COPY --from=builder --chown=appuser:appuser /var/www/html /var/www/html

# Set permissions
RUN chown -R appuser:appuser /var/www/html/storage /var/www/html/bootstrap/cache

# Switch to non-root user
USER appuser

# Expose port
EXPOSE 9000

CMD ["php-fpm"]
