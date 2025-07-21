#!/bin/sh

set -e

# Function to wait for database
wait_for_db() {
    echo "Waiting for database to be ready..."

    # Extract database connection details from environment
    DB_HOST=${DB_HOST:-localhost}
    DB_PORT=${DB_PORT:-3306}
    DB_USERNAME=${DB_USERNAME:-root}
    DB_PASSWORD=${DB_PASSWORD:-}

    # Wait for database to be available
    until mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1" >/dev/null 2>&1; do
        echo "Database is unavailable - sleeping"
        sleep 2
    done

    echo "Database is ready!"
}

# Function to wait for Redis
wait_for_redis() {
    if [ -n "$REDIS_HOST" ]; then
        echo "Waiting for Redis to be ready..."

        REDIS_HOST=${REDIS_HOST:-localhost}
        REDIS_PORT=${REDIS_PORT:-6379}

        until redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" ping >/dev/null 2>&1; do
            echo "Redis is unavailable - sleeping"
            sleep 2
        done

        echo "Redis is ready!"
    fi
}

# Function to initialize Laravel application
init_laravel() {
    echo "Initializing Laravel application..."

    # Generate application key if not set
    if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:CHANGEME" ]; then
        echo "Generating application key..."
        php artisan key:generate --force
    fi

    # Wait for dependencies
    if [ "$DB_CONNECTION" = "mysql" ]; then
        wait_for_db
    fi

    wait_for_redis

    # Run migrations in production
    if [ "$APP_ENV" = "production" ]; then
        echo "Running database migrations..."
        php artisan migrate --force

        echo "Optimizing application..."
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
    fi

    # Clear caches in development
    if [ "$APP_ENV" = "local" ] || [ "$APP_ENV" = "development" ]; then
        echo "Clearing caches for development..."
        php artisan config:clear
        php artisan route:clear
        php artisan view:clear
        php artisan cache:clear
    fi

    # Create storage link if it doesn't exist
    if [ ! -L "/var/www/html/public/storage" ]; then
        echo "Creating storage link..."
        php artisan storage:link
    fi

    # Set proper permissions
    echo "Setting permissions..."
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
}

# Function to start services based on role
start_services() {
    # Check if this is a worker container
    if [ "$CONTAINER_ROLE" = "worker" ]; then
        echo "Starting queue worker..."
        exec php artisan queue:work --sleep=3 --tries=3 --max-time=3600

    # Check if this is a scheduler container
    elif [ "$CONTAINER_ROLE" = "scheduler" ]; then
        echo "Starting scheduler..."
        exec php artisan schedule:work

    # Default: start web server
    else
        echo "Starting web server..."
        exec "$@"
    fi
}

# Main execution
main() {
    echo "=== Docker Container Entrypoint ==="
    echo "APP_ENV: ${APP_ENV:-not-set}"
    echo "CONTAINER_ROLE: ${CONTAINER_ROLE:-web}"
    echo "=================================="

    # Initialize Laravel application
    init_laravel

    # Start appropriate services
    start_services "$@"
}

# Run main function
main "$@"
