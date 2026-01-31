<# 
.SYNOPSIS
    Creates GitHub issues and milestones for Shillings project from the Issues Backlog.

.DESCRIPTION
    This script reads the ISSUES_BACKLOG.md file and creates:
    - Milestones for each milestone defined
    - Issues with proper labels, descriptions, and milestone assignments
    - Adds issues to the specified project board

.PARAMETER RepoOwner
    GitHub username or organization (default: maxymurm)

.PARAMETER RepoName
    Repository name (default: shillings)

.PARAMETER ProjectNumber
    GitHub Project number for the project board

.PARAMETER DryRun
    If specified, shows what would be created without actually creating

.EXAMPLE
    .\create_issues.ps1 -ProjectNumber 1
    .\create_issues.ps1 -DryRun
#>

param(
    [string]$RepoOwner = "maxymurm",
    [string]$RepoName = "shillings",
    [int]$ProjectNumber = 0,
    [switch]$DryRun
)

# Color output helpers
function Write-Success { param($msg) Write-Host "✅ $msg" -ForegroundColor Green }
function Write-Info { param($msg) Write-Host "ℹ️  $msg" -ForegroundColor Cyan }
function Write-Warn { param($msg) Write-Host "⚠️  $msg" -ForegroundColor Yellow }
function Write-Err { param($msg) Write-Host "❌ $msg" -ForegroundColor Red }

# Check GitHub CLI
if (-not (Get-Command gh -ErrorAction SilentlyContinue)) {
    Write-Err "GitHub CLI (gh) is not installed. Install from: https://cli.github.com/"
    exit 1
}

# Check authentication
$authStatus = gh auth status 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Err "Not authenticated with GitHub CLI. Run: gh auth login"
    exit 1
}

Write-Info "Creating issues for $RepoOwner/$RepoName"
if ($DryRun) { Write-Warn "DRY RUN MODE - No changes will be made" }

# Define labels to create
$labels = @(
    @{ name = "type:feature"; color = "0E8A16"; description = "New functionality" },
    @{ name = "type:bug"; color = "D73A4A"; description = "Bug fix" },
    @{ name = "type:chore"; color = "FEF2C0"; description = "Maintenance task" },
    @{ name = "type:docs"; color = "0075CA"; description = "Documentation" },
    @{ name = "type:test"; color = "FBCA04"; description = "Testing" },
    @{ name = "type:refactor"; color = "D4C5F9"; description = "Code refactoring" },
    @{ name = "component:backend"; color = "5319E7"; description = "Laravel/API" },
    @{ name = "component:frontend"; color = "1D76DB"; description = "Web UI" },
    @{ name = "component:mobile"; color = "006B75"; description = "Compose Multiplatform" },
    @{ name = "component:database"; color = "BFD4F2"; description = "Database/migrations" },
    @{ name = "component:sync"; color = "C2E0C6"; description = "Offline/sync engine" },
    @{ name = "component:auth"; color = "E99695"; description = "Authentication" },
    @{ name = "priority:critical"; color = "B60205"; description = "P0 - Must have for phase" },
    @{ name = "priority:high"; color = "D93F0B"; description = "P1 - Important" },
    @{ name = "priority:medium"; color = "FBCA04"; description = "P2 - Nice to have" },
    @{ name = "priority:low"; color = "0E8A16"; description = "P3 - Can defer" },
    @{ name = "status:ready"; color = "0E8A16"; description = "Ready to work on" },
    @{ name = "status:blocked"; color = "D73A4A"; description = "Blocked by dependency" },
    @{ name = "status:in-progress"; color = "FBCA04"; description = "Currently being worked on" },
    @{ name = "status:review"; color = "1D76DB"; description = "In code review" }
)

# Create labels
Write-Info "Creating labels..."
foreach ($label in $labels) {
    if ($DryRun) {
        Write-Info "Would create label: $($label.name)"
    } else {
        $result = gh label create $label.name --repo "$RepoOwner/$RepoName" --color $label.color --description $label.description --force 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Success "Created/updated label: $($label.name)"
        }
    }
}

# Define milestones
$milestones = @(
    @{ title = "1.1 - Project Setup"; description = "Laravel setup, database, CI/CD"; due = "2026-02-15" },
    @{ title = "1.2 - Database Schema"; description = "All core database tables and migrations"; due = "2026-03-01" },
    @{ title = "1.3 - Core Models & Services"; description = "Eloquent models and business logic services"; due = "2026-03-15" },
    @{ title = "1.4 - Authentication & Authorization"; description = "Auth, RBAC, permissions"; due = "2026-03-22" },
    @{ title = "1.5 - Basic API Endpoints"; description = "REST API for all resources"; due = "2026-04-01" },
    @{ title = "1.6 - Double-Entry Engine"; description = "Transaction validation, precision math"; due = "2026-04-15" },
    @{ title = "2.1 - Filament Setup"; description = "Admin panel configuration and theme"; due = "2026-05-01" },
    @{ title = "2.2 - Account Management"; description = "Account CRUD and tree view"; due = "2026-05-15" },
    @{ title = "2.3 - Transaction Management"; description = "Transaction entry with splits"; due = "2026-06-01" },
    @{ title = "2.4 - Reports"; description = "Financial reports (BS, P&L, CF)"; due = "2026-06-15" },
    @{ title = "2.5 - Settings & Configuration"; description = "Company and user settings"; due = "2026-06-30" }
)

# Create milestones
Write-Info "Creating milestones..."
foreach ($milestone in $milestones) {
    if ($DryRun) {
        Write-Info "Would create milestone: $($milestone.title)"
    } else {
        $result = gh api repos/$RepoOwner/$RepoName/milestones -f title="$($milestone.title)" -f description="$($milestone.description)" -f due_on="$($milestone.due)T00:00:00Z" 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Success "Created milestone: $($milestone.title)"
        } else {
            # Try to update existing
            Write-Info "Milestone may already exist: $($milestone.title)"
        }
    }
}

# Define Phase 1 issues
$phase1Issues = @(
    # Milestone 1.1 - Project Setup
    @{
        title = "Initialize Laravel 12 Project"
        labels = "type:chore,component:backend,priority:critical"
        milestone = "1.1 - Project Setup"
        body = @"
## Description
Create new Laravel 12 project with recommended configuration.

## Acceptance Criteria
- [ ] Laravel 12 installed via Composer
- [ ] ``.env.example`` configured with all needed variables
- [ ] Application key generated
- [ ] Project runs locally with ``php artisan serve``
- [ ] README updated with setup instructions

## Estimate
2 hours
"@
    },
    @{
        title = "Configure PostgreSQL Database"
        labels = "type:chore,component:database,priority:critical"
        milestone = "1.1 - Project Setup"
        body = @"
## Description
Set up PostgreSQL database connection and verify connectivity.

## Acceptance Criteria
- [ ] PostgreSQL database created
- [ ] Database credentials in ``.env``
- [ ] Connection verified with ``php artisan migrate``
- [ ] UUID extension enabled if needed

## Estimate
1 hour
"@
    },
    @{
        title = "Install and Configure Filament 4.3"
        labels = "type:chore,component:frontend,priority:critical"
        milestone = "1.1 - Project Setup"
        body = @"
## Description
Install Filament admin panel framework and configure basic settings.

## Acceptance Criteria
- [ ] Filament installed via Composer
- [ ] Admin panel accessible at ``/admin``
- [ ] Default user created for testing
- [ ] Basic theme configuration applied

## Estimate
2 hours
"@
    },
    @{
        title = "Set Up Git Repository and Branching Strategy"
        labels = "type:chore,priority:critical"
        milestone = "1.1 - Project Setup"
        body = @"
## Description
Initialize Git repository with proper branching strategy.

## Acceptance Criteria
- [ ] Git repository initialized
- [ ] ``.gitignore`` properly configured
- [ ] Main branch protected
- [ ] Branching strategy documented (main, develop, feature/*)
- [ ] Initial commit made

## Estimate
1 hour
"@
    },
    @{
        title = "Configure GitHub Actions CI/CD"
        labels = "type:chore,priority:high"
        milestone = "1.1 - Project Setup"
        body = @"
## Description
Set up GitHub Actions for continuous integration.

## Acceptance Criteria
- [ ] Workflow file created (``.github/workflows/ci.yml``)
- [ ] Runs on push to main and PRs
- [ ] Runs PHP linting (Pint)
- [ ] Runs PHPUnit tests
- [ ] Runs PHPStan static analysis
- [ ] Build status badge in README

## Estimate
3 hours
"@
    },
    @{
        title = "Configure Code Quality Tools"
        labels = "type:chore,component:backend,priority:high"
        milestone = "1.1 - Project Setup"
        body = @"
## Description
Set up Laravel Pint, PHPStan, and other code quality tools.

## Acceptance Criteria
- [ ] Laravel Pint installed and configured
- [ ] PHPStan installed with Level 5+ config
- [ ] Pre-commit hooks set up (optional)
- [ ] VS Code settings for formatting
- [ ] ``composer lint`` and ``composer analyse`` scripts

## Estimate
2 hours
"@
    },
    @{
        title = "Set Up Development Environment Documentation"
        labels = "type:docs,priority:medium"
        milestone = "1.1 - Project Setup"
        body = @"
## Description
Document complete development environment setup.

## Acceptance Criteria
- [ ] Prerequisites documented (PHP, Composer, Node, PostgreSQL)
- [ ] Step-by-step setup instructions
- [ ] Common issues and solutions
- [ ] VS Code recommended extensions
- [ ] Environment variables explained

## Estimate
2 hours
"@
    },

    # Milestone 1.2 - Database Schema
    @{
        title = "Create Companies Migration and Model"
        labels = "type:feature,component:database,component:backend,priority:critical"
        milestone = "1.2 - Database Schema"
        body = @"
## Description
Create the companies table for multi-tenancy support.

## Acceptance Criteria
- [ ] Migration creates ``companies`` table
- [ ] Fields: id (UUID), name, currency_code, fiscal_year_start, settings (JSON), timestamps, soft deletes
- [ ] Company model with fillable, casts
- [ ] Factory for testing
- [ ] Seeder with sample company

## Schema
``````php
Schema::create('companies', function (Blueprint `$table) {
    `$table->uuid('id')->primary();
    `$table->string('name');
    `$table->string('currency_code', 3)->default('USD');
    `$table->date('fiscal_year_start')->nullable();
    `$table->json('settings')->nullable();
    `$table->timestamps();
    `$table->softDeletes();
});
``````

## Estimate
3 hours
"@
    },
    @{
        title = "Create Users Table with Company Relationship"
        labels = "type:feature,component:database,component:backend,priority:critical"
        milestone = "1.2 - Database Schema"
        body = @"
## Description
Modify default users table to support multi-company access.

## Acceptance Criteria
- [ ] Users can belong to multiple companies (pivot table)
- [ ] ``company_user`` pivot with role
- [ ] Current company preference stored
- [ ] User model relationships
- [ ] Factory updated

## Estimate
2 hours
"@
    },
    @{
        title = "Create Commodities Migration and Model"
        labels = "type:feature,component:database,component:backend,priority:critical"
        milestone = "1.2 - Database Schema"
        body = @"
## Description
Create commodities table for currencies and securities (GnuCash model).

## Acceptance Criteria
- [ ] Migration creates ``commodities`` table
- [ ] Fields: id (UUID), namespace, mnemonic, full_name, fraction, quote_source, metadata (JSON)
- [ ] Unique constraint on (namespace, mnemonic)
- [ ] Commodity model
- [ ] Factory and seeder with common currencies (USD, EUR, GBP, KES, etc.)

## Estimate
3 hours
"@
    },
    @{
        title = "Create Accounts Migration and Model"
        labels = "type:feature,component:database,component:backend,priority:critical"
        milestone = "1.2 - Database Schema"
        body = @"
## Description
Create hierarchical accounts table (chart of accounts).

## Acceptance Criteria
- [ ] Migration creates ``accounts`` table
- [ ] Fields: id, company_id, parent_id, code, name, type, description, notes, commodity_id, opening_balance, is_placeholder, is_hidden, tax_related, path, level, metadata, timestamps, soft deletes, sync fields
- [ ] Account types enum: ASSET, BANK, CASH, CREDIT_CARD, LIABILITY, EQUITY, INCOME, EXPENSE, STOCK, MUTUAL_FUND, RECEIVABLE, PAYABLE
- [ ] Account model with parent/children relationships
- [ ] Scopes for type filtering
- [ ] Factory and seeder with sample chart of accounts

## Estimate
4 hours
"@
    },
    @{
        title = "Create Transactions Migration and Model"
        labels = "type:feature,component:database,component:backend,priority:critical"
        milestone = "1.2 - Database Schema"
        body = @"
## Description
Create transactions table (transaction headers).

## Acceptance Criteria
- [ ] Migration creates ``transactions`` table
- [ ] Fields: id, company_id, transaction_number, description, notes, posted_at, entered_at, currency_id, is_balanced, metadata, created_by, timestamps, soft deletes, sync fields
- [ ] Transaction model with relationships (company, currency, splits, creator)
- [ ] Factory for testing

## Estimate
3 hours
"@
    },
    @{
        title = "Create Splits Migration and Model"
        labels = "type:feature,component:database,component:backend,priority:critical"
        milestone = "1.2 - Database Schema"
        body = @"
## Description
Create splits table - the fundamental accounting unit (GnuCash model).

## Acceptance Criteria
- [ ] Migration creates ``splits`` table
- [ ] Fields: id, transaction_id, account_id, amount_numerator, amount_denominator, value_numerator, value_denominator, memo, action, reconcile_state, reconcile_date, lot_id, metadata, timestamps, sync fields
- [ ] Split model with relationships
- [ ] Accessors for computed amount/value as decimals
- [ ] Reconcile state constants (n, c, y, f, v)
- [ ] Factory for testing

## Estimate
4 hours
"@
    },
    @{
        title = "Create Prices Migration and Model"
        labels = "type:feature,component:database,component:backend,priority:critical"
        milestone = "1.2 - Database Schema"
        body = @"
## Description
Create prices table for exchange rates and stock prices.

## Acceptance Criteria
- [ ] Migration creates ``prices`` table
- [ ] Fields: id, commodity_id, currency_id, date, source, type, value_numerator, value_denominator, timestamps
- [ ] Unique constraint on (commodity_id, currency_id, date, source, type)
- [ ] Price model with relationships
- [ ] Factory and seeder with sample exchange rates

## Estimate
2 hours
"@
    },
    @{
        title = "Create Categories Migration and Model"
        labels = "type:feature,component:database,component:backend,priority:high"
        milestone = "1.2 - Database Schema"
        body = @"
## Description
Create hierarchical categories for items and transactions.

## Acceptance Criteria
- [ ] Migration creates ``categories`` table
- [ ] Fields: id, company_id, parent_id, type, name, color, is_enabled, path, level, timestamps
- [ ] Category types: income, expense, item
- [ ] Category model with hierarchy
- [ ] Factory and seeder

## Estimate
2 hours
"@
    },
    @{
        title = "Create Settings Migration and Model"
        labels = "type:feature,component:database,component:backend,priority:high"
        milestone = "1.2 - Database Schema"
        body = @"
## Description
Create key-value settings table for company configuration.

## Acceptance Criteria
- [ ] Migration creates ``settings`` table
- [ ] Fields: id, company_id, key, value (JSON), timestamps
- [ ] Unique constraint on (company_id, key)
- [ ] Setting model with helper methods
- [ ] Settings service for easy access

## Estimate
2 hours
"@
    },
    @{
        title = "Create Activity Log Migration and Model"
        labels = "type:feature,component:database,component:backend,priority:medium"
        milestone = "1.2 - Database Schema"
        body = @"
## Description
Create audit trail/activity log table.

## Acceptance Criteria
- [ ] Migration creates ``activity_logs`` table
- [ ] Fields: id, company_id, user_id, subject_type, subject_id, event, properties (JSON), timestamps
- [ ] ActivityLog model
- [ ] Trait for automatic logging on models
- [ ] Index for efficient querying

## Estimate
2 hours
"@
    },
    @{
        title = "Create Database Indexes for Performance"
        labels = "type:chore,component:database,priority:high"
        milestone = "1.2 - Database Schema"
        body = @"
## Description
Add proper indexes to all tables for query performance.

## Acceptance Criteria
- [ ] Indexes on all foreign keys
- [ ] Indexes on commonly queried fields (company_id, posted_at, type)
- [ ] Composite indexes where beneficial
- [ ] Document index strategy

## Estimate
2 hours
"@
    },
    @{
        title = "Create Model Factories for All Models"
        labels = "type:test,component:backend,priority:high"
        milestone = "1.2 - Database Schema"
        body = @"
## Description
Create comprehensive factories for all models for testing.

## Acceptance Criteria
- [ ] Factory for each model
- [ ] Realistic fake data
- [ ] States for different scenarios
- [ ] Relationships properly handled

## Estimate
3 hours
"@
    },
    @{
        title = "Create Database Seeders"
        labels = "type:chore,component:database,priority:high"
        milestone = "1.2 - Database Schema"
        body = @"
## Description
Create seeders for development and demo data.

## Acceptance Criteria
- [ ] Main DatabaseSeeder orchestrates all
- [ ] CurrencySeeder with 50+ currencies
- [ ] DefaultAccountSeeder with standard chart of accounts
- [ ] DemoCompanySeeder with sample transactions
- [ ] Can run independently or together

## Estimate
3 hours
"@
    }
)

# Create Phase 1 issues
Write-Info "Creating Phase 1 issues..."
$issueCount = 0

foreach ($issue in $phase1Issues) {
    $issueCount++
    
    if ($DryRun) {
        Write-Info "Would create issue #$issueCount : $($issue.title)"
        Write-Info "  Labels: $($issue.labels)"
        Write-Info "  Milestone: $($issue.milestone)"
    } else {
        # Create issue
        $result = gh issue create `
            --repo "$RepoOwner/$RepoName" `
            --title $issue.title `
            --body $issue.body `
            --label $issue.labels `
            --milestone $issue.milestone 2>&1
        
        if ($LASTEXITCODE -eq 0) {
            Write-Success "Created issue: $($issue.title)"
            # Extract issue number and add to project if specified
            if ($ProjectNumber -gt 0) {
                $issueUrl = $result
                Write-Info "  Adding to project board..."
                gh project item-add $ProjectNumber --owner $RepoOwner --url $issueUrl 2>&1 | Out-Null
            }
        } else {
            Write-Err "Failed to create: $($issue.title)"
            Write-Err $result
        }
        
        # Rate limit protection
        Start-Sleep -Milliseconds 500
    }
}

Write-Success "`nCompleted! Created $issueCount issues."

if (-not $DryRun) {
    Write-Info "`nNext steps:"
    Write-Info "1. View issues: gh issue list --repo $RepoOwner/$RepoName"
    Write-Info "2. View project: https://github.com/users/$RepoOwner/projects/$ProjectNumber"
    Write-Info "3. Start working on Phase 1, Milestone 1.1!"
}
