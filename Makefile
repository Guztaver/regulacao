.PHONY: help build up down restart logs shell test clean install dev prod deploy

# Default target
help: ## Show this help message
	@echo "Available commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# Development commands
install: ## Install dependencies and setup environment
	@echo "Installing dependencies..."
	npm ci
	composer install
	cp -n .env.example .env || true
	php artisan key:generate --ansi
	@echo "Setup complete!"

dev: ## Start development environment
	@echo "Starting development environment..."
	docker-compose up -d
	@echo "Application running at http://localhost:8000"
	@echo "MailHog available at http://localhost:8025"

dev-build: ## Build and start development environment
	@echo "Building and starting development environment..."
	docker-compose up -d --build
	@echo "Application running at http://localhost:8000"

stop: ## Stop development environment
	@echo "Stopping development environment..."
	docker-compose down

restart: ## Restart development environment
	@echo "Restarting development environment..."
	docker-compose restart

# Docker build commands
build: ## Build Docker image
	@echo "Building Docker image..."
	docker build -t regulacao-list:latest .

build-prod: ## Build production Docker image
	@echo "Building production Docker image..."
	docker build -t regulacao-list:prod .

# Production commands
prod: ## Start production environment
	@echo "Starting production environment..."
	docker-compose -f docker-compose.prod.yml up -d

prod-build: ## Build and start production environment
	@echo "Building and starting production environment..."
	docker-compose -f docker-compose.prod.yml up -d --build

prod-stop: ## Stop production environment
	@echo "Stopping production environment..."
	docker-compose -f docker-compose.prod.yml down

# Utility commands
logs: ## Show logs from all containers
	docker-compose logs -f

logs-app: ## Show logs from app container
	docker-compose logs -f app

shell: ## Open shell in app container
	docker-compose exec app sh

shell-root: ## Open root shell in app container
	docker-compose exec --user root app sh

db-shell: ## Open MySQL shell
	docker-compose exec db mysql -u laravel -p regulacao_list

redis-cli: ## Open Redis CLI
	docker-compose exec redis redis-cli

# Laravel commands
artisan: ## Run artisan command (use: make artisan cmd="migrate")
	docker-compose exec app php artisan $(cmd)

migrate: ## Run database migrations
	docker-compose exec app php artisan migrate

migrate-fresh: ## Fresh migration with seeding
	docker-compose exec app php artisan migrate:fresh --seed

seed: ## Run database seeders
	docker-compose exec app php artisan db:seed

cache-clear: ## Clear all caches
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

cache-optimize: ## Optimize caches for production
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache

# Testing commands
test: ## Run tests
	docker-compose exec app ./vendor/bin/pest

test-coverage: ## Run tests with coverage
	docker-compose exec app ./vendor/bin/pest --coverage

test-local: ## Run tests locally (without Docker)
	./vendor/bin/pest

# Code quality commands
lint: ## Run linting
	npm run lint
	./vendor/bin/pint

format: ## Format code
	npm run format
	./vendor/bin/pint

# Asset commands
assets-build: ## Build assets
	npm run build

assets-dev: ## Build assets for development
	npm run dev

assets-watch: ## Watch and build assets
	npm run dev

# Cleanup commands
clean: ## Clean up containers and volumes
	@echo "Cleaning up..."
	docker-compose down -v
	docker system prune -f
	@echo "Cleanup complete!"

clean-all: ## Clean up everything including images
	@echo "Cleaning up everything..."
	docker-compose down -v --rmi all
	docker system prune -a -f
	@echo "Full cleanup complete!"

# Backup commands
backup-db: ## Backup database
	@echo "Creating database backup..."
	docker-compose exec db mysqldump -u laravel -p regulacao_list > backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "Database backup created!"

restore-db: ## Restore database (use: make restore-db file=backup.sql)
	@echo "Restoring database from $(file)..."
	docker-compose exec -T db mysql -u laravel -p regulacao_list < $(file)
	@echo "Database restored!"

# Deployment commands
deploy-staging: ## Deploy to staging
	@echo "Deploying to staging..."
	git push origin develop
	@echo "Check GitHub Actions for deployment status"

deploy-prod: ## Deploy to production via release
	@echo "To deploy to production, create a release on GitHub"
	@echo "This will trigger the production deployment workflow"

# Health check
health: ## Check application health
	@echo "Checking application health..."
	@curl -f http://localhost:8000/health || echo "Application not responding"
	@docker-compose ps

# Development setup
setup: install dev migrate ## Complete development setup
	@echo "Development environment is ready!"
	@echo "Application: http://localhost:8000"
	@echo "MailHog: http://localhost:8025"
	@echo "Database: localhost:3306"
	@echo "Redis: localhost:6379"
