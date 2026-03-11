---
applyTo: '**'
lastUpdated: '2026-02-01 22:30:00'
chatSession: 'session-005'
projectName: 'Shillings - Offline-First Accounting'
---

# Project Memory - Shillings

> **AGENT INSTRUCTIONS:** Always read this file FIRST before starting any new conversation. Update after completing tasks, making decisions, or when user says "remember this".
> 
> **⚠️ IMPORTANT:** See `memory.instructions.md` in project root for detailed resume instructions!

---

## 🔗 Important Links (REMEMBER THESE)

- **GitHub Repo:** https://github.com/maxymurm/shillings
- **Project Board:** https://github.com/users/maxymurm/projects/4
- **Milestones:** https://github.com/maxymurm/shillings/milestones
- **Issues:** https://github.com/maxymurm/shillings/issues
- **Developer:** Maxwell Murunga (@maxymurm)
- **Company:** Advent Digital

---

## 🎯 Current Focus

**Active Phase:** Phase 16 Implementation (✅ COMPLETE)
**Test Status:** 259/259 passing (100%) - **ALL TESTS PASSING!**
**Current Branch:** develop  
**Last Activity:** 2026-02-13 - Phase 16 COMPLETE! All 17 tests passing. PWA manifest, service worker, IndexedDB storage, offline transactions, background sync, conflict resolution, mobile Filament, push notifications, receipt capture all implemented.

**GitHub Milestones (COMPLETED):**
1. ✅ Phase 1: Database Schema & Core Models (15 issues)
2. ✅ Phase 2: Double-Entry Logic (7 issues)
3. ✅ Phase 3: Financial Reports & Queries (7 issues)
4. ✅ Phase 4: Admin Panel - Accounts (6 issues)
5. ✅ Phase 5: Transactions & Reconciliation (6 issues)
6. ✅ Phase 6: Reports & Charts (6 issues)
7. ✅ Phase 7: Testing & QA (6 issues)
8. ✅ Phase 8: Deployment & Documentation (6 issues)

**GitHub Issues (COMPLETED - code and tests done, issues CLOSED):**
9. ✅ Phase 9: Contacts - CODE DONE, 12/12 tests pass, issues #65-72 CLOSED
10. ✅ Phase 10: Documents - CODE DONE, 20/20 tests pass, issues #73-83 CLOSED
11. ✅ Phase 11: Budgeting - CODE DONE, 15/15 tests pass, issues #84-90 CLOSED
12. ✅ Phase 12: Banking Import - CODE DONE, 15/15 tests pass, issues #91-99 CLOSED
13. ✅ Phase 13: Advanced Reports - CODE DONE, 14/14 tests pass, issues #100-107 CLOSED
14. ✅ Phase 14: Scheduled Transactions - CODE DONE, 13/13 tests pass, issues #108-113 CLOSED
15. ✅ Phase 15: Tax Management - CODE DONE, 14/14 tests pass, issues #114-120 CLOSED
COMPLETED - code and tests done, issues CLOSED):**
9. ✅ Phase 9: Contacts - CODE DONE, 12/12 tests pass, issues #65-72 CLOSED
10. ✅ Phase 10: Documents - CODE DONE, 20/20 tests pass, issues #73-83 CLOSED
11. ✅ Phase 11: Budgeting - CODE DONE, 15/15 tests pass, issues #84-90 CLOSED
12. ✅ Phase 12: Banking Import - CODE DONE, 15/15 tests pass, issues #91-99 CLOSED
13. ✅ Phase 13: Advanced Reports - CODE DONE, 14/14 tests pass, issues #100-107 CLOSED
14. ✅ Phase 14: Scheduled Transactions - CODE DONE, 13/13 tests pass, issues #108-113 CLOSED
15. ✅ Phase 15: Tax Management - CODE DONE, 14/14 tests pass, issues #114-120 CLOSEDd)
    - 📋 Note: Tax Summary feature needs database migration - deferred to future enhancement

**GitHub Issues (NEXT - not started):**
16. ✅ Phase 16: Mobile & Offline Sync - CODE DONE, 17/17 tests pass, issues #121-128 CLOSED

**ALL 16 PHASES COMPLETE! Project fully implemented.**

---

## ✅ Project Completion Summary (Phases 1-8)

### Test Coverage:
- **200 tests passing** (525 assertions)
- Unit tests: 106 (Models, Services, Value Objects)
- Feature tests: 94 (API, Security, Performance)
- Browser tests: Created (Dusk) - ready for E2E testing

### Key Deliverables:
- Full double-entry accounting system
- GnuCash-style precision arithmetic (numerator/denominator)
- Multi-company support with role-based permissions
- REST API with Sanctum authentication
- Filament 4 admin panel
- Financial reports (Trial Balance, Balance Sheet, Income Statement, Cash Flow)
- CI/CD pipeline with GitHub Actions
- Comprehensive documentation

---

## 📋 Next Phases Summary (9-16)

### Priority Tier 1 - Core Business (4-6 weeks)
- **Phase 9**: Contacts (customers, vendors, employees)
- **Phase 10**: Invoicing & Documents (invoices, bills, quotes)

### Priority Tier 2 - Financial Planning (3-4 weeks)
- **Phase 11**: Budgeting with variance analysis
- **Phase 14**: Enhanced scheduled/recurring transactions

### Priority Tier 3 - Banking Integration (3-4 weeks)
- **Phase 12**: OFX/CSV import, transaction matching

### Priority Tier 4 - Advanced Features (3-4 weeks)
- **Phase 13**: Advanced reports (aging, general ledger)
- **Phase 15**: Tax management, multi-jurisdiction

### Priority Tier 5 - Mobile/Offline (4-6 weeks)
- **Phase 16**: PWA, offline sync, conflict resolution

---

## 📁 Key Files by Phase

### Phase 1: Database & Models
- 7 migrations (currencies, companies, account_types, accounts, transactions, splits)
- 6 Eloquent models with relationships
- BelongsToCompany trait for multi-tenancy

### Phase 2: Double-Entry Logic
- AccountService (balance calculations)
- TransactionService (validation, posting)
- Money value object (precision arithmetic)

### Phase 3: Financial Reports
- ReportController API endpoints
- Trial Balance, Balance Sheet, Income Statement, Cash Flow reports
- Export functionality

### Phase 4: Admin Panel
- Filament resources for Accounts, Account Types
- Hierarchical account tree views

### Phase 5: Transactions
- Filament resources for Transactions, Splits
- Reconciliation features

### Phase 6: Reports & Charts
- Filament dashboard widgets
- Chart visualizations

### Phase 7: Testing & QA
- tests/Unit/Accounting/ (14 tests)
- tests/Feature/SecurityTest.php (12 tests)
- tests/Feature/PerformanceTest.php (4 tests)
- tests/Browser/ (Dusk tests)

### Phase 8: Deployment
- .github/workflows/ci.yml (CI/CD pipeline)
- docs/deployment/FORGE_DEPLOYMENT.md
- docs/deployment/LAUNCH_CHECKLIST.md
- docs/api/API_DOCUMENTATION.md
- README.md, CONTRIBUTING.md

---

## 👤 User Preferences

- Solo developer workflow
- Cloud-only deployment (no Docker/K8s)
- Laravel Forge for deployment
- Multi-company support
- Mobile as subset of web features initially

---

## 🔧 Scripts & Tools

**Run Tests:**
```powershell
cd c:\Users\maxmm\Herd\shillings
php artisan test
```

**Run Migrations:**
```powershell
php artisan migrate:fresh --seed
```

**GitHub CLI Status:**
```powershell
gh auth status  # Logged in as maxymurm
```

---

## ⚠️ CRITICAL CODE PATTERNS

### Money Value Object (PRIVATE CONSTRUCTOR!)
```php
// ❌ WRONG - Will throw error
$money = new Money(1000, 100, 'USD');

// ✅ CORRECT - Use static factory methods ONLY
$money = Money::fromFraction(1000, 100, 'USD');
$money = Money::fromDecimal('10.00', 'USD');
$money = Money::fromCents(1000, 'USD');
$money = Money::zero('USD');

// ❌ WRONG methods (don't exist)
$money->toFloat();
$money->getCurrency();

// ✅ CORRECT methods
$money->toDecimal();
$money->getCurrencyCode();
```

### Database Column Names
```php
// Tax model uses:
'enabled'     // NOT 'is_enabled'
'recoverable' // NOT 'is_recoverable'

// Document model uses:
'issued_at'   // NOT 'issue_date'
'due_at'      // NOT 'due_date'
```

---

**Memory Updated:** 2026-02-13 04:00  
*Phase 16 (Mobile & Offline Sync) COMPLETE! 100% tests passing (259/259, 814 assertions). All 16 phases fully implemented. All 128 GitHub issues closed. PWA with offline-first architecture, IndexedDB storage via Dexie.js, background sync with exponential backoff, conflict resolution (3 strategies), push notifications, mobile receipt capture, mobile-optimized Filament with SPA mode.*
