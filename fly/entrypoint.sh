#!/bin/bash

set -e

echo "🚁 Application Starting..."
echo "=========================================="

# Function to initialize application (minimal)
init_app() {
    echo "🔧 Initializing application..."

    # Generate application key if not set
    if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:CHANGEME" ]; then
        echo "🔑 Generating application key..."
        php artisan key:generate --force --no-interaction || true
    fi

    # Create storage directories
    echo "📁 Creating storage directories..."
    mkdir -p /var/www/html/storage/app/public
    mkdir -p /var/www/html/storage/logs
    mkdir -p /var/www/html/storage/framework/cache
    mkdir -p /var/www/html/storage/framework/sessions
    mkdir -p /var/www/html/storage/framework/views

    # Create SQLite database if it doesn't exist
    echo "🗄️ Setting up SQLite database..."
    if [ ! -f "/var/www/html/storage/app/database.sqlite" ]; then
        echo "📁 Creating SQLite database file..."
        touch /var/www/html/storage/app/database.sqlite
        chmod 664 /var/www/html/storage/app/database.sqlite
    fi

    # Create storage link if it doesn't exist
    if [ ! -L "/var/www/html/public/storage" ]; then
        echo "🔗 Creating storage symlink..."
        php artisan storage:link --force || true
    fi

    # Run database migrations
    echo "🗄️ Running database migrations..."
    php artisan migrate --force --no-interaction || echo "⚠️ Migration failed, continuing..."

    # Set proper permissions
    echo "🔒 Setting storage permissions..."
    chown -R www-data:www-data /var/www/html/storage || true
    chown -R www-data:www-data /var/www/html/bootstrap/cache || true
    chmod -R 775 /var/www/html/storage || true
    chmod -R 775 /var/www/html/bootstrap/cache || true

    echo "✅ Laravel application initialized"
}

# Function to start services based on Fly.io process type
start_services() {
    # Get the process type from Fly.io
    FLY_PROCESS_GROUP=${FLY_PROCESS_GROUP:-app}

    echo "🎯 Starting process group: $FLY_PROCESS_GROUP"

    case "$FLY_PROCESS_GROUP" in
        "worker")
            echo "👷 Starting queue worker..."
            # Skip worker for now to avoid database dependency
            echo "⚠️  Worker disabled temporarily - sleeping"
            sleep infinity
            ;;
        "scheduler")
            echo "⏰ Starting task scheduler..."
            # Skip scheduler for now to avoid database dependency
            echo "⚠️  Scheduler disabled temporarily - sleeping"
            sleep infinity
            ;;
        "octane")
            echo "🚀 Starting Octane server..."
            exec php artisan octane:start --server=swoole --host=0.0.0.0 --port=8080 --workers=4
            ;;
        *)
            echo "🌐 Starting web server..."
            # Pipe output to grep to filter health checks, run in background and wait
            "$@" 2>&1 | grep -v "/health" &
            wait $!
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

    # Initialize Laravel application (minimal, no database dependencies)
    if [ "$FLY_PROCESS_GROUP" != "worker" ] && [ "$FLY_PROCESS_GROUP" != "scheduler" ]; then
        init_app
    fi

    # Start appropriate services
    start_services "$@"
}

# Ensure we're in the correct directory
cd /var/www/html

# Run main function
main "$@"
