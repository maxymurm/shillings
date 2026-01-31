# Create Issues for Phases 2-8 for Shillings
# Project: https://github.com/users/maxymurm/projects/4

$RepoOwner = "maxymurm"
$RepoName = "shillings"
$ProjectNumber = 4

Write-Host "Creating Phase 2-8 issues for $RepoOwner/$RepoName" -ForegroundColor Cyan

# Additional labels for later phases
$labels = @(
    @{ name = "phase:2"; color = "1D76DB"; desc = "Phase 2 Double-Entry" },
    @{ name = "phase:3"; color = "5319E7"; desc = "Phase 3 Reports" },
    @{ name = "phase:4"; color = "FBCA04"; desc = "Phase 4 Admin Panel" },
    @{ name = "phase:5"; color = "B60205"; desc = "Phase 5 Transactions" },
    @{ name = "phase:6"; color = "0E8A16"; desc = "Phase 6 Charts" },
    @{ name = "phase:7"; color = "D93F0B"; desc = "Phase 7 Testing" },
    @{ name = "phase:8"; color = "006B75"; desc = "Phase 8 Deployment" },
    @{ name = "component:filament"; color = "7057FF"; desc = "Filament admin panel" },
    @{ name = "component:reports"; color = "E99695"; desc = "Financial reports" },
    @{ name = "component:mobile"; color = "C2E0C6"; desc = "Mobile app" },
    @{ name = "component:sync"; color = "FEF2C0"; desc = "Offline sync" },
    @{ name = "component:testing"; color = "BFD4F2"; desc = "Test suite" }
)

Write-Host "`nCreating additional labels..." -ForegroundColor Yellow
foreach ($label in $labels) {
    gh label create $label.name --color $label.color --description $label.desc --repo "$RepoOwner/$RepoName" --force 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  Created: $($label.name)" -ForegroundColor Green
    }
}

$issues = @()

# ============================================
# PHASE 2: Double-Entry Logic
# ============================================

$issues += @{
    title = "[Phase 2.1] Implement precision arithmetic library"
    labels = "type:feature,component:accounting,priority:critical,phase:2"
    milestone = "Phase 2: Double-Entry Logic"
    body = @'
## Description
Create or integrate a precision arithmetic library for financial calculations using numerator/denominator fractions.

## Requirements
- No floating point arithmetic for money
- Support for arbitrary precision
- Conversion between fraction and decimal display
- Rounding rules for display

## Acceptance Criteria
- [ ] Money value object created
- [ ] Addition, subtraction, multiplication, division
- [ ] Comparison operators
- [ ] String formatting with proper decimal places
- [ ] Unit tests for all operations

## Estimate
6 hours
'@
}

$issues += @{
    title = "[Phase 2.1] Create Split balance validation"
    labels = "type:feature,component:accounting,priority:critical,phase:2"
    milestone = "Phase 2: Double-Entry Logic"
    body = @'
## Description
Implement validation that ensures all splits in a transaction sum to zero.

## Rules
- Sum of all split amounts must equal zero
- Debits are positive, credits are negative (or vice versa with action field)
- Validation runs before save
- Clear error messages on imbalance

## Acceptance Criteria
- [ ] SplitValidator class created
- [ ] Validates transaction balance
- [ ] Returns imbalance amount in error
- [ ] Works with multi-currency transactions
- [ ] Unit tests for edge cases

## Estimate
4 hours
'@
}

$issues += @{
    title = "[Phase 2.1] Implement transaction posting workflow"
    labels = "type:feature,component:accounting,priority:high,phase:2"
    milestone = "Phase 2: Double-Entry Logic"
    body = @'
## Description
Create workflow for posting transactions (making them immutable).

## Workflow
1. Draft transaction created
2. User reviews and edits
3. Post action validates and locks
4. Posted transactions cannot be edited (only reversed)

## Acceptance Criteria
- [ ] is_posted flag enforced
- [ ] PostTransactionAction class
- [ ] Validation before posting
- [ ] Audit trail for post action
- [ ] Reversing entry creation for corrections

## Estimate
4 hours
'@
}

$issues += @{
    title = "[Phase 2.2] Create multi-currency transaction support"
    labels = "type:feature,component:accounting,priority:high,phase:2"
    milestone = "Phase 2: Double-Entry Logic"
    body = @'
## Description
Support transactions with splits in different currencies.

## Requirements
- Exchange rate stored on split (value vs amount)
- amount = quantity in split currency
- value = quantity in transaction currency
- Historical exchange rate preservation

## Acceptance Criteria
- [ ] Split has amount and value columns
- [ ] Exchange rate calculated from ratio
- [ ] Currency conversion service
- [ ] Validation for multi-currency balance
- [ ] Unit tests for currency scenarios

## Estimate
6 hours
'@
}

$issues += @{
    title = "[Phase 2.2] Create exchange rates table and service"
    labels = "type:feature,component:database,priority:high,phase:2"
    milestone = "Phase 2: Double-Entry Logic"
    body = @'
## Description
Create exchange_rates table and service for currency conversion.

## Schema
```sql
CREATE TABLE exchange_rates (
    id UUID PRIMARY KEY,
    from_currency_id UUID NOT NULL,
    to_currency_id UUID NOT NULL,
    rate_num BIGINT NOT NULL,
    rate_denom BIGINT NOT NULL,
    effective_date DATE NOT NULL,
    source VARCHAR(50),
    created_at TIMESTAMP
);
```

## Acceptance Criteria
- [ ] Migration creates exchange_rates table
- [ ] ExchangeRate model with relationships
- [ ] ExchangeRateService for lookups
- [ ] Get rate for specific date
- [ ] API endpoint to add rates

## Estimate
4 hours
'@
}

$issues += @{
    title = "[Phase 2.3] Implement account balance caching"
    labels = "type:feature,component:accounting,priority:medium,phase:2"
    milestone = "Phase 2: Double-Entry Logic"
    body = @'
## Description
Cache account balances for performance (avoid summing all splits every time).

## Strategy
- Store running balance per account
- Update on transaction post
- Recalculate command for corrections
- Cache invalidation on unpost

## Acceptance Criteria
- [ ] account_balances table or cache
- [ ] Balance updated on post
- [ ] RecalculateBalancesCommand
- [ ] Performance benchmark showing improvement
- [ ] Cache invalidation works correctly

## Estimate
4 hours
'@
}

$issues += @{
    title = "[Phase 2.3] Create chart of accounts templates"
    labels = "type:feature,component:accounting,priority:medium,phase:2"
    milestone = "Phase 2: Double-Entry Logic"
    body = @'
## Description
Create predefined chart of accounts templates for different business types.

## Templates
- Personal Finance
- Small Business (General)
- Retail Business
- Service Business
- Non-Profit Organization

## Acceptance Criteria
- [ ] Template JSON/YAML files
- [ ] ImportChartOfAccountsCommand
- [ ] API endpoint to apply template
- [ ] Prevents overwrite of existing accounts
- [ ] Customizable after import

## Estimate
4 hours
'@
}

# ============================================
# PHASE 3: Financial Reports & Queries
# ============================================

$issues += @{
    title = "[Phase 3.1] Create Trial Balance report"
    labels = "type:feature,component:reports,priority:critical,phase:3"
    milestone = "Phase 3: Financial Reports & Queries"
    body = @'
## Description
Implement Trial Balance report showing all account balances.

## Requirements
- List all accounts with balances
- Debit and Credit columns
- Total must balance
- Date range filter
- Export to PDF/Excel

## Acceptance Criteria
- [ ] TrialBalanceReport class
- [ ] Query optimized for large datasets
- [ ] Columns: Account, Debit, Credit
- [ ] Totals row at bottom
- [ ] PDF export working
- [ ] Excel export working

## Estimate
6 hours
'@
}

$issues += @{
    title = "[Phase 3.1] Create Balance Sheet report"
    labels = "type:feature,component:reports,priority:critical,phase:3"
    milestone = "Phase 3: Financial Reports & Queries"
    body = @'
## Description
Implement Balance Sheet (Statement of Financial Position).

## Structure
- Assets (Current, Fixed)
- Liabilities (Current, Long-term)
- Equity
- Assets = Liabilities + Equity

## Acceptance Criteria
- [ ] BalanceSheetReport class
- [ ] Hierarchical account grouping
- [ ] As-of date parameter
- [ ] Comparison period option
- [ ] PDF and Excel export
- [ ] Validates accounting equation

## Estimate
8 hours
'@
}

$issues += @{
    title = "[Phase 3.1] Create Income Statement report"
    labels = "type:feature,component:reports,priority:critical,phase:3"
    milestone = "Phase 3: Financial Reports & Queries"
    body = @'
## Description
Implement Income Statement (Profit & Loss).

## Structure
- Revenue / Income
- Cost of Goods Sold
- Gross Profit
- Operating Expenses
- Net Income

## Acceptance Criteria
- [ ] IncomeStatementReport class
- [ ] Date range parameter (period)
- [ ] Comparison to previous period
- [ ] Percentage calculations
- [ ] PDF and Excel export

## Estimate
8 hours
'@
}

$issues += @{
    title = "[Phase 3.2] Create Cash Flow Statement report"
    labels = "type:feature,component:reports,priority:high,phase:3"
    milestone = "Phase 3: Financial Reports & Queries"
    body = @'
## Description
Implement Statement of Cash Flows.

## Sections
- Operating Activities
- Investing Activities
- Financing Activities
- Net Change in Cash

## Acceptance Criteria
- [ ] CashFlowReport class
- [ ] Indirect method calculation
- [ ] Date range parameter
- [ ] Account classification for sections
- [ ] PDF and Excel export

## Estimate
8 hours
'@
}

$issues += @{
    title = "[Phase 3.2] Create Account Register/Ledger view"
    labels = "type:feature,component:reports,priority:high,phase:3"
    milestone = "Phase 3: Financial Reports & Queries"
    body = @'
## Description
Create account register showing all transactions for an account.

## Features
- Chronological transaction list
- Running balance column
- Filter by date range
- Filter by reconciliation status
- Pagination for large accounts

## Acceptance Criteria
- [ ] AccountRegisterQuery class
- [ ] Running balance calculation
- [ ] Efficient pagination
- [ ] Date and status filters
- [ ] Export to CSV

## Estimate
4 hours
'@
}

$issues += @{
    title = "[Phase 3.3] Create General Ledger report"
    labels = "type:feature,component:reports,priority:high,phase:3"
    milestone = "Phase 3: Financial Reports & Queries"
    body = @'
## Description
Create General Ledger report showing all accounts with transactions.

## Features
- All accounts listed
- Transactions under each account
- Opening and closing balances
- Date range filter

## Acceptance Criteria
- [ ] GeneralLedgerReport class
- [ ] Grouped by account
- [ ] Opening/closing balances
- [ ] Date range parameter
- [ ] PDF export (paginated)

## Estimate
6 hours
'@
}

$issues += @{
    title = "[Phase 3.3] Create report export service (PDF/Excel)"
    labels = "type:feature,component:reports,priority:high,phase:3"
    milestone = "Phase 3: Financial Reports & Queries"
    body = @'
## Description
Create unified export service for all reports.

## Formats
- PDF (using DomPDF or similar)
- Excel (using Laravel Excel)
- CSV (native)

## Acceptance Criteria
- [ ] ReportExportService class
- [ ] PDF generation with company branding
- [ ] Excel with proper formatting
- [ ] CSV for data portability
- [ ] Async export for large reports

## Estimate
6 hours
'@
}

# ============================================
# PHASE 4: Admin Panel - Accounts
# ============================================

$issues += @{
    title = "[Phase 4.1] Create Filament Company resource"
    labels = "type:feature,component:filament,priority:critical,phase:4"
    milestone = "Phase 4: Admin Panel - Accounts"
    body = @'
## Description
Create Filament resource for Company management.

## Features
- List all companies (for multi-company users)
- Create new company
- Edit company settings
- Company switcher in header

## Acceptance Criteria
- [ ] CompanyResource with CRUD
- [ ] Company switcher component
- [ ] Session stores active company
- [ ] All queries scoped to active company
- [ ] Company creation wizard

## Estimate
6 hours
'@
}

$issues += @{
    title = "[Phase 4.1] Create Filament Account resource with tree view"
    labels = "type:feature,component:filament,priority:critical,phase:4"
    milestone = "Phase 4: Admin Panel - Accounts"
    body = @'
## Description
Create Filament resource for Account management with hierarchical tree view.

## Features
- Tree view showing account hierarchy
- Drag-and-drop reordering
- Inline balance display
- Quick add child account
- Account type icons/colors

## Acceptance Criteria
- [ ] AccountResource with CRUD
- [ ] Tree table component
- [ ] Parent/child relationship management
- [ ] Account type filtering
- [ ] Balance column (cached)
- [ ] Bulk actions (hide, delete)

## Estimate
8 hours
'@
}

$issues += @{
    title = "[Phase 4.2] Create Filament Currency resource"
    labels = "type:feature,component:filament,priority:high,phase:4"
    milestone = "Phase 4: Admin Panel - Accounts"
    body = @'
## Description
Create Filament resource for Currency management.

## Features
- List all currencies
- Add custom currencies
- Set default currency
- Manage exchange rates

## Acceptance Criteria
- [ ] CurrencyResource with CRUD
- [ ] Default currency indicator
- [ ] Exchange rate sub-table
- [ ] Import rates from API option
- [ ] Active/inactive toggle

## Estimate
4 hours
'@
}

$issues += @{
    title = "[Phase 4.2] Create account import/export functionality"
    labels = "type:feature,component:filament,priority:medium,phase:4"
    milestone = "Phase 4: Admin Panel - Accounts"
    body = @'
## Description
Allow importing and exporting chart of accounts.

## Features
- Export to CSV/Excel
- Import from CSV/Excel
- Import from templates
- Merge vs replace options

## Acceptance Criteria
- [ ] Export action on Account list
- [ ] Import page with file upload
- [ ] Column mapping UI
- [ ] Preview before import
- [ ] Error handling and reporting

## Estimate
6 hours
'@
}

$issues += @{
    title = "[Phase 4.3] Create dashboard with key metrics"
    labels = "type:feature,component:filament,priority:high,phase:4"
    milestone = "Phase 4: Admin Panel - Accounts"
    body = @'
## Description
Create main dashboard with financial overview widgets.

## Widgets
- Cash balance (sum of cash accounts)
- Net worth (assets - liabilities)
- Income vs Expenses (current month)
- Recent transactions
- Account balances chart

## Acceptance Criteria
- [ ] Dashboard page configured
- [ ] Cash balance widget
- [ ] Net worth widget
- [ ] Income/Expense comparison widget
- [ ] Recent transactions widget
- [ ] Refresh on company switch

## Estimate
6 hours
'@
}

$issues += @{
    title = "[Phase 4.3] Create user management in Filament"
    labels = "type:feature,component:filament,priority:high,phase:4"
    milestone = "Phase 4: Admin Panel - Accounts"
    body = @'
## Description
Create user management for company administrators.

## Features
- Invite users to company
- Assign roles
- Remove users
- User activity log

## Acceptance Criteria
- [ ] UserResource (company-scoped)
- [ ] Invite user action
- [ ] Role assignment select
- [ ] Remove from company action
- [ ] Email notifications working

## Estimate
6 hours
'@
}

# ============================================
# PHASE 5: Transactions & Reconciliation
# ============================================

$issues += @{
    title = "[Phase 5.1] Create Filament Transaction resource"
    labels = "type:feature,component:filament,priority:critical,phase:5"
    milestone = "Phase 5: Transactions & Reconciliation"
    body = @'
## Description
Create Filament resource for Transaction entry and management.

## Features
- Transaction list with filters
- Quick entry form
- Split entry (multiple accounts)
- Duplicate transaction
- Void/reverse transaction

## Acceptance Criteria
- [ ] TransactionResource with CRUD
- [ ] Date, description, amount visible
- [ ] Split repeater in form
- [ ] Balance validation in real-time
- [ ] Post/unpost actions
- [ ] Duplicate action

## Estimate
8 hours
'@
}

$issues += @{
    title = "[Phase 5.1] Create split entry UI with auto-balance"
    labels = "type:feature,component:filament,priority:critical,phase:5"
    milestone = "Phase 5: Transactions & Reconciliation"
    body = @'
## Description
Create intuitive split entry form that auto-calculates balance.

## Features
- Add/remove split rows
- Running total display
- Auto-fill last split to balance
- Account autocomplete
- Debit/Credit toggle or amount sign

## Acceptance Criteria
- [ ] Repeater with split fields
- [ ] Live balance calculation
- [ ] Imbalance warning
- [ ] Auto-balance button
- [ ] Keyboard navigation

## Estimate
6 hours
'@
}

$issues += @{
    title = "[Phase 5.2] Create bank reconciliation workflow"
    labels = "type:feature,component:filament,priority:high,phase:5"
    milestone = "Phase 5: Transactions & Reconciliation"
    body = @'
## Description
Implement bank reconciliation feature.

## Workflow
1. Enter statement ending balance
2. Mark transactions as cleared
3. System calculates difference
4. When balanced, mark reconciliation complete

## Acceptance Criteria
- [ ] ReconciliationPage in Filament
- [ ] Statement balance input
- [ ] Transaction checkbox list
- [ ] Running cleared balance
- [ ] Difference display
- [ ] Complete reconciliation action
- [ ] Reconciliation history

## Estimate
8 hours
'@
}

$issues += @{
    title = "[Phase 5.2] Create transaction search and filters"
    labels = "type:feature,component:filament,priority:high,phase:5"
    milestone = "Phase 5: Transactions & Reconciliation"
    body = @'
## Description
Add comprehensive search and filtering to transactions.

## Filters
- Date range
- Account(s)
- Amount range
- Description contains
- Reconciliation status
- Posted status

## Acceptance Criteria
- [ ] All filters implemented
- [ ] Filters persist in URL
- [ ] Quick date presets
- [ ] Clear all filters
- [ ] Filter count indicator

## Estimate
4 hours
'@
}

$issues += @{
    title = "[Phase 5.3] Create transaction templates/favorites"
    labels = "type:feature,component:filament,priority:medium,phase:5"
    milestone = "Phase 5: Transactions & Reconciliation"
    body = @'
## Description
Allow saving transaction templates for recurring entries.

## Features
- Save current transaction as template
- List saved templates
- Create transaction from template
- Edit/delete templates

## Acceptance Criteria
- [ ] TransactionTemplate model
- [ ] Save as template action
- [ ] Templates page
- [ ] Create from template action
- [ ] Template management

## Estimate
4 hours
'@
}

$issues += @{
    title = "[Phase 5.3] Create scheduled/recurring transactions"
    labels = "type:feature,component:filament,priority:medium,phase:5"
    milestone = "Phase 5: Transactions & Reconciliation"
    body = @'
## Description
Implement scheduled recurring transactions.

## Features
- Set recurrence (daily, weekly, monthly, yearly)
- Next occurrence date
- Auto-create or remind
- Skip/pause schedule

## Acceptance Criteria
- [ ] ScheduledTransaction model
- [ ] Recurrence rule storage
- [ ] CreateScheduledTransactionsCommand
- [ ] Scheduler entry in Kernel
- [ ] UI to manage schedules

## Estimate
6 hours
'@
}

# ============================================
# PHASE 6: Reports & Charts
# ============================================

$issues += @{
    title = "[Phase 6.1] Integrate ApexCharts for visualizations"
    labels = "type:feature,component:filament,priority:high,phase:6"
    milestone = "Phase 6: Reports & Charts"
    body = @'
## Description
Add ApexCharts for financial data visualization.

## Charts Needed
- Line chart (balance over time)
- Bar chart (income vs expense)
- Pie chart (expense breakdown)
- Area chart (cash flow)

## Acceptance Criteria
- [ ] ApexCharts package installed
- [ ] Chart widget base class
- [ ] Responsive design
- [ ] Theme integration (dark mode)
- [ ] Data fetching optimized

## Estimate
4 hours
'@
}

$issues += @{
    title = "[Phase 6.1] Create Income vs Expense chart"
    labels = "type:feature,component:reports,priority:high,phase:6"
    milestone = "Phase 6: Reports & Charts"
    body = @'
## Description
Create bar chart comparing income and expenses by month.

## Features
- Monthly comparison
- Year selector
- Stacked or grouped option
- Hover details

## Acceptance Criteria
- [ ] IncomeExpenseChart widget
- [ ] Monthly data aggregation
- [ ] Year filter
- [ ] Net income line overlay
- [ ] Dashboard integration

## Estimate
4 hours
'@
}

$issues += @{
    title = "[Phase 6.2] Create expense breakdown pie chart"
    labels = "type:feature,component:reports,priority:high,phase:6"
    milestone = "Phase 6: Reports & Charts"
    body = @'
## Description
Create pie/donut chart showing expense categories.

## Features
- Top-level expense accounts
- Drill-down to sub-accounts
- Period selector
- Percentage labels

## Acceptance Criteria
- [ ] ExpenseBreakdownChart widget
- [ ] Category aggregation
- [ ] Click to drill down
- [ ] Date range filter
- [ ] Legend with amounts

## Estimate
4 hours
'@
}

$issues += @{
    title = "[Phase 6.2] Create account balance trend chart"
    labels = "type:feature,component:reports,priority:high,phase:6"
    milestone = "Phase 6: Reports & Charts"
    body = @'
## Description
Create line chart showing account balance over time.

## Features
- Select account(s)
- Date range
- Daily/weekly/monthly granularity
- Multi-account comparison

## Acceptance Criteria
- [ ] BalanceTrendChart widget
- [ ] Account selector
- [ ] Date range picker
- [ ] Granularity toggle
- [ ] Multiple lines support

## Estimate
4 hours
'@
}

$issues += @{
    title = "[Phase 6.3] Create reports page in Filament"
    labels = "type:feature,component:filament,priority:high,phase:6"
    milestone = "Phase 6: Reports & Charts"
    body = @'
## Description
Create dedicated reports section in admin panel.

## Reports
- Trial Balance
- Balance Sheet
- Income Statement
- Cash Flow Statement
- General Ledger
- Account Register

## Acceptance Criteria
- [ ] Reports navigation group
- [ ] Report list page
- [ ] Parameter forms per report
- [ ] Run and display
- [ ] Export buttons

## Estimate
6 hours
'@
}

$issues += @{
    title = "[Phase 6.3] Create custom report builder"
    labels = "type:feature,component:reports,priority:medium,phase:6"
    milestone = "Phase 6: Reports & Charts"
    body = @'
## Description
Allow users to create custom reports.

## Features
- Select accounts to include
- Choose columns
- Set grouping
- Date range
- Save report definition

## Acceptance Criteria
- [ ] CustomReport model
- [ ] Report builder UI
- [ ] Column selection
- [ ] Account filtering
- [ ] Save and load reports

## Estimate
8 hours
'@
}

# ============================================
# PHASE 7: Testing & QA
# ============================================

$issues += @{
    title = "[Phase 7.1] Create comprehensive unit test suite"
    labels = "type:chore,component:testing,priority:critical,phase:7"
    milestone = "Phase 7: Testing & QA"
    body = @'
## Description
Create unit tests for all services and models.

## Coverage Targets
- Models: 90%+
- Services: 95%+
- Overall: 80%+

## Acceptance Criteria
- [ ] All model tests written
- [ ] AccountService tests
- [ ] TransactionService tests
- [ ] Precision arithmetic tests
- [ ] Balance calculation tests
- [ ] CI runs tests on PR

## Estimate
16 hours
'@
}

$issues += @{
    title = "[Phase 7.1] Create feature tests for API endpoints"
    labels = "type:chore,component:testing,priority:critical,phase:7"
    milestone = "Phase 7: Testing & QA"
    body = @'
## Description
Create feature tests for all API endpoints.

## Coverage
- All CRUD operations
- Authentication flows
- Authorization checks
- Error responses
- Pagination

## Acceptance Criteria
- [ ] Account API tests
- [ ] Transaction API tests
- [ ] Report API tests
- [ ] Auth flow tests
- [ ] Error handling tests

## Estimate
12 hours
'@
}

$issues += @{
    title = "[Phase 7.2] Create Filament browser tests"
    labels = "type:chore,component:testing,priority:high,phase:7"
    milestone = "Phase 7: Testing & QA"
    body = @'
## Description
Create browser tests for Filament admin panel using Dusk.

## Coverage
- Login/logout
- Account CRUD
- Transaction entry
- Report generation
- Navigation

## Acceptance Criteria
- [ ] Dusk configured
- [ ] Login test
- [ ] Account tree test
- [ ] Transaction entry test
- [ ] Report view test

## Estimate
8 hours
'@
}

$issues += @{
    title = "[Phase 7.2] Implement accounting rule validation tests"
    labels = "type:chore,component:testing,priority:critical,phase:7"
    milestone = "Phase 7: Testing & QA"
    body = @'
## Description
Create tests specifically for accounting rules.

## Rules to Test
- Double-entry balance
- Account type constraints
- Posting rules
- Reconciliation logic
- Currency conversion

## Acceptance Criteria
- [ ] Balance validation tests
- [ ] Cannot post unbalanced
- [ ] Immutable posted transactions
- [ ] Currency conversion accuracy
- [ ] Account type enforcement

## Estimate
8 hours
'@
}

$issues += @{
    title = "[Phase 7.3] Performance testing and optimization"
    labels = "type:chore,component:testing,priority:high,phase:7"
    milestone = "Phase 7: Testing & QA"
    body = @'
## Description
Test performance with large datasets and optimize.

## Scenarios
- 10,000+ transactions
- 500+ accounts
- Report generation time
- API response time

## Acceptance Criteria
- [ ] Seeder for large dataset
- [ ] Benchmark suite
- [ ] Identify slow queries
- [ ] Add indexes as needed
- [ ] Document performance baselines

## Estimate
8 hours
'@
}

$issues += @{
    title = "[Phase 7.3] Security audit and fixes"
    labels = "type:chore,component:testing,priority:critical,phase:7"
    milestone = "Phase 7: Testing & QA"
    body = @'
## Description
Conduct security audit and fix vulnerabilities.

## Areas
- SQL injection prevention
- XSS prevention
- CSRF protection
- Authorization checks
- Rate limiting
- Input validation

## Acceptance Criteria
- [ ] Run security scanner
- [ ] Fix identified issues
- [ ] Verify auth on all endpoints
- [ ] Rate limiting configured
- [ ] Security headers set

## Estimate
8 hours
'@
}

# ============================================
# PHASE 8: Deployment & Documentation
# ============================================

$issues += @{
    title = "[Phase 8.1] Configure Laravel Forge deployment"
    labels = "type:chore,component:backend,priority:critical,phase:8"
    milestone = "Phase 8: Deployment & Documentation"
    body = @'
## Description
Set up production deployment on Laravel Forge.

## Tasks
- Server provisioning
- Database setup
- SSL certificate
- Queue worker
- Scheduler

## Acceptance Criteria
- [ ] Server created on Forge
- [ ] Git deployment configured
- [ ] Environment variables set
- [ ] SSL enabled
- [ ] Queue worker running
- [ ] Scheduler running

## Estimate
4 hours
'@
}

$issues += @{
    title = "[Phase 8.1] Set up CI/CD with GitHub Actions"
    labels = "type:chore,component:backend,priority:critical,phase:8"
    milestone = "Phase 8: Deployment & Documentation"
    body = @'
## Description
Configure GitHub Actions for CI/CD pipeline.

## Pipeline
- Run tests on PR
- Code style check
- Static analysis
- Deploy on merge to main

## Acceptance Criteria
- [ ] Test workflow running
- [ ] Pint code style check
- [ ] PHPStan analysis
- [ ] Auto-deploy to staging
- [ ] Manual deploy to production

## Estimate
4 hours
'@
}

$issues += @{
    title = "[Phase 8.2] Create user documentation"
    labels = "type:docs,priority:high,phase:8"
    milestone = "Phase 8: Deployment & Documentation"
    body = @'
## Description
Create comprehensive user documentation.

## Sections
- Getting started
- Chart of accounts setup
- Transaction entry
- Reconciliation
- Reports
- FAQ

## Acceptance Criteria
- [ ] Documentation site (docs folder or external)
- [ ] All sections written
- [ ] Screenshots included
- [ ] Video tutorials (optional)
- [ ] Searchable

## Estimate
12 hours
'@
}

$issues += @{
    title = "[Phase 8.2] Create API documentation"
    labels = "type:docs,component:api,priority:high,phase:8"
    milestone = "Phase 8: Deployment & Documentation"
    body = @'
## Description
Create comprehensive API documentation.

## Content
- Authentication guide
- Endpoint reference
- Request/response examples
- Error codes
- Rate limits

## Acceptance Criteria
- [ ] OpenAPI/Swagger spec
- [ ] Documentation generated
- [ ] All endpoints documented
- [ ] Examples for each endpoint
- [ ] Postman collection

## Estimate
8 hours
'@
}

$issues += @{
    title = "[Phase 8.3] Create developer setup guide"
    labels = "type:docs,priority:high,phase:8"
    milestone = "Phase 8: Deployment & Documentation"
    body = @'
## Description
Create guide for developers setting up local environment.

## Content
- Prerequisites
- Installation steps
- Environment configuration
- Running tests
- Contributing guidelines

## Acceptance Criteria
- [ ] README updated
- [ ] CONTRIBUTING.md created
- [ ] Step-by-step instructions
- [ ] Troubleshooting section
- [ ] Verified by fresh setup

## Estimate
4 hours
'@
}

$issues += @{
    title = "[Phase 8.3] Production launch checklist"
    labels = "type:chore,priority:critical,phase:8"
    milestone = "Phase 8: Deployment & Documentation"
    body = @'
## Description
Complete all items required for production launch.

## Checklist
- [ ] All tests passing
- [ ] Security audit complete
- [ ] Performance acceptable
- [ ] Backups configured
- [ ] Monitoring set up
- [ ] Error tracking (Sentry)
- [ ] Documentation complete
- [ ] Legal pages (Terms, Privacy)

## Estimate
8 hours
'@
}

# Create all issues
Write-Host "`nCreating issues..." -ForegroundColor Yellow
$createdCount = 0
$totalIssues = $issues.Count

foreach ($issue in $issues) {
    $bodyFile = [System.IO.Path]::GetTempFileName()
    $issue.body | Out-File -FilePath $bodyFile -Encoding utf8
    
    $result = gh issue create `
        --repo "$RepoOwner/$RepoName" `
        --title $issue.title `
        --body-file $bodyFile `
        --label $issue.labels `
        --milestone $issue.milestone 2>&1
    
    Remove-Item $bodyFile -Force
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  [$($createdCount + 1)/$totalIssues] Created: $($issue.title)" -ForegroundColor Green
        $createdCount++
        
        if ($result -match "#(\d+)") {
            $issueNum = $matches[1]
            gh project item-add $ProjectNumber --owner $RepoOwner --url "https://github.com/$RepoOwner/$RepoName/issues/$issueNum" 2>$null
        }
    } else {
        Write-Host "  [$($createdCount + 1)/$totalIssues] Failed: $($issue.title)" -ForegroundColor Red
        Write-Host "    $result" -ForegroundColor DarkGray
    }
    
    Start-Sleep -Milliseconds 300
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "Done! Created $createdCount of $totalIssues issues." -ForegroundColor Green
Write-Host "View at: https://github.com/$RepoOwner/$RepoName/issues" -ForegroundColor Cyan
Write-Host "Project: https://github.com/users/$RepoOwner/projects/$ProjectNumber" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
