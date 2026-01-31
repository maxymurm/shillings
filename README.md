# Shillings - Offline-First Accounting

A modern, offline-first double-entry bookkeeping application built with Laravel 12 and Filament 4, inspired by GnuCash's precision accounting principles.

[![CI/CD Pipeline](https://github.com/maxymurm/shillings/actions/workflows/ci.yml/badge.svg)](https://github.com/maxymurm/shillings/actions)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

## Features

- 🔢 **Double-Entry Bookkeeping** - GnuCash-compatible precision arithmetic using fractions
- 🏢 **Multi-Company Support** - Manage multiple businesses from one installation
- 💱 **Multi-Currency** - 50+ currencies with proper exchange rate handling
- 📊 **Financial Reports** - Trial Balance, Balance Sheet, Income Statement, Cash Flow
- 🔌 **REST API** - Full API with Sanctum authentication
- 🌐 **Offline-First** - Works without internet (coming soon)
- 🎨 **Modern UI** - Beautiful Filament 4 admin panel with dark mode

## Tech Stack

- **Backend:** Laravel 12 + PHP 8.3
- **Admin Panel:** Filament 4.3
- **Database:** PostgreSQL 15+ (production) / SQLite (development)
- **Frontend:** Livewire 3 + Tailwind CSS
- **API Auth:** Laravel Sanctum

## Requirements

- PHP 8.3+
- Composer 2.x
- Node.js 20+
- PostgreSQL 15+ (production) or SQLite (development)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/maxymurm/shillings.git
cd shillings
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database credentials:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

Or for PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=shillings
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Run Migrations and Seeders

```bash
php artisan migrate --seed
```

This will create:
- 50+ currencies
- 5 standard account types
- Default roles and permissions

### 5. Create Admin User

```bash
php artisan make:filament-user
```

### 6. Build Assets

```bash
npm run build
```

### 7. Start Development Server

```bash
php artisan serve
```

Visit `http://localhost:8000/admin` to access the admin panel.

## Development

### Running Tests

```bash
# Run all tests
php artisan test

# Run with parallel execution
php artisan test --parallel

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

### Code Quality

```bash
# Fix code style
vendor/bin/pint

# Static analysis
vendor/bin/phpstan analyse
```

### Fresh Database

```bash
php artisan migrate:fresh --seed
```

## Architecture

### Database Schema

```
currencies
    └── companies (has default_currency)
            ├── account_types
            └── accounts (hierarchical tree)
                    └── splits
                            └── transactions
```

### Double-Entry System

Shillings uses GnuCash-style split transactions:

- Each transaction has multiple splits
- Splits use fraction-based amounts (`amount_num`/`amount_denom`)
- Total debits must equal total credits
- Actions: `DEBIT` or `CREDIT`

### Account Types

| Type | Normal Balance | Examples |
|------|----------------|----------|
| ASSET | Debit | Bank, Inventory, Equipment |
| LIABILITY | Credit | Loans, Credit Cards, Payables |
| EQUITY | Credit | Owner's Equity, Retained Earnings |
| INCOME | Credit | Sales, Interest Income |
| EXPENSE | Debit | Rent, Utilities, Salaries |

## API Documentation

See [API_DOCUMENTATION.md](docs/api/API_DOCUMENTATION.md) for complete API reference.

### Quick Start

```bash
# Register
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name": "John", "email": "john@example.com", "password": "password", "password_confirmation": "password"}'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "john@example.com", "password": "password"}'
```

## Deployment

See [FORGE_DEPLOYMENT.md](docs/deployment/FORGE_DEPLOYMENT.md) for production deployment guide.

### Quick Deploy Checklist

- [ ] Configure PostgreSQL database
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Run `php artisan config:cache`
- [ ] Configure Redis for cache/queue
- [ ] Set up SSL certificate
- [ ] Configure queue worker
- [ ] Enable scheduler

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development guidelines.

### Development Workflow

1. Fork the repository
2. Create feature branch: `git checkout -b feature/my-feature`
3. Make changes and add tests
4. Run tests: `php artisan test`
5. Run code quality checks: `vendor/bin/pint && vendor/bin/phpstan analyse`
6. Commit with conventional commits: `git commit -m "feat: add new feature"`
7. Push and create Pull Request

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

- **Developer**: Maxwell Murunga ([@maxymurm](https://github.com/maxymurm))
- **Company**: Advent Digital
- **Inspiration**: [GnuCash](https://www.gnucash.org/) for accounting principles

## Support

- 📖 [Documentation](docs/)
- 🐛 [Issue Tracker](https://github.com/maxymurm/shillings/issues)
- 📋 [Project Board](https://github.com/users/maxymurm/projects/4)
