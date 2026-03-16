---
applyTo: '**'
lastUpdated: '2026-03-12 12:00:00'
chatSession: 'session-006'
projectName: 'Shillings - Offline-First Accounting'
---

# Project Memory - Shillings

> **AGENT INSTRUCTIONS:** Always read this file FIRST before starting any new conversation. Update after completing tasks, making decisions, or when user says "remember this".
> 
> **⚠️ IMPORTANT:** See `memory.instructions.md` in project root for detailed resume instructions!

---

## 🔁 PARALLEL PARITY RULE — MANDATORY (Effective 2026-03-16)

> **This is the most important standing instruction. It overrides all previous habits.**

**Every change made to one project MUST be simultaneously applied to the other.**

### The Rule
- Feature added to **web/backend** → same feature must be implemented in **mobile** in the same session
- Bug fixed in **backend** → check if same bug exists in **mobile**, fix it there too
- Feature added to **mobile** → check if the same belongs in **web**, implement if applicable
- **GitHub Issues** scoped for one project → equivalent issues MUST be created for the other project in the same session
- **Autonomous prompts / YOLO execution plans** MUST always include tasks for BOTH projects
- **Commits** must be paired: backend commit + mobile commit in every session
- **Terminology changes** (e.g. Company → Organisation) must be applied to both projects
- **Documentation** (README, memory files, agent docs) must be kept in sync across both projects

### What "Same Feature" Means
- Web gets a new page → Mobile gets the equivalent page/flow
- Web gets a UI redesign → Mobile gets the equivalent design update
- Backend API gains an endpoint → Mobile service layer gains the matching API call
- Web gets a bug fix → Mobile is checked for the same bug and fixed if present
- Issues are created in GitHub → Issues are created for BOTH project boards

### Exception
Features that are **explicitly web-only or mobile-only by design** (e.g. Filament admin CRUD, file import/export on web; biometrics, native camera on mobile) are exempt, but this must be explicitly noted in the task/issue.

### Projects
- **Backend + Web:** `c:\Users\maxmm\Herd\shillings` → `develop` branch → https://github.com/maxymurm/shillings
- **Mobile:** `c:\Users\maxmm\shillings-mobile` → `main` branch → https://github.com/maxymurm/shillings-mobile

---

## 🔗 Important Links (REMEMBER THESE)

- **GitHub Repo:** https://github.com/maxymurm/shillings
- **Project Board:** https://github.com/users/maxymurm/projects/4
- **Milestones:** https://github.com/maxymurm/shillings/milestones
- **Issues:** https://github.com/maxymurm/shillings/issues
- **Developer:** Maxwell Murunga (@maxymurm)
- **Company:** Advent Digital

### 📱 Mobile App (NEW — Session 006)
- **Mobile Repo:** https://github.com/maxymurm/shillings-mobile
- **Mobile Local:** `c:\Users\maxmm\shillings-mobile`
- **Mobile Board:** https://github.com/users/maxymurm/projects/9
- **Mobile Stack:** Ionic 8 + Capacitor 6 + Vue 3 + TypeScript + Pinia + Dexie.js
- **Mobile Prompt:** `c:\Users\maxmm\shillings-mobile\agents\AUTONOMOUS_EXECUTION_PROMPT.md`

---

## 🎯 Current Focus

**Shillings Backend:** ALL 16 PHASES COMPLETE ✅  
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
- **Parallel parity across web and mobile — NOT a subset, both platforms are equal citizens**
- YOLO/autonomous execution mode preferred
- Conventional commits, always push after each logical group

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

**Memory Updated:** 2026-03-16  
*Parallel Parity Rule established (2026-03-16): All future work must target both web and mobile simultaneously. Org switcher and enhanced Account Register ported to mobile. Feature Parity Matrix and Ecosystem Guide added to docs.*

*Phase 16 (Mobile & Offline Sync) COMPLETE! 100% tests passing (259/259, 814 assertions). All 16 phases fully implemented. All 128 GitHub issues closed. PWA with offline-first architecture, IndexedDB storage via Dexie.js, background sync with exponential backoff, conflict resolution (3 strategies), push notifications, mobile receipt capture, mobile-optimized Filament with SPA mode.*
