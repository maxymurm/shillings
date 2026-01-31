# Shillings - Issues Backlog

**Generated:** January 30, 2026  
**Purpose:** Complete backlog of GitHub issues for project board  
**Total Issues:** 180+

---

## 📋 How to Use This File

This file contains all planned issues organized by phase and milestone. Each issue includes:
- **Title** - Clear, actionable title
- **Labels** - For filtering and organization
- **Milestone** - Which milestone it belongs to
- **Priority** - P0 (critical), P1 (high), P2 (medium), P3 (low)
- **Estimate** - Time estimate in hours
- **Description** - Acceptance criteria and details

### Creating Issues
Run the `create_issues.ps1` script to automatically create these issues in GitHub.

---

## 🏷️ Labels Reference

### Type Labels
- `type:feature` - New functionality
- `type:bug` - Bug fix
- `type:chore` - Maintenance task
- `type:docs` - Documentation
- `type:test` - Testing
- `type:refactor` - Code refactoring

### Component Labels
- `component:backend` - Laravel/API
- `component:frontend` - Web UI
- `component:mobile` - Compose Multiplatform
- `component:database` - Database/migrations
- `component:sync` - Offline/sync engine
- `component:auth` - Authentication

### Priority Labels
- `priority:critical` - P0 - Must have for phase
- `priority:high` - P1 - Important
- `priority:medium` - P2 - Nice to have
- `priority:low` - P3 - Can defer

### Status Labels
- `status:ready` - Ready to work on
- `status:blocked` - Blocked by dependency
- `status:in-progress` - Currently being worked on
- `status:review` - In code review

---

# Phase 1: Foundation & Core Accounting

## Milestone 1.1: Project Setup

### Issue #1: Initialize Laravel 12 Project
**Labels:** `type:chore`, `component:backend`, `priority:critical`  
**Milestone:** 1.1 - Project Setup  
**Estimate:** 2h

**Description:**
Create new Laravel 12 project with recommended configuration.

**Acceptance Criteria:**
- [ ] Laravel 12 installed via Composer
- [ ] `.env.example` configured with all needed variables
- [ ] Application key generated
- [ ] Project runs locally with `php artisan serve`
- [ ] README updated with setup instructions

---

### Issue #2: Configure PostgreSQL Database
**Labels:** `type:chore`, `component:database`, `priority:critical`  
**Milestone:** 1.1 - Project Setup  
**Estimate:** 1h

**Description:**
Set up PostgreSQL database connection and verify connectivity.

**Acceptance Criteria:**
- [ ] PostgreSQL database created
- [ ] Database credentials in `.env`
- [ ] Connection verified with `php artisan migrate`
- [ ] UUID extension enabled if needed

---

### Issue #3: Install and Configure Filament 4.3
**Labels:** `type:chore`, `component:frontend`, `priority:critical`  
**Milestone:** 1.1 - Project Setup  
**Estimate:** 2h

**Description:**
Install Filament admin panel framework and configure basic settings.

**Acceptance Criteria:**
- [ ] Filament installed via Composer
- [ ] Admin panel accessible at `/admin`
- [ ] Default user created for testing
- [ ] Basic theme configuration applied

---

### Issue #4: Set Up Git Repository and Branching Strategy
**Labels:** `type:chore`, `priority:critical`  
**Milestone:** 1.1 - Project Setup  
**Estimate:** 1h

**Description:**
Initialize Git repository with proper branching strategy.

**Acceptance Criteria:**
- [ ] Git repository initialized
- [ ] `.gitignore` properly configured
- [ ] Main branch protected
- [ ] Branching strategy documented (main, develop, feature/*)
- [ ] Initial commit made

---

### Issue #5: Configure GitHub Actions CI/CD
**Labels:** `type:chore`, `priority:high`  
**Milestone:** 1.1 - Project Setup  
**Estimate:** 3h

**Description:**
Set up GitHub Actions for continuous integration.

**Acceptance Criteria:**
- [ ] Workflow file created (`.github/workflows/ci.yml`)
- [ ] Runs on push to main and PRs
- [ ] Runs PHP linting (Pint)
- [ ] Runs PHPUnit tests
- [ ] Runs PHPStan static analysis
- [ ] Build status badge in README

---

### Issue #6: Configure Code Quality Tools
**Labels:** `type:chore`, `component:backend`, `priority:high`  
**Milestone:** 1.1 - Project Setup  
**Estimate:** 2h

**Description:**
Set up Laravel Pint, PHPStan, and other code quality tools.

**Acceptance Criteria:**
- [ ] Laravel Pint installed and configured
- [ ] PHPStan installed with Level 5+ config
- [ ] Pre-commit hooks set up (optional)
- [ ] VS Code settings for formatting
- [ ] `composer lint` and `composer analyse` scripts

---

### Issue #7: Set Up Development Environment Documentation
**Labels:** `type:docs`, `priority:medium`  
**Milestone:** 1.1 - Project Setup  
**Estimate:** 2h

**Description:**
Document complete development environment setup.

**Acceptance Criteria:**
- [ ] Prerequisites documented (PHP, Composer, Node, PostgreSQL)
- [ ] Step-by-step setup instructions
- [ ] Common issues and solutions
- [ ] VS Code recommended extensions
- [ ] Environment variables explained

---

## Milestone 1.2: Database Schema

### Issue #8: Create Companies Migration and Model
**Labels:** `type:feature`, `component:database`, `component:backend`, `priority:critical`  
**Milestone:** 1.2 - Database Schema  
**Estimate:** 3h

**Description:**
Create the companies table for multi-tenancy support.

**Acceptance Criteria:**
- [ ] Migration creates `companies` table
- [ ] Fields: id (UUID), name, currency_code, fiscal_year_start, settings (JSON), timestamps, soft deletes
- [ ] Company model with fillable, casts
- [ ] Factory for testing
- [ ] Seeder with sample company

**Schema:**
```php
Schema::create('companies', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->string('currency_code', 3)->default('USD');
    $table->date('fiscal_year_start')->nullable();
    $table->json('settings')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

---

### Issue #9: Create Users Table with Company Relationship
**Labels:** `type:feature`, `component:database`, `component:backend`, `priority:critical`  
**Milestone:** 1.2 - Database Schema  
**Estimate:** 2h

**Description:**
Modify default users table to support multi-company access.

**Acceptance Criteria:**
- [ ] Users can belong to multiple companies (pivot table)
- [ ] `company_user` pivot with role
- [ ] Current company preference stored
- [ ] User model relationships
- [ ] Factory updated

---

### Issue #10: Create Commodities Migration and Model
**Labels:** `type:feature`, `component:database`, `component:backend`, `priority:critical`  
**Milestone:** 1.2 - Database Schema  
**Estimate:** 3h

**Description:**
Create commodities table for currencies and securities (GnuCash model).

**Acceptance Criteria:**
- [ ] Migration creates `commodities` table
- [ ] Fields: id (UUID), namespace, mnemonic, full_name, fraction, quote_source, metadata (JSON)
- [ ] Unique constraint on (namespace, mnemonic)
- [ ] Commodity model
- [ ] Factory and seeder with common currencies (USD, EUR, GBP, KES, etc.)

---

### Issue #11: Create Accounts Migration and Model
**Labels:** `type:feature`, `component:database`, `component:backend`, `priority:critical`  
**Milestone:** 1.2 - Database Schema  
**Estimate:** 4h

**Description:**
Create hierarchical accounts table (chart of accounts).

**Acceptance Criteria:**
- [ ] Migration creates `accounts` table
- [ ] Fields: id, company_id, parent_id, code, name, type, description, notes, commodity_id, opening_balance, is_placeholder, is_hidden, tax_related, path, level, metadata, timestamps, soft deletes, sync fields
- [ ] Account types enum: ASSET, BANK, CASH, CREDIT_CARD, LIABILITY, EQUITY, INCOME, EXPENSE, STOCK, MUTUAL_FUND, RECEIVABLE, PAYABLE
- [ ] Account model with parent/children relationships
- [ ] Scopes for type filtering
- [ ] Factory and seeder with sample chart of accounts

---

### Issue #12: Create Transactions Migration and Model
**Labels:** `type:feature`, `component:database`, `component:backend`, `priority:critical`  
**Milestone:** 1.2 - Database Schema  
**Estimate:** 3h

**Description:**
Create transactions table (transaction headers).

**Acceptance Criteria:**
- [ ] Migration creates `transactions` table
- [ ] Fields: id, company_id, transaction_number, description, notes, posted_at, entered_at, currency_id, is_balanced, metadata, created_by, timestamps, soft deletes, sync fields
- [ ] Transaction model with relationships (company, currency, splits, creator)
- [ ] Factory for testing

---

### Issue #13: Create Splits Migration and Model
**Labels:** `type:feature`, `component:database`, `component:backend`, `priority:critical`  
**Milestone:** 1.2 - Database Schema  
**Estimate:** 4h

**Description:**
Create splits table - the fundamental accounting unit (GnuCash model).

**Acceptance Criteria:**
- [ ] Migration creates `splits` table
- [ ] Fields: id, transaction_id, account_id, amount_numerator, amount_denominator, value_numerator, value_denominator, memo, action, reconcile_state, reconcile_date, lot_id, metadata, timestamps, sync fields
- [ ] Split model with relationships
- [ ] Accessors for computed amount/value as decimals
- [ ] Reconcile state constants (n, c, y, f, v)
- [ ] Factory for testing

---

### Issue #14: Create Prices Migration and Model
**Labels:** `type:feature`, `component:database`, `component:backend`, `priority:critical`  
**Milestone:** 1.2 - Database Schema  
**Estimate:** 2h

**Description:**
Create prices table for exchange rates and stock prices.

**Acceptance Criteria:**
- [ ] Migration creates `prices` table
- [ ] Fields: id, commodity_id, currency_id, date, source, type, value_numerator, value_denominator, timestamps
- [ ] Unique constraint on (commodity_id, currency_id, date, source, type)
- [ ] Price model with relationships
- [ ] Factory and seeder with sample exchange rates

---

### Issue #15: Create Categories Migration and Model
**Labels:** `type:feature`, `component:database`, `component:backend`, `priority:high`  
**Milestone:** 1.2 - Database Schema  
**Estimate:** 2h

**Description:**
Create hierarchical categories for items and transactions.

**Acceptance Criteria:**
- [ ] Migration creates `categories` table
- [ ] Fields: id, company_id, parent_id, type, name, color, is_enabled, path, level, timestamps
- [ ] Category types: income, expense, item
- [ ] Category model with hierarchy
- [ ] Factory and seeder

---

### Issue #16: Create Settings Migration and Model
**Labels:** `type:feature`, `component:database`, `component:backend`, `priority:high`  
**Milestone:** 1.2 - Database Schema  
**Estimate:** 2h

**Description:**
Create key-value settings table for company configuration.

**Acceptance Criteria:**
- [ ] Migration creates `settings` table
- [ ] Fields: id, company_id, key, value (JSON), timestamps
- [ ] Unique constraint on (company_id, key)
- [ ] Setting model with helper methods
- [ ] Settings service for easy access

---

### Issue #17: Create Activity Log Migration and Model
**Labels:** `type:feature`, `component:database`, `component:backend`, `priority:medium`  
**Milestone:** 1.2 - Database Schema  
**Estimate:** 2h

**Description:**
Create audit trail/activity log table.

**Acceptance Criteria:**
- [ ] Migration creates `activity_logs` table
- [ ] Fields: id, company_id, user_id, subject_type, subject_id, event, properties (JSON), timestamps
- [ ] ActivityLog model
- [ ] Trait for automatic logging on models
- [ ] Index for efficient querying

---

### Issue #18: Create Database Indexes for Performance
**Labels:** `type:chore`, `component:database`, `priority:high`  
**Milestone:** 1.2 - Database Schema  
**Estimate:** 2h

**Description:**
Add proper indexes to all tables for query performance.

**Acceptance Criteria:**
- [ ] Indexes on all foreign keys
- [ ] Indexes on commonly queried fields (company_id, posted_at, type)
- [ ] Composite indexes where beneficial
- [ ] Document index strategy

---

### Issue #19: Create Model Factories for All Models
**Labels:** `type:test`, `component:backend`, `priority:high`  
**Milestone:** 1.2 - Database Schema  
**Estimate:** 3h

**Description:**
Create comprehensive factories for all models for testing.

**Acceptance Criteria:**
- [ ] Factory for each model
- [ ] Realistic fake data
- [ ] States for different scenarios
- [ ] Relationships properly handled

---

### Issue #20: Create Database Seeders
**Labels:** `type:chore`, `component:database`, `priority:high`  
**Milestone:** 1.2 - Database Schema  
**Estimate:** 3h

**Description:**
Create seeders for development and demo data.

**Acceptance Criteria:**
- [ ] Main DatabaseSeeder orchestrates all
- [ ] CurrencySeeder with 50+ currencies
- [ ] DefaultAccountSeeder with standard chart of accounts
- [ ] DemoCompanySeeder with sample transactions
- [ ] Can run independently or together

---

## Milestone 1.3: Core Models & Services

### Issue #21: Implement Company Model with Multi-Tenancy
**Labels:** `type:feature`, `component:backend`, `priority:critical`  
**Milestone:** 1.3 - Core Models & Services  
**Estimate:** 4h

**Description:**
Complete Company model with multi-tenancy trait and scoping.

**Acceptance Criteria:**
- [ ] BelongsToCompany trait for all company-scoped models
- [ ] Global scope for automatic company filtering
- [ ] Company switching in session
- [ ] Current company helper/facade
- [ ] Unit tests

---

### Issue #22: Implement Account Model with Hierarchy
**Labels:** `type:feature`, `component:backend`, `priority:critical`  
**Milestone:** 1.3 - Core Models & Services  
**Estimate:** 5h

**Description:**
Complete Account model with hierarchical relationships and balance calculations.

**Acceptance Criteria:**
- [ ] Parent/children relationships
- [ ] Ancestors/descendants methods
- [ ] Path and level auto-calculation
- [ ] getBalance() method
- [ ] getBalanceAsOf(date) method
- [ ] Account type validation
- [ ] Unit tests for hierarchy and balances

---

### Issue #23: Implement Transaction Model with Validation
**Labels:** `type:feature`, `component:backend`, `priority:critical`  
**Milestone:** 1.3 - Core Models & Services  
**Estimate:** 4h

**Description:**
Complete Transaction model with double-entry validation.

**Acceptance Criteria:**
- [ ] Relationship to splits
- [ ] isBalanced() method
- [ ] getTotal() method
- [ ] Auto-set is_balanced flag
- [ ] Prevent saving unbalanced transactions
- [ ] Unit tests

---

### Issue #24: Implement Split Model with Precision Arithmetic
**Labels:** `type:feature`, `component:backend`, `priority:critical`  
**Milestone:** 1.3 - Core Models & Services  
**Estimate:** 5h

**Description:**
Complete Split model with numerator/denominator arithmetic.

**Acceptance Criteria:**
- [ ] Precision arithmetic helpers (no float issues)
- [ ] getAmount() and getValue() accessors
- [ ] setAmount() and setValue() mutators
- [ ] Currency conversion when needed
- [ ] Reconciliation state management
- [ ] Unit tests for precision

---

### Issue #25: Implement Commodity Model and Currency Support
**Labels:** `type:feature`, `component:backend`, `priority:critical`  
**Milestone:** 1.3 - Core Models & Services  
**Estimate:** 3h

**Description:**
Complete Commodity model with currency helpers.

**Acceptance Criteria:**
- [ ] isCurrency() method
- [ ] isStock() method
- [ ] Format amount for display
- [ ] Get symbol
- [ ] Unit tests

---

### Issue #26: Create AccountService for Balance Calculations
**Labels:** `type:feature`, `component:backend`, `priority:critical`  
**Milestone:** 1.3 - Core Models & Services  
**Estimate:** 4h

**Description:**
Service class for complex account operations.

**Acceptance Criteria:**
- [ ] getBalance(account) - current balance
- [ ] getBalanceAsOf(account, date) - historical balance
- [ ] getBalanceInCurrency(account, currency) - converted balance
- [ ] getSubtreeBalance(account) - include children
- [ ] Efficient queries with caching
- [ ] Unit tests

---

### Issue #27: Create TransactionService for Double-Entry Operations
**Labels:** `type:feature`, `component:backend`, `priority:critical`  
**Milestone:** 1.3 - Core Models & Services  
**Estimate:** 5h

**Description:**
Service class for transaction operations.

**Acceptance Criteria:**
- [ ] createTransaction(data) with splits
- [ ] validateTransaction() - ensure balanced
- [ ] reverseTransaction() - create reversing entry
- [ ] voidTransaction() - mark as void
- [ ] Atomic database operations
- [ ] Event dispatching
- [ ] Unit tests

---

### Issue #28: Create CurrencyService for Exchange Rates
**Labels:** `type:feature`, `component:backend`, `priority:high`  
**Milestone:** 1.3 - Core Models & Services  
**Estimate:** 4h

**Description:**
Service class for currency conversion and rates.

**Acceptance Criteria:**
- [ ] convert(amount, from, to, date) - convert currencies
- [ ] getRate(from, to, date) - get exchange rate
- [ ] fetchRates() - update from external API
- [ ] Rate caching
- [ ] Unit tests

---

### Issue #29: Create PrecisionMath Helper Class
**Labels:** `type:feature`, `component:backend`, `priority:critical`  
**Milestone:** 1.3 - Core Models & Services  
**Estimate:** 3h

**Description:**
Helper class for arbitrary precision arithmetic (GnuCash style).

**Acceptance Criteria:**
- [ ] add(a, b) - precise addition
- [ ] subtract(a, b) - precise subtraction
- [ ] multiply(a, b) - precise multiplication
- [ ] divide(a, b) - precise division
- [ ] toFraction(decimal) - convert to numerator/denominator
- [ ] fromFraction(num, denom) - convert to decimal
- [ ] Unit tests with edge cases

---

### Issue #30: Implement Model Events and Observers
**Labels:** `type:feature`, `component:backend`, `priority:high`  
**Milestone:** 1.3 - Core Models & Services  
**Estimate:** 3h

**Description:**
Set up model observers for automatic actions.

**Acceptance Criteria:**
- [ ] AccountObserver - update path/level on save
- [ ] TransactionObserver - validate balance, log activity
- [ ] SplitObserver - log activity
- [ ] Events for significant actions
- [ ] Unit tests

---

## Milestone 1.4: Authentication & Authorization

### Issue #31: Implement User Registration
**Labels:** `type:feature`, `component:auth`, `priority:critical`  
**Milestone:** 1.4 - Authentication & Authorization  
**Estimate:** 3h

**Description:**
User registration flow with company creation.

**Acceptance Criteria:**
- [ ] Registration form (name, email, password)
- [ ] Create user account
- [ ] Create default company
- [ ] Send verification email
- [ ] Redirect to dashboard
- [ ] Unit/feature tests

---

### Issue #32: Implement User Login/Logout
**Labels:** `type:feature`, `component:auth`, `priority:critical`  
**Milestone:** 1.4 - Authentication & Authorization  
**Estimate:** 2h

**Description:**
Standard login/logout functionality.

**Acceptance Criteria:**
- [ ] Login form (email, password, remember me)
- [ ] Session management
- [ ] Logout with session invalidation
- [ ] Redirect to intended URL
- [ ] Feature tests

---

### Issue #33: Implement Password Reset
**Labels:** `type:feature`, `component:auth`, `priority:critical`  
**Milestone:** 1.4 - Authentication & Authorization  
**Estimate:** 2h

**Description:**
Password reset via email flow.

**Acceptance Criteria:**
- [ ] Forgot password form
- [ ] Send reset email
- [ ] Reset password form
- [ ] Token expiration
- [ ] Feature tests

---

### Issue #34: Implement Email Verification
**Labels:** `type:feature`, `component:auth`, `priority:high`  
**Milestone:** 1.4 - Authentication & Authorization  
**Estimate:** 2h

**Description:**
Email verification for new accounts.

**Acceptance Criteria:**
- [ ] Verification email sent on registration
- [ ] Verify link handler
- [ ] Resend verification option
- [ ] Middleware for verified users
- [ ] Feature tests

---

### Issue #35: Implement Role-Based Access Control (RBAC)
**Labels:** `type:feature`, `component:auth`, `priority:critical`  
**Milestone:** 1.4 - Authentication & Authorization  
**Estimate:** 5h

**Description:**
Role and permission system using Spatie/laravel-permission.

**Acceptance Criteria:**
- [ ] Install spatie/laravel-permission
- [ ] Default roles: owner, admin, accountant, viewer
- [ ] Role assignment to users per company
- [ ] Role checking middleware
- [ ] Unit tests

---

### Issue #36: Implement Permission System
**Labels:** `type:feature`, `component:auth`, `priority:critical`  
**Milestone:** 1.4 - Authentication & Authorization  
**Estimate:** 4h

**Description:**
Granular permissions for all resources.

**Acceptance Criteria:**
- [ ] Permissions: view, create, update, delete for each resource
- [ ] Permission groups (accounts, transactions, reports, settings)
- [ ] Permission checking in policies
- [ ] Filament integration
- [ ] Unit tests

---

### Issue #37: Implement Company-Level Isolation
**Labels:** `type:feature`, `component:auth`, `priority:critical`  
**Milestone:** 1.4 - Authentication & Authorization  
**Estimate:** 3h

**Description:**
Ensure users can only access their company data.

**Acceptance Criteria:**
- [ ] Global scope on all company models
- [ ] Middleware to verify company access
- [ ] Company switching with permission check
- [ ] Security tests

---

### Issue #38: Implement API Authentication (Sanctum)
**Labels:** `type:feature`, `component:auth`, `component:backend`, `priority:critical`  
**Milestone:** 1.4 - Authentication & Authorization  
**Estimate:** 3h

**Description:**
API authentication using Laravel Sanctum.

**Acceptance Criteria:**
- [ ] Sanctum installed and configured
- [ ] API token generation
- [ ] Token abilities/scopes
- [ ] Token revocation
- [ ] API auth middleware
- [ ] Feature tests

---

## Milestone 1.5: Basic API Endpoints

### Issue #39: Create Companies API Endpoints
**Labels:** `type:feature`, `component:backend`, `priority:critical`  
**Milestone:** 1.5 - Basic API Endpoints  
**Estimate:** 3h

**Description:**
RESTful API for company management.

**Acceptance Criteria:**
- [ ] GET /api/companies - list user's companies
- [ ] GET /api/companies/{id} - show company
- [ ] POST /api/companies - create company
- [ ] PUT /api/companies/{id} - update company
- [ ] DELETE /api/companies/{id} - soft delete
- [ ] API resource/transformer
- [ ] Feature tests

---

### Issue #40: Create Accounts API Endpoints
**Labels:** `type:feature`, `component:backend`, `priority:critical`  
**Milestone:** 1.5 - Basic API Endpoints  
**Estimate:** 4h

**Description:**
RESTful API for account management.

**Acceptance Criteria:**
- [ ] GET /api/accounts - list accounts (with tree option)
- [ ] GET /api/accounts/{id} - show with balance
- [ ] POST /api/accounts - create account
- [ ] PUT /api/accounts/{id} - update account
- [ ] DELETE /api/accounts/{id} - soft delete (if no transactions)
- [ ] Filters: type, parent, search
- [ ] API resource with relationships
- [ ] Feature tests

---

### Issue #41: Create Transactions API Endpoints
**Labels:** `type:feature`, `component:backend`, `priority:critical`  
**Milestone:** 1.5 - Basic API Endpoints  
**Estimate:** 5h

**Description:**
RESTful API for transaction management.

**Acceptance Criteria:**
- [ ] GET /api/transactions - list with filters
- [ ] GET /api/transactions/{id} - show with splits
- [ ] POST /api/transactions - create with splits
- [ ] PUT /api/transactions/{id} - update
- [ ] DELETE /api/transactions/{id} - soft delete
- [ ] Filters: account, date range, type, search
- [ ] Pagination
- [ ] API resource
- [ ] Feature tests

---

### Issue #42: Create Commodities API Endpoints
**Labels:** `type:feature`, `component:backend`, `priority:high`  
**Milestone:** 1.5 - Basic API Endpoints  
**Estimate:** 2h

**Description:**
RESTful API for commodity/currency management.

**Acceptance Criteria:**
- [ ] GET /api/commodities - list all
- [ ] GET /api/commodities/{id} - show
- [ ] POST /api/commodities - create custom
- [ ] Filters: namespace, type (currency/stock)
- [ ] API resource
- [ ] Feature tests

---

### Issue #43: Create Prices API Endpoints
**Labels:** `type:feature`, `component:backend`, `priority:high`  
**Milestone:** 1.5 - Basic API Endpoints  
**Estimate:** 3h

**Description:**
RESTful API for exchange rates and prices.

**Acceptance Criteria:**
- [ ] GET /api/prices - list prices
- [ ] GET /api/prices/latest - latest rates
- [ ] POST /api/prices - add price
- [ ] GET /api/prices/convert - convert amount
- [ ] Filters: commodity, currency, date range
- [ ] API resource
- [ ] Feature tests

---

### Issue #44: Create API Documentation (OpenAPI/Swagger)
**Labels:** `type:docs`, `component:backend`, `priority:high`  
**Milestone:** 1.5 - Basic API Endpoints  
**Estimate:** 4h

**Description:**
Generate OpenAPI documentation for all endpoints.

**Acceptance Criteria:**
- [ ] Install L5-Swagger or similar
- [ ] Document all endpoints
- [ ] Document request/response schemas
- [ ] Authentication documented
- [ ] Interactive documentation UI
- [ ] Export OpenAPI JSON

---

### Issue #45: Implement API Rate Limiting
**Labels:** `type:feature`, `component:backend`, `priority:high`  
**Milestone:** 1.5 - Basic API Endpoints  
**Estimate:** 2h

**Description:**
Rate limiting to prevent API abuse.

**Acceptance Criteria:**
- [ ] Configure rate limits (60/minute default)
- [ ] Different limits per endpoint type
- [ ] Rate limit headers in response
- [ ] Handle 429 responses gracefully
- [ ] Feature tests

---

## Milestone 1.6: Double-Entry Engine

### Issue #46: Implement Transaction Balance Validation
**Labels:** `type:feature`, `component:backend`, `priority:critical`  
**Milestone:** 1.6 - Double-Entry Engine  
**Estimate:** 4h

**Description:**
Ensure all transactions follow double-entry rules.

**Acceptance Criteria:**
- [ ] Sum of split values must equal zero
- [ ] Validation in service layer
- [ ] Validation in model observer
- [ ] Clear error messages
- [ ] Cannot save unbalanced transaction
- [ ] Unit tests with edge cases

---

### Issue #47: Implement Split Creation with Precision Math
**Labels:** `type:feature`, `component:backend`, `priority:critical`  
**Milestone:** 1.6 - Double-Entry Engine  
**Estimate:** 4h

**Description:**
Create splits with arbitrary precision arithmetic.

**Acceptance Criteria:**
- [ ] Convert decimal input to numerator/denominator
- [ ] Handle multi-currency splits
- [ ] Maintain precision through all operations
- [ ] Rounding only on display
- [ ] Unit tests for precision edge cases

---

### Issue #48: Implement Account Balance Calculation
**Labels:** `type:feature`, `component:backend`, `priority:critical`  
**Milestone:** 1.6 - Double-Entry Engine  
**Estimate:** 4h

**Description:**
Calculate account balances efficiently.

**Acceptance Criteria:**
- [ ] Sum splits for account
- [ ] Include opening balance
- [ ] Handle reconciled vs all splits
- [ ] Balance as of date
- [ ] Efficient query (consider caching)
- [ ] Unit tests

---

### Issue #49: Implement Multi-Currency Transactions
**Labels:** `type:feature`, `component:backend`, `priority:critical`  
**Milestone:** 1.6 - Double-Entry Engine  
**Estimate:** 5h

**Description:**
Support transactions between accounts with different currencies.

**Acceptance Criteria:**
- [ ] Split amount in account currency
- [ ] Split value in transaction currency
- [ ] Exchange rate stored
- [ ] Balance maintained in account currency
- [ ] Reports in any currency
- [ ] Unit tests

---

### Issue #50: Implement Currency Conversion Service
**Labels:** `type:feature`, `component:backend`, `priority:high`  
**Milestone:** 1.6 - Double-Entry Engine  
**Estimate:** 3h

**Description:**
Convert amounts between currencies with historical rates.

**Acceptance Criteria:**
- [ ] Use stored rates from prices table
- [ ] Fallback to nearest date if exact not found
- [ ] External API for live rates (configurable)
- [ ] Caching for performance
- [ ] Unit tests

---

### Issue #51: Implement Transaction Reversal
**Labels:** `type:feature`, `component:backend`, `priority:high`  
**Milestone:** 1.6 - Double-Entry Engine  
**Estimate:** 3h

**Description:**
Create reversing entry for a transaction.

**Acceptance Criteria:**
- [ ] Create new transaction with opposite splits
- [ ] Link to original transaction
- [ ] Maintain audit trail
- [ ] Unit tests

---

### Issue #52: Create Comprehensive Accounting Test Suite
**Labels:** `type:test`, `component:backend`, `priority:critical`  
**Milestone:** 1.6 - Double-Entry Engine  
**Estimate:** 5h

**Description:**
Unit tests covering all accounting scenarios.

**Acceptance Criteria:**
- [ ] Test balanced transactions
- [ ] Test unbalanced rejection
- [ ] Test multi-currency
- [ ] Test precision (0.01 + 0.02 = 0.03)
- [ ] Test balance calculations
- [ ] Test historical balances
- [ ] Test account hierarchy balances
- [ ] Edge cases documented

---

### Issue #53: Create Account Type Behaviors
**Labels:** `type:feature`, `component:backend`, `priority:high`  
**Milestone:** 1.6 - Double-Entry Engine  
**Estimate:** 3h

**Description:**
Implement type-specific behaviors for accounts.

**Acceptance Criteria:**
- [ ] Normal balance direction (debit/credit)
- [ ] Valid transaction types per account type
- [ ] Balance display (positive for assets, etc.)
- [ ] Type-specific validations
- [ ] Unit tests

---

### Issue #54: Implement Opening Balance Handling
**Labels:** `type:feature`, `component:backend`, `priority:high`  
**Milestone:** 1.6 - Double-Entry Engine  
**Estimate:** 3h

**Description:**
Handle opening balances correctly.

**Acceptance Criteria:**
- [ ] Opening balance as special transaction
- [ ] Opening balance equity account
- [ ] Include in balance calculations
- [ ] Can be edited
- [ ] Unit tests

---

---

# Phase 2: Web Application (Filament Admin)

## Milestone 2.1: Filament Setup

### Issue #55: Configure Filament Panel and Navigation
**Labels:** `type:feature`, `component:frontend`, `priority:critical`  
**Milestone:** 2.1 - Filament Setup  
**Estimate:** 3h

**Description:**
Set up main Filament admin panel structure.

**Acceptance Criteria:**
- [ ] Panel at /admin
- [ ] Navigation groups (Banking, Reports, Settings)
- [ ] Icons for each menu item
- [ ] Breadcrumbs configured
- [ ] User menu dropdown

---

### Issue #56: Create Custom Filament Theme
**Labels:** `type:feature`, `component:frontend`, `priority:medium`  
**Milestone:** 2.1 - Filament Setup  
**Estimate:** 3h

**Description:**
Custom theme and branding for Shillings.

**Acceptance Criteria:**
- [ ] Custom primary color
- [ ] Logo in sidebar and login
- [ ] Custom favicon
- [ ] Light/dark mode support
- [ ] Loading indicator customized

---

### Issue #57: Implement Multi-Company Switcher Widget
**Labels:** `type:feature`, `component:frontend`, `priority:critical`  
**Milestone:** 2.1 - Filament Setup  
**Estimate:** 4h

**Description:**
Widget to switch between companies in header.

**Acceptance Criteria:**
- [ ] Dropdown showing user's companies
- [ ] Current company displayed
- [ ] Quick switch without page reload
- [ ] Create new company option
- [ ] Remember last selected

---

### Issue #58: Create Dashboard Layout with Widgets
**Labels:** `type:feature`, `component:frontend`, `priority:high`  
**Milestone:** 2.1 - Filament Setup  
**Estimate:** 4h

**Description:**
Main dashboard with accounting widgets.

**Acceptance Criteria:**
- [ ] Total assets widget
- [ ] Total liabilities widget
- [ ] Net worth widget
- [ ] Recent transactions widget
- [ ] Quick actions (new transaction, etc.)
- [ ] Customizable layout

---

## Milestone 2.2: Account Management

### Issue #59: Create Account Filament Resource
**Labels:** `type:feature`, `component:frontend`, `priority:critical`  
**Milestone:** 2.2 - Account Management  
**Estimate:** 5h

**Description:**
Full CRUD resource for accounts.

**Acceptance Criteria:**
- [ ] List view with balance column
- [ ] Create form with all fields
- [ ] Edit form
- [ ] Delete with confirmation (if no transactions)
- [ ] Bulk actions (hide, enable, disable)
- [ ] Filters (type, hidden, placeholder)

---

### Issue #60: Implement Account Tree View
**Labels:** `type:feature`, `component:frontend`, `priority:high`  
**Milestone:** 2.2 - Account Management  
**Estimate:** 5h

**Description:**
Hierarchical tree view for chart of accounts.

**Acceptance Criteria:**
- [ ] Collapsible tree structure
- [ ] Drag-and-drop reordering
- [ ] Indent levels visible
- [ ] Balance at each level
- [ ] Expand/collapse all

---

### Issue #61: Create Account Type Filter
**Labels:** `type:feature`, `component:frontend`, `priority:medium`  
**Milestone:** 2.2 - Account Management  
**Estimate:** 2h

**Description:**
Filter accounts by type with icons.

**Acceptance Criteria:**
- [ ] Filter dropdown with all types
- [ ] Color-coded type badges
- [ ] Icons for each type
- [ ] Remember filter preference

---

### Issue #62: Implement Account Search
**Labels:** `type:feature`, `component:frontend`, `priority:medium`  
**Milestone:** 2.2 - Account Management  
**Estimate:** 2h

**Description:**
Search accounts by name, code, or description.

**Acceptance Criteria:**
- [ ] Real-time search
- [ ] Highlight matches
- [ ] Search in tree view
- [ ] Search in list view

---

## Milestone 2.3: Transaction Management

### Issue #63: Create Transaction Filament Resource
**Labels:** `type:feature`, `component:frontend`, `priority:critical`  
**Milestone:** 2.3 - Transaction Management  
**Estimate:** 5h

**Description:**
Full CRUD resource for transactions.

**Acceptance Criteria:**
- [ ] List view with key columns
- [ ] Date range filter
- [ ] Account filter
- [ ] Search by description
- [ ] Pagination

---

### Issue #64: Create Transaction Entry Form with Splits
**Labels:** `type:feature`, `component:frontend`, `priority:critical`  
**Milestone:** 2.3 - Transaction Management  
**Estimate:** 8h

**Description:**
Form for creating/editing transactions with multiple splits.

**Acceptance Criteria:**
- [ ] Date picker
- [ ] Description field
- [ ] Dynamic split rows (add/remove)
- [ ] Account selector with search
- [ ] Amount with debit/credit toggle
- [ ] Auto-balance indicator
- [ ] Running total
- [ ] Validation before save

---

### Issue #65: Implement Quick Transaction Entry
**Labels:** `type:feature`, `component:frontend`, `priority:high`  
**Milestone:** 2.3 - Transaction Management  
**Estimate:** 4h

**Description:**
Simplified form for common 2-split transactions.

**Acceptance Criteria:**
- [ ] From/To account dropdowns
- [ ] Single amount field
- [ ] Auto-creates balanced transaction
- [ ] Option to add more splits

---

### Issue #66: Create Account Register View
**Labels:** `type:feature`, `component:frontend`, `priority:high`  
**Milestone:** 2.3 - Transaction Management  
**Estimate:** 5h

**Description:**
Checkbook-style register for single account.

**Acceptance Criteria:**
- [ ] All transactions for account
- [ ] Running balance column
- [ ] Inline editing (optional)
- [ ] Reconciliation checkboxes
- [ ] Date range filter

---

### Issue #67: Implement Transaction Import (CSV)
**Labels:** `type:feature`, `component:frontend`, `priority:high`  
**Milestone:** 2.3 - Transaction Management  
**Estimate:** 6h

**Description:**
Import transactions from CSV file.

**Acceptance Criteria:**
- [ ] File upload
- [ ] Column mapping interface
- [ ] Preview before import
- [ ] Duplicate detection
- [ ] Error reporting
- [ ] Batch import

---

### Issue #68: Create Transaction Templates
**Labels:** `type:feature`, `component:frontend`, `priority:medium`  
**Milestone:** 2.3 - Transaction Management  
**Estimate:** 4h

**Description:**
Save and reuse transaction templates.

**Acceptance Criteria:**
- [ ] Save current transaction as template
- [ ] Template library
- [ ] Apply template to new transaction
- [ ] Edit/delete templates

---

## Milestone 2.4: Reports

### Issue #69: Create Balance Sheet Report
**Labels:** `type:feature`, `component:frontend`, `priority:critical`  
**Milestone:** 2.4 - Reports  
**Estimate:** 6h

**Description:**
Standard balance sheet report.

**Acceptance Criteria:**
- [ ] Assets, Liabilities, Equity sections
- [ ] Account hierarchy maintained
- [ ] As-of date selector
- [ ] Comparison periods
- [ ] Export to PDF/Excel

---

### Issue #70: Create Profit & Loss Report
**Labels:** `type:feature`, `component:frontend`, `priority:critical`  
**Milestone:** 2.4 - Reports  
**Estimate:** 6h

**Description:**
Income statement report.

**Acceptance Criteria:**
- [ ] Revenue section
- [ ] Expense section
- [ ] Net income calculation
- [ ] Date range selector
- [ ] Comparison to prior period
- [ ] Export to PDF/Excel

---

### Issue #71: Create Cash Flow Statement
**Labels:** `type:feature`, `component:frontend`, `priority:high`  
**Milestone:** 2.4 - Reports  
**Estimate:** 6h

**Description:**
Cash flow statement report.

**Acceptance Criteria:**
- [ ] Operating activities
- [ ] Investing activities
- [ ] Financing activities
- [ ] Net cash flow
- [ ] Date range selector
- [ ] Export to PDF/Excel

---

### Issue #72: Create Trial Balance Report
**Labels:** `type:feature`, `component:frontend`, `priority:high`  
**Milestone:** 2.4 - Reports  
**Estimate:** 4h

**Description:**
Trial balance for verification.

**Acceptance Criteria:**
- [ ] All accounts with balances
- [ ] Debit/credit columns
- [ ] Total must balance
- [ ] As-of date selector
- [ ] Export to PDF/Excel

---

### Issue #73: Create General Ledger Report
**Labels:** `type:feature`, `component:frontend`, `priority:high`  
**Milestone:** 2.4 - Reports  
**Estimate:** 5h

**Description:**
Detailed general ledger with all transactions.

**Acceptance Criteria:**
- [ ] Group by account
- [ ] All transactions listed
- [ ] Running balance
- [ ] Date range filter
- [ ] Account filter
- [ ] Export to PDF/Excel

---

### Issue #74: Create Report Export Service
**Labels:** `type:feature`, `component:backend`, `priority:high`  
**Milestone:** 2.4 - Reports  
**Estimate:** 5h

**Description:**
Service for exporting reports to various formats.

**Acceptance Criteria:**
- [ ] PDF export (DomPDF)
- [ ] Excel export (Laravel Excel)
- [ ] CSV export
- [ ] Print-friendly view
- [ ] Proper formatting

---

## Milestone 2.5: Settings & Configuration

### Issue #75: Create Company Settings Page
**Labels:** `type:feature`, `component:frontend`, `priority:high`  
**Milestone:** 2.5 - Settings & Configuration  
**Estimate:** 4h

**Description:**
Company configuration page.

**Acceptance Criteria:**
- [ ] Company name, address
- [ ] Base currency
- [ ] Fiscal year
- [ ] Logo upload
- [ ] Tax settings

---

### Issue #76: Create Currency Management Page
**Labels:** `type:feature`, `component:frontend`, `priority:high`  
**Milestone:** 2.5 - Settings & Configuration  
**Estimate:** 3h

**Description:**
Manage currencies and exchange rates.

**Acceptance Criteria:**
- [ ] List all currencies
- [ ] Add custom currency
- [ ] Set exchange rates
- [ ] Historical rates view
- [ ] Auto-update toggle

---

### Issue #77: Create Category Management Page
**Labels:** `type:feature`, `component:frontend`, `priority:medium`  
**Milestone:** 2.5 - Settings & Configuration  
**Estimate:** 3h

**Description:**
Manage transaction/item categories.

**Acceptance Criteria:**
- [ ] Category tree view
- [ ] Add/edit/delete categories
- [ ] Color picker
- [ ] Enable/disable

---

### Issue #78: Create User Management Page
**Labels:** `type:feature`, `component:frontend`, `priority:high`  
**Milestone:** 2.5 - Settings & Configuration  
**Estimate:** 4h

**Description:**
Manage company users and invitations.

**Acceptance Criteria:**
- [ ] List company users
- [ ] Invite new user
- [ ] Change user role
- [ ] Remove user
- [ ] Pending invitations

---

### Issue #79: Create Role/Permission Management
**Labels:** `type:feature`, `component:frontend`, `priority:high`  
**Milestone:** 2.5 - Settings & Configuration  
**Estimate:** 4h

**Description:**
Manage custom roles and permissions.

**Acceptance Criteria:**
- [ ] List roles
- [ ] Create custom role
- [ ] Assign permissions to role
- [ ] Edit/delete roles
- [ ] Cannot modify built-in roles

---

### Issue #80: Create Default Settings Configuration
**Labels:** `type:feature`, `component:frontend`, `priority:medium`  
**Milestone:** 2.5 - Settings & Configuration  
**Estimate:** 3h

**Description:**
Configure default accounts and behaviors.

**Acceptance Criteria:**
- [ ] Default income account
- [ ] Default expense account
- [ ] Default bank account
- [ ] Date format
- [ ] Number format

---

---

# Phase 3-8: Additional Issues (Summary)

Due to the massive scope, here's a summary of remaining issues by phase. Full details will be added as phases approach.

## Phase 3: Mobile Foundation (30+ issues)
- Compose Multiplatform setup
- Core architecture (Ktor, Room, Koin)
- Authentication screens
- Account/transaction screens
- Company switching
- UI polish

## Phase 4: Offline & Sync Engine (25+ issues)
- Sync protocol implementation
- Server-side sync endpoints
- Mobile offline storage
- Web offline (IndexedDB)
- Conflict resolution
- Cached authentication

## Phase 5: Business Features (30+ issues)
- Contact management
- Invoice creation and management
- Bill management
- A/R and A/P
- Payment processing
- Aging reports

## Phase 6: Advanced Features (25+ issues)
- Budget management
- Bank reconciliation
- Investment tracking
- Scheduled transactions
- Capital gains

## Phase 7: Automation & AI (15+ issues)
- Auto-categorization
- Receipt OCR
- Bank feed integration
- Smart notifications

## Phase 8: Polish & Launch (15+ issues)
- Testing suite
- Performance optimization
- Documentation
- App store submissions
- Launch preparation

---

## 📊 Issue Statistics

| Phase | Issues | Hours | Status |
|-------|--------|-------|--------|
| Phase 1 | 54 | ~180h | Ready |
| Phase 2 | 26 | ~100h | Ready |
| Phase 3 | ~30 | ~120h | Planned |
| Phase 4 | ~25 | ~100h | Planned |
| Phase 5 | ~30 | ~100h | Planned |
| Phase 6 | ~25 | ~80h | Planned |
| Phase 7 | ~15 | ~50h | Planned |
| Phase 8 | ~15 | ~50h | Planned |
| **Total** | **~220** | **~780h** | - |

**Estimated Total Duration:** 12-18 months (solo developer, part-time)

---

## 🔗 Quick Links

- [Phase Planning Document](PHASES_AND_MILESTONES.md)
- [Feature Comparison](FEATURE_COMPARISON.md)
- [Architecture](../architecture/OFFLINE_FIRST_ARCHITECTURE.md)
- [GitHub Project Board](https://github.com/users/maxymurm/projects/XXX)

---

**End of Issues Backlog**  
*Run `create_issues.ps1` to create these issues in GitHub*
