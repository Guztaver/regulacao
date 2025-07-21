#!/bin/bash

set -e

echo "🚁 Fly.io Laravel Application Starting..."
echo "=========================================="

# Function to wait for database
wait_for_database() {
    echo "⏳ Waiting for database connection..."

    # Maximum wait time in seconds
    max_wait=60
    wait_time=0

    until php artisan migrate:status >/dev/null 2>&1; do
        if [ $wait_time -ge $max_wait ]; then
            echo "❌ Database connection timeout after ${max_wait}s"
            exit 1
        fi

        echo "⏳ Database not ready, waiting... (${wait_time}s/${max_wait}s)"
        sleep 2
        wait_time=$((wait_time + 2))
    done

    echo "✅ Database connection established"
}

# Function to wait for Redis (if configured)
wait_for_redis() {
    if [ "$CACHE_DRIVER" = "redis" ] || [ "$SESSION_DRIVER" = "redis" ] || [ "$QUEUE_CONNECTION" = "redis" ]; then
        echo "⏳ Waiting for Redis connection..."

        max_wait=30
        wait_time=0

        until php artisan cache:clear >/dev/null 2>&1; do
            if [ $wait_time -ge $max_wait ]; then
                echo "⚠️  Redis connection timeout, falling back to database drivers"
                export CACHE_DRIVER=database
                export SESSION_DRIVER=database
                export QUEUE_CONNECTION=database
                break
            fi

            echo "⏳ Redis not ready, waiting... (${wait_time}s/${max_wait}s)"
            sleep 2
            wait_time=$((wait_time + 2))
        done

        if [ $wait_time -lt $max_wait ]; then
            echo "✅ Redis connection established"
        fi
    fi
}

# Function to initialize Laravel application
init_laravel() {
    echo "🔧 Initializing Laravel application..."

    # Generate application key if not set
    if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:CHANGEME" ]; then
        echo "🔑 Generating application key..."
        php artisan key:generate --force --no-interaction
    fi

    # Wait for database
    wait_for_database

    # Wait for Redis if needed
    wait_for_redis

    # Create storage directories
    echo "📁 Creating storage directories..."
    mkdir -p /var/www/html/storage/app/public
    mkdir -p /var/www/html/storage/logs
    mkdir -p /var/www/html/storage/framework/cache
    mkdir -p /var/www/html/storage/framework/sessions
    mkdir -p /var/www/html/storage/framework/views

    # Create storage link if it doesn't exist
    if [ ! -L "/var/www/html/public/storage" ]; then
        echo "🔗 Creating storage symlink..."
        php artisan storage:link --force
    fi

    # Set proper permissions
    echo "🔒 Setting storage permissions..."
    chown -R www-data:www-data /var/www/html/storage
    chown -R www-data:www-data /var/www/html/bootstrap/cache
    chmod -R 775 /var/www/html/storage
    chmod -R 775 /var/www/html/bootstrap/cache

    # Cache Laravel configuration for production
    if [ "$APP_ENV" = "production" ]; then
        echo "⚡ Caching configuration for production..."
        php artisan config:cache --no-interaction
        php artisan route:cache --no-interaction
        php artisan view:cache --no-interaction
        php artisan event:cache --no-interaction

        # Optimize application
        echo "🚀 Optimizing application..."
        php artisan optimize --no-interaction
    else
        echo "🧹 Clearing caches for development..."
        php artisan config:clear --no-interaction || true
        php artisan route:clear --no-interaction || true
        php artisan view:clear --no-interaction || true
        php artisan cache:clear --no-interaction || true
    fi

    echo "✅ Laravel application initialized"
}

# Function to run database migrations
run_migrations() {
    echo "🗄️  Running database migrations..."

    # Check if we should run migrations
    if [ "$RUN_MIGRATIONS" != "false" ]; then
        php artisan migrate --force --no-interaction
        echo "✅ Database migrations completed"
    else
        echo "⏭️  Skipping migrations (RUN_MIGRATIONS=false)"
    fi
}

# Function to start services based on Fly.io process type
start_services() {
    # Get the process type from Fly.io
    FLY_PROCESS_GROUP=${FLY_PROCESS_GROUP:-app}

    echo "🎯 Starting process group: $FLY_PROCESS_GROUP"

    case "$FLY_PROCESS_GROUP" in
        "worker")
            echo "👷 Starting queue worker..."
            exec php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --memory=128 --verbose
            ;;
        "scheduler")
            echo "⏰ Starting task scheduler..."
            exec php artisan schedule:work --verbose
            ;;
        "octane")
            echo "🚀 Starting Octane server..."
            exec php artisan octane:start --server=swoole --host=0.0.0.0 --port=8080 --workers=4
            ;;
        *)
            echo "🌐 Starting web server (nginx + php-fpm)..."
            # Start supervisor which manages nginx and php-fpm
            exec "$@"
            ;;
    esac
}

# Function to handle cleanup on exit
cleanup() {
    echo "🛑 Shutting down gracefully..."
    # Kill any background processes
    jobs -p | xargs -r kill
    exit 0
}

# Set trap for cleanup
trap cleanup SIGTERM SIGINT

# Main execution
main() {
    echo "🌍 Environment: ${APP_ENV:-local}"
    echo "🔧 Process Group: ${FLY_PROCESS_GROUP:-app}"
    echo "🗄️  Database: ${DB_CONNECTION:-sqlite}"
    echo "💾 Cache Driver: ${CACHE_DRIVER:-file}"
    echo "📦 Queue Connection: ${QUEUE_CONNECTION:-sync}"
    echo "========================================"

    # Initialize Laravel application (except for worker processes)
    if [ "$FLY_PROCESS_GROUP" != "worker" ] && [ "$FLY_PROCESS_GROUP" != "scheduler" ]; then
        init_laravel
        run_migrations
    else
        # For worker processes, just wait for database
        wait_for_database
        wait_for_redis
    fi

    # Start appropriate services
    start_services "$@"
}

# Ensure we're in the correct directory
cd /var/www/html

# Run main function
main "$@"
