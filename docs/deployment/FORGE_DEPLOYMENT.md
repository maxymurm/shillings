# Laravel Forge Deployment Guide for Shillings

## Prerequisites

1. Laravel Forge account with active subscription
2. Server provider account (DigitalOcean, Linode, AWS, Hetzner, or Vultr)
3. Domain name configured with DNS access
4. GitHub repository access

---

## Server Setup

### 1. Create New Server

In Forge dashboard:

| Setting | Value |
|---------|-------|
| **Server Provider** | Your preferred provider |
| **Server Name** | `shillings-production` |
| **Region** | Closest to your users |
| **Server Size** | Minimum 2GB RAM |
| **PHP Version** | 8.3 |
| **Database** | PostgreSQL 15 |
| **Server Type** | App Server |

### 2. Server Configuration

After server provisioning, configure these settings:

#### PHP Configuration
```bash
# /etc/php/8.3/fpm/php.ini
memory_limit = 512M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
```

#### PHP Extensions
Ensure these extensions are installed:
- bcmath (for precision arithmetic)
- gmp (for GnuCash-compatible calculations)
- pdo_pgsql (PostgreSQL driver)
- intl (internationalization)

---

## Site Setup

### 1. Create New Site

| Setting | Value |
|---------|-------|
| **Root Domain** | `shillings.yourdomain.com` |
| **Project Type** | General PHP / Laravel |
| **Web Directory** | `/public` |
| **PHP Version** | PHP 8.3 |
| **Create Database** | ✅ Yes |
| **Database Name** | `shillings_production` |

### 2. Install Git Repository

Connect to GitHub and select:
- Repository: `maxymurm/shillings`
- Branch: `main`

### 3. Environment Configuration

Set these environment variables in Forge:

```env
APP_NAME="Shillings"
APP_ENV=production
APP_KEY=base64:... (generate with php artisan key:generate --show)
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://shillings.yourdomain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=shillings_production
DB_USERNAME=forge
DB_PASSWORD=your-secure-password

# Cache
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (configure as needed)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Security
SANCTUM_STATEFUL_DOMAINS=shillings.yourdomain.com
SESSION_DOMAIN=.yourdomain.com
```

---

## Deploy Script

Configure this deployment script in Forge:

```bash
cd /home/forge/shillings.yourdomain.com

# Pull latest changes
git pull origin $FORGE_SITE_BRANCH

# Install composer dependencies
$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Run database migrations
$FORGE_PHP artisan migrate --force

# Clear and rebuild caches
$FORGE_PHP artisan cache:clear
$FORGE_PHP artisan config:cache
$FORGE_PHP artisan route:cache
$FORGE_PHP artisan view:cache
$FORGE_PHP artisan icons:cache
$FORGE_PHP artisan filament:cache-components

# Build frontend assets
npm ci
npm run build

# Restart services
$FORGE_PHP artisan queue:restart

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock
```

---

## SSL Configuration

### Enable Let's Encrypt SSL

1. Go to Site > SSL
2. Click "Let's Encrypt"
3. Enter domain(s): `shillings.yourdomain.com`
4. Click "Obtain Certificate"

---

## Queue Worker

### Setup Queue Worker

In Forge > Site > Queue:

| Setting | Value |
|---------|-------|
| **Connection** | redis |
| **Queue** | default |
| **Maximum Seconds** | 60 |
| **Sleep** | 3 |
| **Processes** | 1 |
| **Maximum Tries** | 3 |
| **Timeout** | 60 |

---

## Scheduler

### Enable Laravel Scheduler

In Forge > Site > Scheduler, add:

```
* * * * * cd /home/forge/shillings.yourdomain.com && php artisan schedule:run >> /dev/null 2>&1
```

---

## Database Backups

### Configure Automatic Backups

In Forge > Database > Backups:

1. Click "Add Backup Configuration"
2. Configure backup destination (S3, Spaces, etc.)
3. Set backup frequency (daily recommended)
4. Enable backup retention (keep 7 days)

---

## Monitoring

### Server Monitoring

Enable Forge monitoring:
- CPU usage alerts (>80%)
- Memory usage alerts (>80%)
- Disk usage alerts (>80%)

### Application Monitoring

Optionally integrate:
- Laravel Telescope (development/staging only)
- Sentry for error tracking
- New Relic or Blackfire for APM

---

## Security Checklist

- [ ] SSL certificate installed and auto-renewing
- [ ] Firewall enabled (22, 80, 443 only)
- [ ] SSH key authentication only
- [ ] Database not publicly accessible
- [ ] APP_DEBUG=false in production
- [ ] Strong database password
- [ ] Environment variables secured
- [ ] Regular security updates enabled
- [ ] 2FA enabled on Forge account

---

## Staging Environment

Repeat the above process for staging with these differences:

| Setting | Staging Value |
|---------|---------------|
| **Domain** | `staging.shillings.yourdomain.com` |
| **Branch** | `develop` |
| **Database** | `shillings_staging` |
| **APP_ENV** | `staging` |

---

## GitHub Secrets

Add these secrets to your GitHub repository for CI/CD:

| Secret Name | Description |
|-------------|-------------|
| `FORGE_STAGING_DEPLOY_URL` | Forge webhook URL for staging |
| `FORGE_PRODUCTION_DEPLOY_URL` | Forge webhook URL for production |

Get the webhook URLs from Forge > Site > Deployments > Deploy Webhook URL.

---

## Troubleshooting

### Common Issues

1. **500 Error After Deploy**
   - Check `storage/logs/laravel.log`
   - Verify environment variables
   - Run `php artisan config:clear`

2. **Permission Errors**
   - Run `chmod -R 775 storage bootstrap/cache`
   - Run `chown -R forge:forge storage bootstrap/cache`

3. **Queue Jobs Not Processing**
   - Check queue worker status in Forge
   - Verify Redis connection
   - Check `queue:failed` table

4. **Slow Performance**
   - Enable config/route/view caching
   - Ensure OPcache is enabled
   - Check database query performance
