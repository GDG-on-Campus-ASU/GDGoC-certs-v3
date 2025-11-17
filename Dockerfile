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

# Install Redis extension with retry logic and fallback
RUN set -eux; \
    { \
        pecl channel-update pecl.php.net && \
        pecl install redis && \
        docker-php-ext-enable redis; \
    } || { \
        echo "Warning: Failed to install Redis extension via PECL. Trying alternative method..." >&2; \
        cd /tmp && \
        curl -L https://github.com/phpredis/phpredis/archive/6.0.2.tar.gz -o phpredis.tar.gz && \
        tar -xzf phpredis.tar.gz && \
        cd phpredis-6.0.2 && \
        phpize && \
        ./configure && \
        make && \
        make install && \
        docker-php-ext-enable redis && \
        cd / && \
        rm -rf /tmp/phpredis* || \
        echo "Warning: Redis extension installation failed completely. Redis features will not be available." >&2; \
    }

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
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    curl \
    zlib1g-dev \
    gosu \
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

# Install Redis extension with retry logic and fallback
RUN set -eux; \
    { \
        pecl channel-update pecl.php.net && \
        pecl install redis && \
        docker-php-ext-enable redis; \
    } || { \
        echo "Warning: Failed to install Redis extension via PECL. Trying alternative method..." >&2; \
        cd /tmp && \
        curl -L https://github.com/phpredis/phpredis/archive/6.0.2.tar.gz -o phpredis.tar.gz && \
        tar -xzf phpredis.tar.gz && \
        cd phpredis-6.0.2 && \
        phpize && \
        ./configure && \
        make && \
        make install && \
        docker-php-ext-enable redis && \
        cd / && \
        rm -rf /tmp/phpredis* || \
        echo "Warning: Redis extension installation failed completely. Redis features will not be available." >&2; \
    }

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Set working directory
WORKDIR /var/www/html

# Create non-root user for running the application
RUN groupadd -g 1000 appuser && \
    useradd -u 1000 -g appuser -s /bin/bash -m appuser

# Copy application from builder
COPY --from=builder --chown=appuser:appuser /var/www/html /var/www/html

# Copy composer for runtime dependency installation
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy and configure entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Ensure vendor directory exists and set permissions for writable directories
RUN mkdir -p /var/www/html/vendor /var/www/html/storage /var/www/html/bootstrap/cache && \
    chown -R appuser:appuser /var/www/html && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/vendor

# Note: We don't switch to non-root user yet to allow entrypoint script to fix permissions
# The entrypoint script will handle permission fixes and then exec as appuser

# Expose port
EXPOSE 9000

# Set entrypoint and default command
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
