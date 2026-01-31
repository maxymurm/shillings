# Shillings - Modern Accounting Application

**Laravel + Filament conversion of GnuCash double-entry accounting software**

## Overview

Shillings is a modern web-based accounting application that brings the power of GnuCash's double-entry bookkeeping to the web. Built with Laravel 12 and Filament 4.3.

## Features

- Double-entry bookkeeping
- Multi-currency support
- Multi-company support
- Financial reports and charts
- Account reconciliation
- Transaction management
- Budgeting and forecasting
- Import/Export capabilities
- REST API with Laravel Sanctum

## Tech Stack

- **Backend:** Laravel 12 + PHP 8.3
- **Admin Panel:** Filament 4.3
- **Database:** PostgreSQL 16
- **Frontend:** Livewire + Tailwind CSS
- **API Auth:** Laravel Sanctum

## Requirements

- PHP 8.2+
- Composer 2.x
- PostgreSQL 15+ (or use Herd's built-in PostgreSQL)
- Node.js 18+ & npm

## Installation

```bash
# Clone the repository
git clone https://github.com/maxymurm/shillings.git
cd shillings

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database in .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=shillings
# DB_USERNAME=postgres
# DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# (Optional) Seed the database with sample data
php artisan db:seed

# Install Node dependencies
npm install

# Build frontend assets
npm run build
```

## Development

```bash
# Start development server (Laravel, Queue, Vite)
composer dev

# Or run individually
php artisan serve
npm run dev
```

## Admin Panel

Access the admin panel at `/admin`. Create your first admin user:

```bash
php artisan make:filament-user
```

## API Documentation

The API is available at `/api/v1/`. Authentication is handled via Laravel Sanctum tokens.

## Testing

```bash
# Run all tests
composer test

# Run with coverage
php artisan test --coverage
```

## License

MIT License

## Credits

- [GnuCash](https://github.com/Gnucash/gnucash) - Accounting model inspiration
- [Akaunting](https://github.com/akaunting/akaunting) - UX inspiration
- [Laravel](https://laravel.com) - Framework
- [Filament](https://filamentphp.com) - Admin panel
