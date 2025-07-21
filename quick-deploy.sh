#!/bin/bash

set -e

# Simple Fly.io deployment script for Regulação List
echo "🚁 Quick Fly.io Deployment"
echo "=========================="

# Check if flyctl is installed
if ! command -v flyctl &> /dev/null; then
    echo "❌ flyctl not found. Installing..."
    curl -L https://fly.io/install.sh | sh
    export PATH="$HOME/.fly/bin:$PATH"
fi

# Check if logged in
if ! flyctl auth whoami &> /dev/null; then
    echo "🔐 Please log in to Fly.io..."
    flyctl auth login
fi

# Initialize app with generated name
echo "🆕 Creating new Fly.io app..."
flyctl launch --no-deploy --copy-config --name regulacao-list-br --region gru

# Set essential environment variables
echo "⚙️  Setting environment variables..."
flyctl secrets set \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    SESSION_SECURE_COOKIE=true \
    TRUSTED_PROXIES="*" \
    CACHE_DRIVER=database \
    SESSION_DRIVER=database \
    QUEUE_CONNECTION=database

# Generate and set APP_KEY
echo "🔑 Generating application key..."
APP_KEY=$(openssl rand -base64 32)
flyctl secrets set APP_KEY="base64:$APP_KEY"

# Create database
echo "🗄️  Setting up database..."
flyctl mysql create --name regulacao-list-br-db
flyctl mysql attach regulacao-list-br-db

# Create storage volume
echo "💾 Creating storage volume..."
flyctl volumes create storage_volume --size 1

# Deploy the application
echo "🚀 Deploying application..."
flyctl deploy

# Show final status
echo ""
echo "✅ Deployment completed!"
echo "🌐 Your app is available at: https://regulacao-list-br.fly.dev"
echo "📊 View dashboard: https://fly.io/apps/regulacao-list-br"
echo ""
echo "Useful commands:"
echo "  flyctl logs -f               # View logs"
echo "  flyctl ssh console           # SSH into container"
echo "  flyctl status                # Check app status"
echo "  flyctl deploy                # Deploy updates"
