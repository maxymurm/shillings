# Shillings - Modern Accounting Application

**Laravel + Filament conversion of GnuCash double-entry accounting software**

## Overview

Shillings is a modern web-based accounting application that brings the power of GnuCash's double-entry bookkeeping to the web. Built with Laravel 12 and Filament 4.3.

## Features

- Double-entry bookkeeping
- Multi-currency support
- Financial reports and charts
- Account reconciliation
- Transaction management
- Budgeting and forecasting
- Import/Export capabilities

## Tech Stack

- **Backend:** Laravel 12 + PHP 8.3
- **Admin Panel:** Filament 4.3
- **Database:** PostgreSQL 15
- **Frontend:** Livewire + Tailwind CSS

## Installation

`ash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
`

## Development

`ash
composer dev
`

## License

MIT License - Based on GnuCash (GPL)

## Credits

Inspired by [GnuCash](https://github.com/Gnucash/gnucash)
