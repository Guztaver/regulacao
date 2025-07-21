# Railway Deployment Guide

This guide covers deploying the Regulação List Laravel application to Railway using Nixpacks.

## What is Railway?

Railway is a modern deployment platform that provides:
- **Instant deployments** from Git repositories
- **Automatic HTTPS** and custom domains
- **Built-in databases** (PostgreSQL, MySQL, Redis)
- **Environment management** with Railway's dashboard
- **Nixpacks builds** for automatic dependency detection

## Prerequisites

- Railway account (sign up at [railway.app](https://railway.app))
- Git repository with your application code
- GitHub/GitLab account for automatic deployments

## Quick Deploy

### 1. One-Click Deploy

[![Deploy on Railway](https://railway.app/button.svg)](https://railway.app/template/laravel)

### 2. Manual Deploy

1. **Connect Repository:**
   ```bash
   # Install Railway CLI
   npm install -g @railway/cli
   
   # Login to Railway
   railway login
   
   # Initialize project
   railway init
   
   # Deploy
   railway up
   ```

2. **Or deploy from Railway Dashboard:**
   - Go to [railway.app/new](https://railway.app/new)
   - Select "Deploy from GitHub repo"
   - Choose your repository
   - Railway automatically detects Laravel and uses Nixpacks

## Database Setup

### PostgreSQL (Recommended)

1. **Add PostgreSQL service:**
   ```bash
   railway add postgresql
   ```

2. **Railway automatically sets these environment variables:**
   - `DATABASE_URL`
   - `PGHOST`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD`

3. **Update environment variables:**
   ```bash
   railway variables set DB_CONNECTION=pgsql
   ```

### MySQL Alternative

1. **Add MySQL service:**
   ```bash
   railway add mysql
   ```

2. **Environment variables set automatically:**
   - `DATABASE_URL`
   - `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`

3. **Update environment:**
   ```bash
   railway variables set DB_CONNECTION=mysql
   ```

## Environment Configuration

### Required Variables

Set these in Railway dashboard or CLI:

```bash
# Application
railway variables set APP_NAME="Regulação List"
railway variables set APP_ENV=production
railway variables set APP_DEBUG=false
railway variables set APP_KEY=$(php artisan key:generate --show)

# Database (automatically set when adding database service)
railway variables set DB_CONNECTION=pgsql

# Cache & Sessions
railway variables set CACHE_DRIVER=database
railway variables set SESSION_DRIVER=database
railway variables set QUEUE_CONNECTION=database

# Security
railway variables set SESSION_SECURE_COOKIE=true
```

### Optional Variables

```bash
# Mail (configure with your SMTP provider)
railway variables set MAIL_MAILER=smtp
railway variables set MAIL_HOST=your-smtp-host
railway variables set MAIL_PORT=587
railway variables set MAIL_USERNAME=your-username
railway variables set MAIL_PASSWORD=your-password
railway variables set MAIL_FROM_ADDRESS=noreply@yourdomain.com

# AWS S3 for file storage (optional)
railway variables set AWS_ACCESS_KEY_ID=your-access-key
railway variables set AWS_SECRET_ACCESS_KEY=your-secret-key
railway variables set AWS_DEFAULT_REGION=us-east-1
railway variables set AWS_BUCKET=your-bucket-name
```

### Bulk Environment Setup

Use the `.env.railway` file as a template:

```bash
# Copy Railway environment template
cp .env.railway .env.production

# Edit with your values
nano .env.production

# Set all variables at once
railway variables set --from-file .env.production
```

## Custom Domain

### Adding Custom Domain

1. **In Railway Dashboard:**
   - Go to your service settings
   - Click "Domains"
   - Add your custom domain
   - Configure DNS records as shown

2. **Via CLI:**
   ```bash
   railway domain add yourdomain.com
   ```

### SSL Certificate

Railway automatically provides SSL certificates for all domains.

## File Storage

### Local Storage (Default)

Files are stored in the container filesystem. **Note:** Files will be lost on redeploys.

### S3-Compatible Storage (Recommended)

```bash
# Add environment variables
railway variables set FILESYSTEM_DISK=s3
railway variables set AWS_ACCESS_KEY_ID=your-key
railway variables set AWS_SECRET_ACCESS_KEY=your-secret
railway variables set AWS_DEFAULT_REGION=us-east-1
railway variables set AWS_BUCKET=your-bucket
```

### Railway Volume Storage

```bash
# Mount persistent volume
railway volume mount /app/storage/app/public
```

## Scaling and Performance

### Vertical Scaling

Railway automatically scales based on your plan:
- **Hobby**: Up to 512MB RAM, 1 vCPU
- **Pro**: Up to 8GB RAM, 8 vCPU
- **Team**: Custom scaling

### Horizontal Scaling

```bash
# Scale to multiple instances
railway scale --replicas 3
```

### Performance Optimization

1. **Enable OPcache** (automatically enabled in production)
2. **Database connection pooling** (built into Railway databases)
3. **CDN integration** for static assets

## Monitoring and Logging

### Application Logs

```bash
# View live logs
railway logs

# Follow logs
railway logs --follow

# Filter logs
railway logs --filter "ERROR"
```

### Health Monitoring

Railway automatically monitors your application:
- Health check endpoint: `/health`
- Automatic restarts on failure
- Uptime monitoring

### Metrics Dashboard

Available in Railway dashboard:
- CPU and memory usage
- Request metrics
- Database performance
- Error rates

## Database Management

### Running Migrations

Migrations run automatically on deployment, but you can run manually:

```bash
# Connect to your service
railway connect

# Run migrations
php artisan migrate --force

# Seed database (development only)
php artisan db:seed --force
```

### Database Backups

Railway automatically backs up your databases:
- **PostgreSQL**: Daily backups, 7-day retention
- **MySQL**: Daily backups, 7-day retention

### Database Console

```bash
# Connect to PostgreSQL
railway connect postgresql

# Connect to MySQL
railway connect mysql
```

## Debugging

### Common Issues

1. **Build Failures:**
   ```bash
   # Check build logs
   railway logs --deployment
   
   # Common fixes:
   # - Ensure composer.json is valid
   # - Check PHP version compatibility
   # - Verify all required extensions
   ```

2. **Database Connection Issues:**
   ```bash
   # Verify database variables
   railway variables
   
   # Test connection
   railway run php artisan migrate:status
   ```

3. **Asset Build Failures:**
   ```bash
   # Check Node.js version in package.json
   # Ensure npm scripts are defined
   # Verify Vite configuration
   ```

### Debug Mode

**Never enable debug mode in production**, but for troubleshooting:

```bash
# Temporary debug enable
railway variables set APP_DEBUG=true

# Remember to disable after debugging
railway variables set APP_DEBUG=false
```

### Shell Access

```bash
# Get shell access to running container
railway shell

# Run artisan commands
railway run php artisan <command>
```

## Security Best Practices

### Environment Variables

- Never commit `.env` files to Git
- Use Railway's variable management
- Rotate keys regularly
- Use strong, unique passwords

### Application Security

```bash
# Security headers (add to middleware)
railway variables set SECURE_HEADERS=true

# Force HTTPS
railway variables set FORCE_HTTPS=true

# CSRF protection (enabled by default)
railway variables set SESSION_SECURE_COOKIE=true
```

### Database Security

- Use Railway's managed databases (automatically secured)
- Enable SSL connections
- Use strong passwords
- Restrict database access

## Cost Optimization

### Resource Management

1. **Right-size your service:**
   - Monitor CPU/memory usage
   - Scale down when possible
   - Use appropriate plan

2. **Database optimization:**
   - Regular maintenance
   - Index optimization
   - Query performance monitoring

### Usage Monitoring

```bash
# Check resource usage
railway status

# View billing information
railway billing
```

## CI/CD Integration

### GitHub Actions

Railway integrates with the existing GitHub Actions workflow:

```yaml
# In .github/workflows/railway.yml
name: Railway Deploy

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Deploy to Railway
        uses: railway/deploy@v1
        with:
          railway-token: ${{ secrets.RAILWAY_TOKEN }}
```

### Manual Deployments

```bash
# Deploy current branch
railway up

# Deploy specific branch
railway up --branch production

# Deploy with environment
railway up --environment production
```

## Migration from Other Platforms

### From Heroku

Railway provides excellent Heroku compatibility:

```bash
# Import Heroku app
railway import heroku your-heroku-app

# Or manually migrate
railway variables set --from-heroku your-heroku-app
```

### From Docker

Use the existing Docker setup alongside Railway:

```bash
# Railway will automatically detect Laravel
# No changes needed to your Docker files
```

## Backup and Disaster Recovery

### Application Backup

```bash
# Export environment variables
railway variables > railway-env-backup.txt

# Database backup (automatic with Railway)
# Additional manual backup:
railway run pg_dump $DATABASE_URL > backup.sql
```

### Restore Process

```bash
# Restore variables
railway variables set --from-file railway-env-backup.txt

# Restore database
railway run psql $DATABASE_URL < backup.sql
```

## Support and Resources

### Railway Documentation

- [Railway Docs](https://docs.railway.app/)
- [Laravel on Railway Guide](https://docs.railway.app/getting-started/laravel)
- [Nixpacks Documentation](https://nixpacks.com/)

### Community Resources

- [Railway Discord](https://discord.gg/railway)
- [Railway Community Forum](https://help.railway.app/)
- [GitHub Issues](https://github.com/railwayapp/railway)

### Getting Help

1. **Check Railway status:** [status.railway.app](https://status.railway.app)
2. **Community Discord:** Get help from other developers
3. **Support tickets:** For paid plans
4. **Documentation:** Comprehensive guides and tutorials

## Example Deployment Workflow

Here's a complete deployment workflow:

```bash
# 1. Prepare your application
git clone <your-repo>
cd regulacao-list

# 2. Install Railway CLI
npm install -g @railway/cli

# 3. Login and initialize
railway login
railway init

# 4. Add database
railway add postgresql

# 5. Set environment variables
railway variables set APP_NAME="Regulação List"
railway variables set APP_ENV=production
railway variables set APP_DEBUG=false
railway variables set DB_CONNECTION=pgsql
railway variables set CACHE_DRIVER=database
railway variables set SESSION_DRIVER=database

# 6. Deploy
railway up

# 7. Run migrations
railway run php artisan migrate --force

# 8. Add custom domain (optional)
railway domain add yourdomain.com

# 9. Monitor deployment
railway logs --follow
```

Your Laravel application will be live at the Railway-provided URL or your custom domain!

## Advanced Configuration

### Multiple Environments

```bash
# Create staging environment
railway environment create staging

# Deploy to staging
railway up --environment staging

# Promote staging to production
railway environment promote staging production
```

### Custom Build Commands

Edit `nixpacks.toml` to customize the build process:

```toml
[phases.install]
cmds = [
    "composer install --no-dev --optimize-autoloader",
    "npm ci --only=production",
    "npm run build",
    "./railway-deploy.sh"  # Custom deployment script
]
```

### Worker Processes

For queue workers:

```bash
# Add worker service
railway service create worker

# Set worker command
railway variables set --service worker RAILWAY_RUN_COMMAND="php artisan queue:work"
```

This Railway setup provides a robust, scalable deployment solution for your Laravel application with minimal configuration required.