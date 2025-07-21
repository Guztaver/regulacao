#!/bin/bash

set -e

echo "🚂 Railway Deployment Script"
echo "=============================="

# Check if we're in Railway environment
if [ -z "$RAILWAY_ENVIRONMENT" ]; then
    echo "⚠️  Not running in Railway environment"
    exit 1
fi

echo "🌍 Environment: $RAILWAY_ENVIRONMENT"
echo "📦 Building application..."

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Install Node.js dependencies
echo "📦 Installing Node.js dependencies..."
npm ci --only=production

# Build frontend assets
echo "🎨 Building frontend assets..."
npm run build

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

# Cache configuration for production
echo "⚡ Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
if [ "$RAILWAY_ENVIRONMENT" = "production" ]; then
    echo "🗄️  Running database migrations..."
    php artisan migrate --force
else
    echo "🗄️  Running database migrations with seeding..."
    php artisan migrate --force
    # Only seed in non-production environments
    php artisan db:seed --force || echo "⚠️  Seeding failed or not needed"
fi

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link || echo "Storage link already exists"

# Clear any old caches that might interfere
echo "🧹 Clearing old caches..."
php artisan cache:clear || true

# Optimize for production
echo "⚡ Optimizing application..."
php artisan optimize

# Set proper permissions
echo "🔒 Setting permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache

echo "✅ Deployment completed successfully!"
echo "🚀 Application ready to serve"
