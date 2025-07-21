#!/bin/bash

set -e

echo "🚁 Fly.io Deployment Script for Regulação List"
echo "=============================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if flyctl is installed
check_flyctl() {
    if ! command -v flyctl &> /dev/null; then
        print_error "flyctl is not installed. Please install it first:"
        echo "curl -L https://fly.io/install.sh | sh"
        exit 1
    fi

    print_success "flyctl is installed: $(flyctl version)"
}

# Function to check if user is logged in
check_auth() {
    if ! flyctl auth whoami &> /dev/null; then
        print_error "You are not logged into Fly.io. Please login first:"
        echo "flyctl auth login"
        exit 1
    fi

    print_success "Logged in as: $(flyctl auth whoami)"
}

# Function to initialize Fly.io app if needed
init_app() {
    if [ ! -f "fly.toml" ]; then
        print_error "fly.toml not found. Please run this script from the project root."
        exit 1
    fi

    local app_name=$(grep "^app = " fly.toml | cut -d'"' -f2)

    if ! flyctl status --app "$app_name" &> /dev/null; then
        print_status "Creating new Fly.io app: $app_name"

        # Try to create with the specified name first
        if ! flyctl apps create "$app_name" --generate-name=false 2>/dev/null; then
            print_warning "App name '$app_name' is not available. Generating a unique name..."

            # Generate a unique app name
            local new_app_name=$(flyctl apps create --generate-name=true | grep "New app created:" | cut -d' ' -f4)

            if [ -n "$new_app_name" ]; then
                print_success "Created app with unique name: $new_app_name"

                # Update fly.toml with the new app name
                sed -i.bak "s/^app = \"$app_name\"/app = \"$new_app_name\"/" fly.toml
                print_status "Updated fly.toml with new app name: $new_app_name"

                # Update APP_URL if it exists
                if grep -q "APP_URL.*fly.dev" fly.toml; then
                    sed -i.bak "s|https://[^.]*\.fly\.dev|https://$new_app_name.fly.dev|" fly.toml
                fi
            else
                print_error "Failed to create app with unique name"
                exit 1
            fi
        else
            print_success "App created successfully"
        fi
    else
        print_success "App $app_name already exists"
    fi
}

# Function to create volumes
create_volumes() {
    local app_name=$(grep "^app = " fly.toml | cut -d'"' -f2)

    print_status "Checking for storage volume..."

    if ! flyctl volumes list --app "$app_name" | grep -q "storage_volume"; then
        print_status "Creating storage volume..."
        flyctl volumes create storage_volume --size 1 --app "$app_name"
        print_success "Storage volume created"
    else
        print_success "Storage volume already exists"
    fi
}

# Function to set up database
setup_database() {
    local app_name=$(grep "^app = " fly.toml | cut -d'"' -f2)

    print_status "Setting up database..."

    # Check if database app exists
    local db_app="${app_name}-db"

    if ! flyctl status --app "$db_app" &> /dev/null; then
        print_status "Creating MySQL database..."
        flyctl mysql create --app "$db_app" --name "$db_app"

        print_status "Attaching database to app..."
        flyctl mysql attach "$db_app" --app "$app_name"

        print_success "Database created and attached"
    else
        print_success "Database already exists"
    fi
}

# Function to set up Redis (optional)
setup_redis() {
    local app_name=$(grep "^app = " fly.toml | cut -d'"' -f2)
    local redis_app="${app_name}-redis"

    read -p "Do you want to set up Redis for caching and sessions? (y/N): " -n 1 -r
    echo

    if [[ $REPLY =~ ^[Yy]$ ]]; then
        print_status "Setting up Redis..."

        if ! flyctl status --app "$redis_app" &> /dev/null; then
            print_status "Creating Redis instance..."
            flyctl redis create --name "$redis_app"

            print_status "Attaching Redis to app..."
            flyctl redis attach "$redis_app" --app "$app_name"

            print_success "Redis created and attached"
        else
            print_success "Redis already exists"
        fi
    else
        print_warning "Skipping Redis setup. Using database for cache and sessions."
    fi
}

# Function to set environment variables
set_environment() {
    local app_name=$(grep "^app = " fly.toml | cut -d'"' -f2)

    print_status "Setting environment variables..."

    # Generate APP_KEY if not set
    if ! flyctl secrets list --app "$app_name" | grep -q "APP_KEY"; then
        print_status "Generating APP_KEY..."
        local app_key=$(openssl rand -base64 32)
        flyctl secrets set APP_KEY="base64:$app_key" --app "$app_name"
    fi

    # Set other essential variables
    flyctl secrets set \
        APP_ENV=production \
        APP_DEBUG=false \
        LOG_CHANNEL=stderr \
        SESSION_SECURE_COOKIE=true \
        TRUSTED_PROXIES="*" \
        --app "$app_name"

    print_success "Environment variables configured"
}

# Function to deploy the application
deploy_app() {
    local app_name=$(grep "^app = " fly.toml | cut -d'"' -f2)

    print_status "Building and deploying application..."

    # Build and deploy
    flyctl deploy --app "$app_name" --build-arg BUILDKIT_INLINE_CACHE=1

    if [ $? -eq 0 ]; then
        print_success "Deployment completed successfully!"

        # Show app URL
        local app_url="https://${app_name}.fly.dev"
        print_success "Your application is available at: $app_url"

        # Show logs
        echo
        print_status "Recent logs:"
        flyctl logs --app "$app_name" -n 20

    else
        print_error "Deployment failed!"
        exit 1
    fi
}

# Function to scale the application
scale_app() {
    local app_name=$(grep "^app = " fly.toml | cut -d'"' -f2)

    read -p "Do you want to configure scaling? (y/N): " -n 1 -r
    echo

    if [[ $REPLY =~ ^[Yy]$ ]]; then
        print_status "Current scaling configuration:"
        flyctl scale show --app "$app_name"

        echo
        echo "Scale options:"
        echo "1. Small (1 CPU, 256MB RAM) - Good for testing"
        echo "2. Medium (1 CPU, 512MB RAM) - Default, good for small apps"
        echo "3. Large (2 CPU, 1GB RAM) - Good for production"
        echo "4. Custom - Enter your own values"

        read -p "Choose scaling option (1-4): " -n 1 -r
        echo

        case $REPLY in
            1)
                flyctl scale vm shared-cpu-1x --memory 256 --app "$app_name"
                ;;
            2)
                flyctl scale vm shared-cpu-1x --memory 512 --app "$app_name"
                ;;
            3)
                flyctl scale vm shared-cpu-2x --memory 1024 --app "$app_name"
                ;;
            4)
                read -p "Enter VM size (shared-cpu-1x, shared-cpu-2x, etc.): " vm_size
                read -p "Enter memory in MB: " memory
                flyctl scale vm "$vm_size" --memory "$memory" --app "$app_name"
                ;;
            *)
                print_warning "Skipping scaling configuration"
                ;;
        esac
    fi
}

# Function to set up worker processes
setup_workers() {
    local app_name=$(grep "^app = " fly.toml | cut -d'"' -f2)

    read -p "Do you want to set up background workers for queues? (y/N): " -n 1 -r
    echo

    if [[ $REPLY =~ ^[Yy]$ ]]; then
        print_status "Scaling worker processes..."
        flyctl scale count worker=1 --app "$app_name"
        print_success "Worker process configured"
    else
        print_warning "Skipping worker setup. Queues will run synchronously."
    fi
}

# Function to show deployment information
show_info() {
    local app_name=$(grep "^app = " fly.toml | cut -d'"' -f2)

    echo
    print_success "🎉 Deployment Summary"
    echo "===================="
    echo "App Name: $app_name"
    echo "URL: https://${app_name}.fly.dev"
    echo "Dashboard: https://fly.io/apps/$app_name"
    echo
    echo "Useful commands:"
    echo "  flyctl status --app $app_name              # Check app status"
    echo "  flyctl logs --app $app_name                # View logs"
    echo "  flyctl ssh console --app $app_name         # SSH into container"
    echo "  flyctl secrets list --app $app_name        # List environment variables"
    echo "  flyctl deploy --app $app_name              # Deploy updates"
    echo "  flyctl scale show --app $app_name          # Show scaling info"
    echo
    print_status "Visit the Fly.io dashboard for more management options!"
}

# Function to run post-deployment tasks
post_deployment() {
    local app_name=$(grep "^app = " fly.toml | cut -d'"' -f2)

    print_status "Running post-deployment tasks..."

    # Wait a moment for the app to be ready
    sleep 10

    # Check if app is responding
    local app_url="https://${app_name}.fly.dev"
    if curl -f -s "$app_url/health" > /dev/null; then
        print_success "Application is responding to health checks"
    else
        print_warning "Application may not be ready yet. Check logs with: flyctl logs --app $app_name"
    fi

    # Optionally run database seeders
    read -p "Do you want to run database seeders? (y/N): " -n 1 -r
    echo

    if [[ $REPLY =~ ^[Yy]$ ]]; then
        print_status "Running database seeders..."
        flyctl ssh console --app "$app_name" -C "php artisan db:seed --force"
        print_success "Database seeders completed"
    fi
}

# Main deployment function
main() {
    echo "Starting Fly.io deployment process..."
    echo

    # Pre-flight checks
    check_flyctl
    check_auth

    # Deployment steps
    init_app
    create_volumes
    setup_database
    setup_redis
    set_environment

    # Deploy
    deploy_app

    # Post-deployment configuration
    scale_app
    setup_workers
    post_deployment

    # Show final information
    show_info
}

# Parse command line arguments
case "${1:-}" in
    "init")
        print_status "Initializing Fly.io app only..."
        check_flyctl
        check_auth
        init_app
        create_volumes
        setup_database
        setup_redis
        set_environment
        print_success "Initialization complete. Run './fly-deploy.sh' to deploy."
        ;;
    "deploy")
        print_status "Deploying existing app..."
        check_flyctl
        check_auth
        deploy_app
        ;;
    "status")
        check_flyctl
        local app_name=$(grep "^app = " fly.toml | cut -d'"' -f2)
        flyctl status --app "$app_name"
        ;;
    "logs")
        check_flyctl
        local app_name=$(grep "^app = " fly.toml | cut -d'"' -f2)
        flyctl logs --app "$app_name" -f
        ;;
    "help"|"-h"|"--help")
        echo "Fly.io Deployment Script"
        echo "Usage: $0 [command]"
        echo
        echo "Commands:"
        echo "  (no args)  Complete deployment process"
        echo "  init       Initialize Fly.io app and services only"
        echo "  deploy     Deploy application only"
        echo "  status     Show application status"
        echo "  logs       Show and follow application logs"
        echo "  help       Show this help message"
        ;;
    "")
        main
        ;;
    *)
        print_error "Unknown command: $1"
        echo "Run '$0 help' for usage information."
        exit 1
        ;;
esac
