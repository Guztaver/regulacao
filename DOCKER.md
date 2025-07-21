# Docker Setup Guide

This guide covers how to use Docker with the Regulação List application for both development and production environments.

## Prerequisites

- Docker Engine 20.10+
- Docker Compose v2.0+
- Git
- Make (optional, for simplified commands)

## Quick Start

### Development Environment

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd regulacao-list
   ```

2. **Start the development environment:**
   ```bash
   # Using Make (recommended)
   make setup

   # Or manually
   cp .env.example .env
   docker-compose up -d --build
   docker-compose exec app php artisan migrate
   ```

3. **Access the application:**
   - Application: http://localhost:8000
   - MailHog (email testing): http://localhost:8025
   - Database: localhost:3306
   - Redis: localhost:6379

### Production Environment

1. **Build and deploy:**
   ```bash
   # Set environment variables
   cp .env.example .env.production
   # Edit .env.production with production values

   # Start production environment
   docker-compose -f docker-compose.prod.yml --env-file .env.production up -d --build
   ```

## Available Commands

### Using Make (Recommended)

```bash
# Development
make dev                # Start development environment
make dev-build         # Build and start development environment
make stop              # Stop development environment
make restart           # Restart development environment

# Production
make prod              # Start production environment
make prod-build        # Build and start production environment
make prod-stop         # Stop production environment

# Utilities
make logs              # Show logs from all containers
make logs-app          # Show logs from app container
make shell             # Open shell in app container
make db-shell          # Open MySQL shell
make redis-cli         # Open Redis CLI

# Laravel commands
make migrate           # Run database migrations
make migrate-fresh     # Fresh migration with seeding
make seed              # Run database seeders
make cache-clear       # Clear all caches
make cache-optimize    # Optimize caches for production

# Testing
make test              # Run tests
make test-coverage     # Run tests with coverage

# Code quality
make lint              # Run linting
make format            # Format code

# Assets
make assets-build      # Build assets
make assets-watch      # Watch and build assets

# Cleanup
make clean             # Clean up containers and volumes
make clean-all         # Clean up everything including images

# Complete setup
make setup             # Install dependencies and start development environment
```

### Using Docker Compose Directly

```bash
# Development
docker-compose up -d --build
docker-compose down
docker-compose logs -f
docker-compose exec app sh

# Production
docker-compose -f docker-compose.prod.yml up -d --build
docker-compose -f docker-compose.prod.yml down
docker-compose -f docker-compose.prod.yml logs -f
```

## Container Architecture

### Development Containers

1. **app** - Laravel application (PHP-FPM + development server)
2. **worker** - Queue worker
3. **scheduler** - Task scheduler
4. **vite** - Asset compilation and hot reloading
5. **db** - MySQL database
6. **redis** - Redis cache and session store
7. **mailhog** - Email testing

### Production Containers

1. **app** - Laravel application (Nginx + PHP-FPM)
2. **worker** - Queue worker
3. **db** - MySQL database
4. **redis** - Redis cache and session store
5. **nginx-proxy** - SSL termination and load balancing

## Environment Variables

### Required Production Variables

Create a `.env.production` file with these variables:

```bash
# Application
APP_KEY=base64:your-app-key-here
APP_URL=https://your-domain.com
APP_ENV=production
APP_DEBUG=false

# Database
DB_PASSWORD=your-secure-database-password
DB_ROOT_PASSWORD=your-secure-root-password

# Cache & Sessions
REDIS_HOST=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Email
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
```

## GitHub Actions CI/CD

The repository includes automated CI/CD pipelines:

### Workflows

1. **tests.yml** - Runs tests on pull requests and pushes
2. **lint.yml** - Code quality checks
3. **docker.yml** - Docker build and deployment

### Deployment Process

1. **Development:** Push to `develop` branch triggers staging deployment
2. **Production:** Create a GitHub release triggers production deployment

### Required Secrets

Configure these secrets in your GitHub repository:

#### Staging Environment
- `STAGING_HOST` - Server hostname/IP
- `STAGING_USER` - SSH username
- `STAGING_SSH_KEY` - Private SSH key
- `STAGING_APP_KEY` - Laravel application key
- `STAGING_DB_PASSWORD` - Database password
- `STAGING_DB_ROOT_PASSWORD` - Database root password
- `STAGING_APP_URL` - Application URL

#### Production Environment
- `PRODUCTION_HOST` - Server hostname/IP
- `PRODUCTION_USER` - SSH username
- `PRODUCTION_SSH_KEY` - Private SSH key
- `PRODUCTION_APP_KEY` - Laravel application key
- `PRODUCTION_DB_PASSWORD` - Database password
- `PRODUCTION_DB_ROOT_PASSWORD` - Database root password
- `PRODUCTION_APP_URL` - Application URL

## Container Customization

### Custom PHP Configuration

Create `docker/php.ini` to override PHP settings:

```ini
upload_max_filesize = 100M
post_max_size = 100M
memory_limit = 512M
max_execution_time = 300
```

Update Dockerfile to copy this file:

```dockerfile
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
```

### Custom Nginx Configuration

Modify `docker/nginx.conf` to customize Nginx settings.

### Adding PHP Extensions

Add to Dockerfile:

```dockerfile
RUN docker-php-ext-install extension_name
```

## Volume Management

### Development Volumes

- Source code is mounted for live editing
- Database and Redis data persist between restarts
- Node modules and vendor directories are excluded

### Production Volumes

- Only data directories are mounted
- Application code is baked into the image
- Optimized for performance and security

## Health Checks

The application includes health check endpoints:

- `GET /health` - Basic health check
- Docker containers have built-in health checks

## Performance Optimization

### Production Optimizations

1. **Multi-stage builds** reduce image size
2. **OPcache** enabled for PHP
3. **Nginx gzip** compression
4. **Redis** for caching and sessions
5. **Optimized Composer** autoloader

### Resource Limits

Production containers have resource limits:

- **app**: 512MB memory limit
- **worker**: 256MB memory limit
- **db**: 512MB memory limit
- **redis**: 128MB memory limit

## Troubleshooting

### Common Issues

1. **Permission errors:**
   ```bash
   make shell-root
   chown -R www-data:www-data storage bootstrap/cache
   chmod -R 775 storage bootstrap/cache
   ```

2. **Database connection issues:**
   ```bash
   # Check if database is running
   docker-compose ps db
   
   # Check database logs
   docker-compose logs db
   ```

3. **Redis connection issues:**
   ```bash
   # Test Redis connection
   make redis-cli
   ping
   ```

4. **Clear all caches:**
   ```bash
   make cache-clear
   ```

### Logs

View container logs:

```bash
# All containers
make logs

# Specific container
docker-compose logs -f app
docker-compose logs -f worker
docker-compose logs -f db
```

## Security Considerations

### Production Security

1. **Environment variables** - Never commit secrets to repository
2. **SSL/TLS** - Use HTTPS in production
3. **Database** - Use strong passwords and restrict access
4. **Updates** - Keep base images updated
5. **Scanning** - CI/CD includes vulnerability scanning

### Network Security

- Containers communicate through internal networks
- Only necessary ports are exposed
- Database and Redis are not directly accessible from outside

## Backup and Recovery

### Database Backup

```bash
# Create backup
make backup-db

# Restore backup
make restore-db file=backup_20240101_120000.sql
```

### Volume Backup

```bash
# Backup all volumes
docker run --rm -v regulacao-list_mysql_data:/data -v $(pwd):/backup alpine tar czf /backup/mysql_backup.tar.gz -C /data .
```

## Monitoring

### Container Monitoring

```bash
# Check container status
docker-compose ps

# Monitor resource usage
docker stats

# Health check
make health
```

### Application Monitoring

Consider integrating:

- **Laravel Telescope** - Application debugging
- **Laravel Horizon** - Queue monitoring
- **Prometheus + Grafana** - Metrics and dashboards
- **Sentry** - Error tracking

## Updates and Maintenance

### Updating Dependencies

1. **PHP dependencies:**
   ```bash
   make shell
   composer update
   ```

2. **Node dependencies:**
   ```bash
   npm update
   make assets-build
   ```

3. **Base images:**
   ```bash
   docker-compose pull
   make dev-build
   ```

### Database Migrations

```bash
# Run migrations
make migrate

# Fresh migration (development only)
make migrate-fresh
```

This Docker setup provides a robust, scalable, and maintainable environment for the Regulação List application, suitable for both development and production use.