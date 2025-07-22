# syntax=docker/dockerfile:1

# =============================================================================
# Build stage for Node.js assets
# =============================================================================
FROM node:20-alpine AS node-builder

WORKDIR /app

# Copy package files first for better caching
COPY package*.json ./

# Install dependencies with cache mount for faster rebuilds
RUN --mount=type=cache,target=/root/.npm \
    npm ci --prefer-offline --no-audit --no-fund

# Copy only necessary source files for building
COPY resources/ resources/
COPY public/ public/
COPY vite.config.ts ./
COPY tsconfig.json ./

# Build assets
RUN npm run build

# =============================================================================
# PHP production stage
# =============================================================================
FROM php:8.4-fpm-alpine AS production

# Install system dependencies in single layer with cache mount
RUN --mount=type=cache,target=/var/cache/apk \
    apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite \
    sqlite-dev \
    oniguruma-dev \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    mysql-client \
    redis \
    nginx \
    supervisor \
    bash

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    pdo_sqlite \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Install production dependencies with cache mount
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Copy application code
COPY . .

# Copy built assets from node-builder
COPY --from=node-builder /app/public/build ./public/build

# Copy entrypoint script and make it executable
COPY fly/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Generate optimized autoloader and discover packages
RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi || true

# Create directories, set permissions, and configure PHP
RUN mkdir -p \
    /var/log/{supervisor,nginx} \
    /var/run/nginx \
    storage/{app,logs,framework/{cache,sessions,views}} \
    bootstrap/cache \
    && touch storage/app/database.sqlite \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 storage bootstrap/cache \
    && chmod 664 storage/app/database.sqlite

# Configure PHP for production
RUN { \
    echo "memory_limit = 512M"; \
    echo "upload_max_filesize = 100M"; \
    echo "post_max_size = 100M"; \
    echo "max_execution_time = 300"; \
    echo "opcache.enable = 1"; \
    echo "opcache.memory_consumption = 128"; \
    echo "opcache.validate_timestamps = 0"; \
    echo "opcache.max_accelerated_files = 10000"; \
    echo "opcache.interned_strings_buffer = 16"; \
    } > /usr/local/etc/php/conf.d/laravel.ini

# Create .env from example and set production values
RUN [ ! -f .env ] && cp .env.example .env || true \
    && sed -i 's/APP_ENV=local/APP_ENV=production/' .env \
    && sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env \
    && sed -i 's|APP_URL=http://localhost|APP_URL=https://regulacao-list-br.fly.dev|' .env \
    && sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=sqlite/' .env \
    && sed -i 's|# DB_DATABASE=database/laravel.sqlite|DB_DATABASE=/var/www/html/storage/app/database.sqlite|' .env

# Expose port
EXPOSE 8080

# Health check
HEALTHCHECK --interval=20s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8080/health || exit 1

# Set entrypoint and default command
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
