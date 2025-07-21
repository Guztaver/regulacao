# syntax=docker/dockerfile:1

# =============================================================================
# Build stage for Node.js assets - Optimized for caching
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
# PHP base stage with optimized dependency installation
# =============================================================================
FROM php:8.4-fpm-alpine AS php-base

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
    supervisor

# Configure and install PHP extensions in parallel
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

# =============================================================================
# Composer dependencies stage - Separate for better caching
# =============================================================================
FROM php-base AS composer-deps

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

# =============================================================================
# Development stage - Lightweight for local development
# =============================================================================
FROM composer-deps AS development

# Install dev dependencies
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --optimize-autoloader --no-interaction

# Copy application code
COPY . .

# Copy built assets from node-builder
COPY --from=node-builder /app/public/build ./public/build

# Generate optimized autoloader
RUN composer dump-autoload --optimize

# Create storage directories and set permissions in single layer
RUN mkdir -p storage/{app,logs,framework/{cache,sessions,views}} bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 storage bootstrap/cache

# Copy entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# =============================================================================
# Production stage - Optimized for deployment
# =============================================================================
FROM composer-deps AS production

# Copy application code
COPY . .

# Copy built assets from node-builder
COPY --from=node-builder /app/public/build ./public/build

# Generate optimized autoloader and discover packages
RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi

# Copy configuration files
COPY fly/nginx.conf /etc/nginx/nginx.conf
COPY fly/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY fly/entrypoint.sh /entrypoint.sh

# Create directories, set permissions, and configure PHP in single layer
RUN mkdir -p \
    /var/log/{supervisor,nginx} \
    /var/run/nginx \
    storage/{app,logs,framework/{cache,sessions,views}} \
    bootstrap/cache \
    && touch storage/database.sqlite \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 storage bootstrap/cache \
    && chmod +x /entrypoint.sh \
    && { \
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

# Create .env from example if needed
RUN [ ! -f .env ] && cp .env.example .env || true

# Expose ports
EXPOSE 80 8080

# Health check with faster timeout
HEALTHCHECK --interval=20s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8080/health || curl -f http://localhost:80/health || exit 1

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Default to production stage
FROM production
