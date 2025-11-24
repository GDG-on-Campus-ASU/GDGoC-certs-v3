# Multi-stage build for optimized production image

# Stage 1: Build frontend assets
FROM node:20-bookworm AS node_builder
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: Build PHP dependencies
FROM composer:2 AS composer_builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --ignore-platform-reqs

# Stage 3: Production image
FROM php:8.3-apache-bookworm

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    gnupg2 \
    lsb-release \
    ca-certificates \
    && curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor -o /etc/apt/trusted.gpg.d/postgresql.gpg \
    && echo "deb http://apt.postgresql.org/pub/repos/apt/ $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
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

# Install PHP extensions including Redis, BCMath, PDO MySQL
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
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

# Enable Apache modules
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Create non-root user for running the application
RUN groupadd -g 1000 appuser && \
    useradd -u 1000 -g appuser -s /bin/bash -m appuser

# Copy application files
COPY . .

# Copy built assets from node_builder
COPY --from=node_builder /app/public/build /var/www/html/public/build

# Copy composer dependencies from composer_builder
COPY --from=composer_builder /app/vendor /var/www/html/vendor

# Copy composer binary for runtime usage
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy and configure entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

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

# Ensure vendor directory exists and set permissions for writable directories
# We do this AFTER copying vendor to ensure permissions are correct
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/vendor

# Expose port
EXPOSE 80

# Set entrypoint and default command
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
