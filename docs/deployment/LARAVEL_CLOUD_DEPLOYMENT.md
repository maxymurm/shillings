# Laravel Cloud Deployment Guide for Shillings

## Current Status

- **Environment**: Laravel Cloud (https://cloud.laravel.com)
- **Domain**: https://shillings-develop-x0styg.laravel.cloud
- **Database**: PostgreSQL (managed by Laravel Cloud)
- **Branch**: develop (auto-deploys on push)

---

## Deployment Issues & Troubleshooting

### Issue: Login Returns 500 Error After Deployment

**Symptoms**:
- Login page loads successfully
- Health endpoint (`/api/health`) responds with 200
- Login attempt shows 500 error in iframe

**Root Causes** (in order of likelihood):

1. **Database not initialized** - Migrations haven't run
2. **Seeding failed** - Demo users not created in database
3. **Deploy command failing** - `migrate:fresh --seed --force` not executing properly

### Current Deploy Command

The deployment is configured to run:
```bash
php artisan migrate:fresh --seed --force
```

**Problem**: This command:
- Drops ALL tables (destructive)
- Rebuilds schema from migrations
- Seeds with demo data
- If migrations fail at any point, entire command fails

### Recommended Solution

Change the deploy command to be more resilient:

```bash
# Option 1: Safe migration + separate seeding
php artisan migrate --force && php artisan db:seed --force

# Option 2: Migrate fresh with error handling
php artisan migrate:fresh --seed --force || php artisan migrate --force
```

---

## Manual Deployment Steps

If automated deployments are failing:

### 1. SSH into Laravel Cloud (if available)

```bash
# Access the server
cd /home/laravel/shillings-develop/current

# Run migrations
php artisan migrate --force

# Seed database with demo data
php artisan db:seed --force

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### 2. Via Laravel Cloud Commands (if available)

In the Laravel Cloud dashboard, use the "Commands" tab to execute:

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan cache:clear
```

---

## Database Initialization

### Tables Created by Migrations

The application requires these core tables:

1. **Core**: users, companies, company_user, currencies, account_types
2. **Accounting**: accounts, transactions, transaction_splits, account_balances
3. **Configuration**: roles, permissions, model_has_roles, model_has_permissions
4. **Features**: contacts, documents, budgets, taxes, etc.

### Seeded Demo Data

DatabaseSeeder creates:
- 6 test users (superadmin, admin, accountant, bookkeeper, viewer, owner)
- 1 demo user: `samone@shillings.app` / `Shillings@2026!`
- Each user attached to a test company
- Core permissions and roles

### If Seeding Fails

If you see errors during seeding (especially `Class "Faker\Factory" not found`):

**Fix**: The UserFactory now uses `Str::random()` instead of Faker for test data generation. This should work in all environments.

If issues persist, manually create a test user:

```php
php artisan tinker
> $user = App\Models\User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password'),
    'email_verified_at' => now(),
]);
> $company = App\Models\Company::create(['name' => 'Test Company', 'currency_id' => 1]);
> $user->companies()->attach($company->id, ['role' => 'member']);
> $user->assignRole('owner');
```

---

## Login Issues After Deployment

If login still returns 500 after successful deployment:

### 1. Verify Database State

```bash
php artisan tinker
> User::count()  # Should return 7+ (6 test users + 1 demo user)
> Company::count()  # Should return 6+ (one per test user)
> Role::count()  # Should return 5+ (super-admin, admin, etc.)
```

### 2. Check User-Company Relationship

```bash
php artisan tinker
> $user = User::where('email', 'samone@shillings.app')->first();
> $user->companies()->count()  # Should return 1+
```

**Error**: If user has 0 companies, that's why login redirects to onboarding and causes 500!

**Fix**:
```bash
php artisan tinker
> $user = User::where('email', 'samone@shillings.app')->first();
> $company = Company::first();
> $user->companies()->attach($company->id, ['role' => 'member']);
```

### 3. Check Filament Configuration

Ensure `app/Providers/Filament/AdminPanelProvider.php` has:
- `.spa()` mode enabled for SPA frontend
- Correct middleware stack
- Authentication properly configured

---

## Deployment Checklist

Before deploying to production:

- [ ] All 271 tests passing locally
- [ ] PHP 8.5+ compatible (no Faker direct instantiation)
- [ ] Migrations don't use forward-referencing foreign keys
- [ ] UserFactory doesn't rely on external dependencies
- [ ] Database seeders properly handle many-to-many relationships
- [ ] Environment variables configured in Laravel Cloud
- [ ] Deploy command set to: `php artisan migrate --force && php artisan db:seed --force`
- [ ] Verified users have associated companies after seeding
- [ ] Cache cleared after deployment

---

## Key Files

- **Migrations**: `database/migrations/`
- **Seeders**: `database/seeders/`
- **Factories**: `database/factories/UserFactory.php`
- **Filament Config**: `app/Providers/Filament/AdminPanelProvider.php`
- **Models**: `app/Models/` (especially User.php and Company.php)

---

## Contact & Support

For deployment issues:

1. Check the deploy command in Laravel Cloud dashboard
2. Review this guide's troubleshooting section
3. Check the application logs if accessible
4. Verify database state with tinker commands above

**Demo Credentials** (after successful deployment):
- Email: `samone@shillings.app`
- Password: `Shillings@2026!`
- URL: `https://shillings-develop-x0styg.laravel.cloud/admin/login`
