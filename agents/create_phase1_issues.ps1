# Create Phase 1 Issues for Shillings
# Project: https://github.com/users/maxymurm/projects/4

$RepoOwner = "maxymurm"
$RepoName = "shillings"
$ProjectNumber = 4

Write-Host "Creating Phase 1 issues for $RepoOwner/$RepoName" -ForegroundColor Cyan

# Check GitHub CLI
if (-not (Get-Command gh -ErrorAction SilentlyContinue)) {
    Write-Host "GitHub CLI (gh) is not installed." -ForegroundColor Red
    exit 1
}

# Create labels first (skip if exists)
$labels = @(
    @{ name = "type:feature"; color = "0E8A16"; desc = "New functionality" },
    @{ name = "type:chore"; color = "FEF2C0"; desc = "Maintenance work" },
    @{ name = "type:docs"; color = "0075CA"; desc = "Documentation" },
    @{ name = "component:backend"; color = "C5DEF5"; desc = "Laravel backend" },
    @{ name = "component:database"; color = "D4C5F9"; desc = "Database and migrations" },
    @{ name = "component:auth"; color = "FBCA04"; desc = "Authentication" },
    @{ name = "component:api"; color = "BFD4F2"; desc = "REST API" },
    @{ name = "component:accounting"; color = "F9D0C4"; desc = "Accounting engine" },
    @{ name = "priority:critical"; color = "B60205"; desc = "Must have" },
    @{ name = "priority:high"; color = "D93F0B"; desc = "Important" },
    @{ name = "priority:medium"; color = "FBCA04"; desc = "Nice to have" },
    @{ name = "phase:1"; color = "0E8A16"; desc = "Phase 1 Foundation" }
)

Write-Host "`nCreating labels..." -ForegroundColor Yellow
foreach ($label in $labels) {
    gh label create $label.name --color $label.color --description $label.desc --repo "$RepoOwner/$RepoName" --force 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  Created: $($label.name)" -ForegroundColor Green
    }
}

# Issues to create - using here-strings carefully
$issues = @()

# Issue 1
$issues += @{
    title = "[Phase 1.1] Initialize Laravel 12 Project"
    labels = "type:chore,component:backend,priority:critical,phase:1"
    milestone = "Phase 1: Database Schema & Core Models"
    body = @'
## Description
Create new Laravel 12 project with recommended configuration.

## Acceptance Criteria
- [ ] Laravel 12 installed via Composer
- [ ] .env.example configured with all needed variables
- [ ] Application key generated
- [ ] Project runs locally with php artisan serve
- [ ] README updated with setup instructions

## Estimate
2 hours
'@
}

# Issue 2
$issues += @{
    title = "[Phase 1.1] Configure PostgreSQL Database"
    labels = "type:chore,component:database,priority:critical,phase:1"
    milestone = "Phase 1: Database Schema & Core Models"
    body = @'
## Description
Set up PostgreSQL database connection and verify connectivity.

## Acceptance Criteria
- [ ] PostgreSQL database created
- [ ] Database credentials in .env
- [ ] Connection verified with php artisan migrate
- [ ] UUID extension enabled if needed

## Estimate
1 hour
'@
}

# Issue 3
$issues += @{
    title = "[Phase 1.1] Install and Configure Filament 4"
    labels = "type:chore,component:backend,priority:critical,phase:1"
    milestone = "Phase 1: Database Schema & Core Models"
    body = @'
## Description
Install Filament 4 admin panel with proper configuration.

## Acceptance Criteria
- [ ] Filament 4 installed via Composer
- [ ] Admin panel accessible at /admin
- [ ] Custom theme configured
- [ ] Dark mode enabled

## Estimate
2 hours
'@
}

# Issue 4
$issues += @{
    title = "[Phase 1.2] Create companies migration and model"
    labels = "type:feature,component:database,priority:critical,phase:1"
    milestone = "Phase 1: Database Schema & Core Models"
    body = @'
## Description
Create the companies table for multi-tenancy support.

## Schema
```sql
CREATE TABLE companies (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    fiscal_year_start DATE,
    default_currency_id UUID,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

## Acceptance Criteria
- [ ] Migration creates companies table
- [ ] Company model with fillable, casts
- [ ] Factory and seeder for testing
- [ ] Soft deletes enabled

## Estimate
2 hours
'@
}

# Issue 5
$issues += @{
    title = "[Phase 1.2] Create currencies migration and model"
    labels = "type:feature,component:database,priority:critical,phase:1"
    milestone = "Phase 1: Database Schema & Core Models"
    body = @'
## Description
Create the currencies table (ISO 4217 commodities).

## Schema
```sql
CREATE TABLE currencies (
    id UUID PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    symbol VARCHAR(10),
    decimal_places INT DEFAULT 2,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Acceptance Criteria
- [ ] Migration creates currencies table
- [ ] Currency model with proper casts
- [ ] Seeder with common currencies (USD, EUR, KES, etc.)
- [ ] ISO 4217 codes used

## Estimate
2 hours
'@
}

# Issue 6
$issues += @{
    title = "[Phase 1.2] Create account_types migration and model"
    labels = "type:feature,component:database,priority:critical,phase:1"
    milestone = "Phase 1: Database Schema & Core Models"
    body = @'
## Description
Create the account_types table (ASSET, LIABILITY, INCOME, EXPENSE, EQUITY).

## Schema
```sql
CREATE TABLE account_types (
    id UUID PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    normal_balance ENUM('DEBIT', 'CREDIT') NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Acceptance Criteria
- [ ] Migration creates account_types table
- [ ] AccountType model with enum for normal_balance
- [ ] Seeder with 5 standard account types
- [ ] Normal balance correctly assigned (ASSET/EXPENSE = DEBIT)

## Estimate
1 hour
'@
}

# Issue 7
$issues += @{
    title = "[Phase 1.2] Create accounts migration and model"
    labels = "type:feature,component:database,priority:critical,phase:1"
    milestone = "Phase 1: Database Schema & Core Models"
    body = @'
## Description
Create the accounts table with hierarchical structure (tree).

## Schema
```sql
CREATE TABLE accounts (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id),
    parent_id UUID REFERENCES accounts(id),
    account_type_id UUID NOT NULL REFERENCES account_types(id),
    currency_id UUID NOT NULL REFERENCES currencies(id),
    code VARCHAR(50),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    is_placeholder BOOLEAN DEFAULT false,
    is_hidden BOOLEAN DEFAULT false,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

## Acceptance Criteria
- [ ] Migration creates accounts table with all columns
- [ ] Account model with relationships (company, parent, children, type, currency)
- [ ] Scoped by company_id
- [ ] Tree traversal methods (ancestors, descendants)
- [ ] Soft deletes enabled

## Estimate
3 hours
'@
}

# Issue 8
$issues += @{
    title = "[Phase 1.2] Create transactions migration and model"
    labels = "type:feature,component:database,priority:critical,phase:1"
    milestone = "Phase 1: Database Schema & Core Models"
    body = @'
## Description
Create the transactions table (groups of splits that must balance).

## Schema
```sql
CREATE TABLE transactions (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(id),
    transaction_date DATE NOT NULL,
    post_date DATE,
    description VARCHAR(500),
    num VARCHAR(50),
    notes TEXT,
    is_posted BOOLEAN DEFAULT false,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

## Acceptance Criteria
- [ ] Migration creates transactions table
- [ ] Transaction model with relationships
- [ ] Scoped by company_id
- [ ] posted vs draft status
- [ ] Soft deletes enabled

## Estimate
2 hours
'@
}

# Issue 9
$issues += @{
    title = "[Phase 1.2] Create splits migration and model"
    labels = "type:feature,component:database,priority:critical,phase:1"
    milestone = "Phase 1: Database Schema & Core Models"
    body = @'
## Description
Create the splits table (individual entries linking accounts to transactions).

## Schema
```sql
CREATE TABLE splits (
    id UUID PRIMARY KEY,
    transaction_id UUID NOT NULL REFERENCES transactions(id),
    account_id UUID NOT NULL REFERENCES accounts(id),
    amount_num BIGINT NOT NULL,
    amount_denom BIGINT NOT NULL DEFAULT 100,
    value_num BIGINT NOT NULL,
    value_denom BIGINT NOT NULL DEFAULT 100,
    action ENUM('DEBIT', 'CREDIT') NOT NULL,
    memo VARCHAR(500),
    reconciled_state CHAR(1) DEFAULT 'n',
    reconcile_date DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Acceptance Criteria
- [ ] Migration creates splits table with precision columns
- [ ] Split model with relationships (transaction, account)
- [ ] Precision arithmetic methods (getAmount, getValue)
- [ ] Reconciliation state tracking

## Estimate
3 hours
'@
}

# Issue 10
$issues += @{
    title = "[Phase 1.3] Create AccountService with balance calculations"
    labels = "type:feature,component:accounting,priority:high,phase:1"
    milestone = "Phase 1: Database Schema & Core Models"
    body = @'
## Description
Create AccountService for balance calculations and account operations.

## Methods Needed
- getBalance(account, date = null): Get account balance
- getBalanceInCurrency(account, currency, date = null): Convert to currency
- getChildrenBalances(account): Sum of all descendant balances
- getRunningBalance(account, splits): Running balance for register

## Acceptance Criteria
- [ ] AccountService class created
- [ ] Precision arithmetic used (no floating point)
- [ ] Date filtering works correctly
- [ ] Currency conversion supported
- [ ] Unit tests with 90%+ coverage

## Estimate
4 hours
'@
}

# Issue 11
$issues += @{
    title = "[Phase 1.3] Create TransactionService with validation"
    labels = "type:feature,component:accounting,priority:high,phase:1"
    milestone = "Phase 1: Database Schema & Core Models"
    body = @'
## Description
Create TransactionService for transaction operations with double-entry validation.

## Methods Needed
- create(data): Create transaction with splits (validates balance)
- update(transaction, data): Update transaction
- post(transaction): Mark as posted (immutable after)
- reverse(transaction): Create reversing entry
- validate(transaction): Check all splits sum to zero

## Acceptance Criteria
- [ ] TransactionService class created
- [ ] Double-entry validation enforced
- [ ] Cannot save unbalanced transactions
- [ ] Posted transactions are immutable
- [ ] Unit tests for all validation rules

## Estimate
6 hours
'@
}

# Issue 12
$issues += @{
    title = "[Phase 1.4] Implement Laravel Sanctum authentication"
    labels = "type:feature,component:auth,priority:critical,phase:1"
    milestone = "Phase 1: Database Schema & Core Models"
    body = @'
## Description
Set up Laravel Sanctum for API token authentication.

## Acceptance Criteria
- [ ] Sanctum installed and configured
- [ ] Token creation on login
- [ ] Token revocation on logout
- [ ] Token abilities/scopes defined
- [ ] Rate limiting configured

## Estimate
3 hours
'@
}

# Issue 13
$issues += @{
    title = "[Phase 1.4] Create roles and permissions system"
    labels = "type:feature,component:auth,priority:high,phase:1"
    milestone = "Phase 1: Database Schema & Core Models"
    body = @'
## Description
Implement RBAC with roles and permissions (spatie/laravel-permission).

## Roles
- owner: Full access to company
- admin: Manage users, settings
- accountant: Full accounting access
- bookkeeper: Enter transactions, view reports
- viewer: Read-only access

## Acceptance Criteria
- [ ] spatie/laravel-permission installed
- [ ] Roles seeded
- [ ] Permissions defined for all resources
- [ ] Role assignment on user creation
- [ ] Middleware for permission checks

## Estimate
4 hours
'@
}

# Issue 14
$issues += @{
    title = "[Phase 1.5] Create accounts API endpoints"
    labels = "type:feature,component:api,priority:high,phase:1"
    milestone = "Phase 1: Database Schema & Core Models"
    body = @'
## Description
Create REST API endpoints for account management.

## Endpoints
- GET /api/v1/accounts - List accounts (with tree option)
- GET /api/v1/accounts/{id} - Get single account
- POST /api/v1/accounts - Create account
- PUT /api/v1/accounts/{id} - Update account
- DELETE /api/v1/accounts/{id} - Soft delete account
- GET /api/v1/accounts/{id}/balance - Get account balance

## Acceptance Criteria
- [ ] All endpoints implemented
- [ ] Request validation
- [ ] Proper HTTP status codes
- [ ] Pagination on list endpoint
- [ ] Tree structure option
- [ ] Feature tests for all endpoints

## Estimate
4 hours
'@
}

# Issue 15
$issues += @{
    title = "[Phase 1.5] Create transactions API endpoints"
    labels = "type:feature,component:api,priority:high,phase:1"
    milestone = "Phase 1: Database Schema & Core Models"
    body = @'
## Description
Create REST API endpoints for transaction management.

## Endpoints
- GET /api/v1/transactions - List transactions
- GET /api/v1/transactions/{id} - Get with splits
- POST /api/v1/transactions - Create with splits
- PUT /api/v1/transactions/{id} - Update (if not posted)
- DELETE /api/v1/transactions/{id} - Soft delete
- POST /api/v1/transactions/{id}/post - Post transaction

## Acceptance Criteria
- [ ] All endpoints implemented
- [ ] Split creation in same request
- [ ] Double-entry validation
- [ ] Cannot modify posted transactions
- [ ] Feature tests for all endpoints

## Estimate
6 hours
'@
}

Write-Host "`nCreating issues..." -ForegroundColor Yellow
$createdCount = 0

foreach ($issue in $issues) {
    # Write body to temp file to handle special characters
    $bodyFile = [System.IO.Path]::GetTempFileName()
    $issue.body | Out-File -FilePath $bodyFile -Encoding utf8
    
    # Create issue
    $result = gh issue create `
        --repo "$RepoOwner/$RepoName" `
        --title $issue.title `
        --body-file $bodyFile `
        --label $issue.labels `
        --milestone $issue.milestone 2>&1
    
    Remove-Item $bodyFile -Force
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  Created: $($issue.title)" -ForegroundColor Green
        $createdCount++
        
        # Extract issue number and add to project
        if ($result -match "#(\d+)") {
            $issueNum = $matches[1]
            gh project item-add $ProjectNumber --owner $RepoOwner --url "https://github.com/$RepoOwner/$RepoName/issues/$issueNum" 2>$null
        }
    } else {
        Write-Host "  Failed: $($issue.title) - $result" -ForegroundColor Red
    }
    
    # Small delay to avoid rate limiting
    Start-Sleep -Milliseconds 500
}

Write-Host "`nDone! Created $createdCount issues." -ForegroundColor Green
Write-Host "View at: https://github.com/$RepoOwner/$RepoName/issues" -ForegroundColor Cyan
Write-Host "Project: https://github.com/users/$RepoOwner/projects/$ProjectNumber" -ForegroundColor Cyan
