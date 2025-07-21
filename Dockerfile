# Build stage for Node.js assets
FROM node:20-alpine AS node-build

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install Node dependencies
RUN npm ci

# Copy source code needed for build
COPY . .

# Build assets
RUN npm run build

# PHP base stage
FROM php:8.4-fpm-alpine AS php-base

# Install system dependencies
RUN apk add --no-cache \
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
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
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
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --no-scripts --no-autoloader --optimize-autoloader

# Development stage
FROM php-base AS development

# Install development dependencies
RUN composer install --optimize-autoloader

# Copy application code
COPY . .

# Copy built assets from node-build stage
COPY --from=node-build /app/public/build ./public/build

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Generate optimized autoloader
RUN composer dump-autoload --optimize

# Create entrypoint script for development
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# Production stage - optimized for both Docker Compose and Fly.io
FROM php-base AS production

# Copy application code
COPY . .

# Copy built assets from node-build stage
COPY --from=node-build /app/public/build ./public/build

# Create .env file from example if it doesn't exist
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# Generate optimized autoloader and run post-install scripts
RUN composer dump-autoload --optimize && php artisan package:discover --ansi

# Copy configuration files from fly directory (more robust for production)
COPY fly/nginx.conf /etc/nginx/nginx.conf
COPY fly/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY fly/entrypoint.sh /entrypoint.sh

# Create necessary directories and set permissions
RUN mkdir -p /var/log/supervisor \
    && mkdir -p /var/run/nginx \
    && mkdir -p /var/log/nginx \
    && mkdir -p /var/www/html/storage/app \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/bootstrap/cache

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache \
    && chmod +x /entrypoint.sh

# Create database file for SQLite (if needed)
RUN touch /var/www/html/storage/database.sqlite \
    && chown www-data:www-data /var/www/html/storage/database.sqlite

# Configure PHP for production
RUN echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/laravel.ini \
    && echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/laravel.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/laravel.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/laravel.ini \
    && echo "opcache.enable = 1" >> /usr/local/etc/php/conf.d/laravel.ini \
    && echo "opcache.memory_consumption = 128" >> /usr/local/etc/php/conf.d/laravel.ini

# Expose ports (8080 for Fly.io, 80 for local)
EXPOSE 80 8080

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/health || curl -f http://localhost:80/health || exit 1

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Default to production stage
FROM production
