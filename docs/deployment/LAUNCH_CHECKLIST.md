# Production Launch Checklist

## Pre-Launch

### Server Setup
- [ ] Server provisioned on Laravel Forge
- [ ] PostgreSQL 15+ database created
- [ ] Redis installed for cache/queue/sessions
- [ ] PHP 8.3 with required extensions (bcmath, gmp, pdo_pgsql, intl)
- [ ] Composer and Node.js installed

### Application Configuration
- [ ] Environment file configured with production values
- [ ] APP_ENV=production
- [ ] APP_DEBUG=false
- [ ] APP_KEY generated and set
- [ ] Database credentials configured
- [ ] Redis connection configured
- [ ] Mail service configured (SMTP/Mailgun/SES)

### Security
- [ ] SSL certificate installed (Let's Encrypt)
- [ ] HTTPS redirect enabled
- [ ] Firewall configured (ports 22, 80, 443 only)
- [ ] Database not publicly accessible
- [ ] Strong passwords for database and admin users
- [ ] 2FA enabled on Forge account
- [ ] Session driver set to Redis
- [ ] CSRF protection enabled
- [ ] Sanctum stateful domains configured

### Performance
- [ ] Config cache: `php artisan config:cache`
- [ ] Route cache: `php artisan route:cache`
- [ ] View cache: `php artisan view:cache`
- [ ] Event cache: `php artisan event:cache`
- [ ] Filament cache: `php artisan filament:cache-components`
- [ ] Icons cache: `php artisan icons:cache`
- [ ] OPcache enabled
- [ ] Database indexes verified

### Database
- [ ] Migrations run: `php artisan migrate --force`
- [ ] Seeders run for essential data (currencies, account types)
- [ ] Automatic backups configured (daily)
- [ ] Backup retention policy set (7+ days)
- [ ] Backup restoration tested

### Queue & Scheduler
- [ ] Queue worker configured and running
- [ ] Queue retry policy configured
- [ ] Scheduler cron job added
- [ ] Failed jobs notification set up

### Monitoring
- [ ] Error tracking (Sentry/Bugsnag) configured
- [ ] Uptime monitoring configured
- [ ] Server resource alerts (CPU, memory, disk)
- [ ] Log rotation configured
- [ ] Application health check endpoint

### CI/CD
- [ ] GitHub Actions workflow passing
- [ ] Forge deployment webhook configured
- [ ] Staging environment tested
- [ ] Deployment script verified
- [ ] Rollback procedure documented

---

## Launch Day

### Final Checks
- [ ] All tests passing on staging
- [ ] Staging environment matches production config
- [ ] DNS records updated and propagated
- [ ] SSL certificate active

### Deploy
- [ ] Trigger production deployment
- [ ] Verify deployment completed
- [ ] Check application loads correctly
- [ ] Verify database connections
- [ ] Test authentication flow
- [ ] Test key features (accounts, transactions)

### Post-Deploy Verification
- [ ] Homepage loads
- [ ] Admin panel accessible at /admin
- [ ] Login works correctly
- [ ] Can create new company
- [ ] Can create accounts
- [ ] Can create transactions
- [ ] Reports generate correctly
- [ ] API endpoints respond

---

## Post-Launch

### First 24 Hours
- [ ] Monitor error rates
- [ ] Monitor server performance
- [ ] Monitor database performance
- [ ] Check queue processing
- [ ] Verify scheduled tasks running
- [ ] Review access logs for issues

### First Week
- [ ] Address any reported issues
- [ ] Review performance metrics
- [ ] Optimize slow queries if needed
- [ ] Gather user feedback
- [ ] Update documentation as needed

### Ongoing
- [ ] Weekly backup verification
- [ ] Monthly security updates
- [ ] Quarterly dependency updates
- [ ] Regular performance reviews
- [ ] User feedback collection

---

## Rollback Procedure

If issues are found:

1. **Quick Fix Possible?**
   - Deploy hotfix branch
   - Monitor for resolution

2. **Need to Rollback?**
   ```bash
   # In Forge or on server
   cd /home/forge/your-domain.com
   git checkout <previous-commit>
   php artisan migrate:rollback --step=1  # if migration issues
   php artisan config:cache
   php artisan route:cache
   sudo service php8.3-fpm reload
   ```

3. **Database Rollback Needed?**
   - Restore from most recent backup
   - Apply incremental logs if available

---

## Emergency Contacts

| Role | Contact |
|------|---------|
| Lead Developer | Maxwell Murunga |
| Server Admin | [Configure] |
| Forge Support | https://forge.laravel.com/support |
| Domain Registrar | [Configure] |

---

## Documentation Links

- [Forge Deployment Guide](docs/deployment/FORGE_DEPLOYMENT.md)
- [API Documentation](docs/api/API_DOCUMENTATION.md)
- [Contributing Guide](CONTRIBUTING.md)
- [GitHub Issues](https://github.com/maxymurm/shillings/issues)
