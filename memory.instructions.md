# Shillings Project - Resume Instructions

> **CREATED:** 2026-02-01 (Session Pause Point)
> **PURPOSE:** Instructions for AI agent to resume work on this project

---

## 🔁 PARALLEL PARITY RULE — MANDATORY (Effective 2026-03-16)

**Every change in this project MUST have a corresponding change in shillings-mobile in the same session.**
- Features, bug fixes, terminology changes, docs → apply to both projects
- GitHub issues scoped for one project → create equivalent issues for the other
- Autonomous prompts MUST include tasks for both projects
- Commits must be paired: one in `develop`, one in `shillings-mobile/main`
- Exception: platform-specific features (Filament admin, biometrics) are exempt but must be noted

**Mobile project:** `c:\Users\maxmm\shillings-mobile` | `main` branch | https://github.com/maxymurm/shillings-mobile

---

## 🎯 WHERE WE LEFT OFF

### Project Status at a Glance
| Metric | Status |
|--------|--------|
| **GitHub Issues** | 64 closed (Phases 1-8) / 64 open (Phases 9-16) |
| **Test Pass Rate** | 183/200 (91.5%) |
| **Unit Tests** | 106/106 ✅ (100%) |
| **Feature Tests** | 77/94 (17 failing) |
| **Current Phase** | Phase 9-16 implementation in progress |

### Test Status by Suite
| Test Suite | Status | Pass Rate |
|------------|--------|-----------|
| Unit Tests (all) | ✅ | 106/106 (100%) |
| ContactApiTest | ✅ | 12/12 (100%) |
| TaxApiTest | ✅ | 14/14 (100%) |
| DocumentApiTest | ⚠️ | 10/20 (50%) |
| BudgetApiTest | ⚠️ | 8/15 (53%) |
| Other Feature Tests | ✅ | ~33/33 |

---

## 🚦 WHAT'S WORKING

### Fully Implemented & Tested ✅
1. **Phase 9 - Contacts** (Issues #65-72)
   - Model: `Contact.php`, `ContactPerson.php`, `Address.php`
   - Service: `ContactService.php`
   - API Controller: `ContactController.php`
   - Filament: `ContactResource.php`
   - Tests: 12/12 passing

2. **Phase 15 - Tax Management** (Issues #114-120)
   - Models: `Tax.php`, `TaxRule.php`
   - Service: `TaxService.php`
   - API Controller: `TaxController.php`
   - Filament: `TaxResource.php`
   - Tests: 14/14 passing

### Partially Implemented ⚠️
3. **Phase 10 - Documents/Invoicing** (Issues #73-83)
   - Models: `Document.php`, `DocumentItem.php`, `BillTerm.php`
   - Service: `DocumentService.php`
   - API Controller: `DocumentController.php`
   - Filament: `DocumentResource.php`
   - Tests: 10/20 passing
   - **PROBLEM:** Summary endpoint structure mismatch, overdue calculation issues

4. **Phase 11 - Budgeting** (Issues #84-90)
   - Models: `Budget.php`, `BudgetAccount.php`
   - Service: `BudgetService.php`
   - API Controller: `BudgetController.php`
   - Filament: `BudgetResource.php`
   - Tests: 8/15 passing
   - **PROBLEM:** BudgetAccount relationship issues, distribute amount not creating multiple periods

### Scaffolded (Not Tested) 📝
5. **Phase 12 - Banking Import** (Issues #91-99)
   - Models: `BankConnection.php`, `ImportBatch.php`
   - Service: `ImportService.php`
   - API Controller: `ImportController.php`
   - NO TESTS YET

6. **Phase 13 - Advanced Reports** (Issues #100-107)
   - Model: `CustomReport.php`
   - Filament: `CustomReportResource.php`
   - NO TESTS YET

7. **Phase 14 - Scheduled Transactions** (Issues #108-113)
   - Models: `ScheduledTransaction.php`, `TransactionTemplate.php`
   - Service: `RecurrenceService.php`
   - API Controller: `ScheduledTransactionController.php`
   - Filament: `ScheduledTransactionResource.php`, `TransactionTemplateResource.php`
   - NO TESTS YET

8. **Phase 16 - Mobile/Offline** (Issues #121-128)
   - NOT STARTED

---

## 🐛 BUGS TO FIX FIRST

### Priority 1: DocumentApiTest (10 failing)
```
File: tests/Feature/Api/DocumentApiTest.php
Issues:
1. test_it_gets_overdue_documents - assertJsonCount mismatch
2. test_it_gets_summary - Missing 'invoices' key in response structure
```

The `DocumentController@summary` endpoint returns wrong structure. Test expects:
```php
'data' => [
    'invoices' => ['count', 'total_num', 'total_denom', 'paid_num', 'paid_denom'],
    'bills' => [...],
    'quotes' => [...]
]
```

### Priority 2: BudgetApiTest (7 failing)
```
File: tests/Feature/Api/BudgetApiTest.php
Issues:
1. test_it_distributes_amount - Creates 1 account instead of 12
2. test_it_gets_forecast - Missing response structure
```

The `BudgetService::distributeAmount()` method needs to create 12 `BudgetAccount` records (one per period), but only creates 1.

---

## ⚠️ CRITICAL CODE PATTERNS

### Money Value Object - PRIVATE CONSTRUCTOR
```php
// ❌ WRONG - Will throw error
$money = new Money(1000, 100, 'USD');

// ✅ CORRECT - Use static factory methods
$money = Money::fromFraction(1000, 100, 'USD');  // numerator/denominator
$money = Money::fromDecimal('10.00', 'USD');     // decimal string
$money = Money::fromCents(1000, 'USD');          // cents (100 = 1.00)
$money = Money::zero('USD');                      // zero value

// ❌ WRONG methods
$money->toFloat();      // Does not exist
$money->getCurrency();  // Does not exist

// ✅ CORRECT methods
$money->toDecimal();      // Returns string like "10.00"
$money->getCurrencyCode(); // Returns "USD"
```

### Database Column Names
```php
// Tax model
'enabled'    // NOT 'is_enabled'
'recoverable' // NOT 'is_recoverable'

// Document model
'issued_at'  // NOT 'issue_date'
'due_at'     // NOT 'due_date'
```

### Factory States
```php
// TaxFactory
Tax::factory()->enabled()->create();
Tax::factory()->recoverable()->create();
Tax::factory()->fixed()->create();

// DocumentFactory
Document::factory()->draft()->create();
Document::factory()->creditNote()->create();
Document::factory()->withTotal(1000, 100)->create();
```

---

## 📁 KEY FILES MODIFIED THIS SESSION

| File | Changes Made |
|------|-------------|
| `app/Services/BudgetService.php` | Fixed Money method calls |
| `app/Services/TaxService.php` | Fixed `is_enabled` → `enabled` |
| `app/Http/Controllers/Api/TaxController.php` | Fixed column names |
| `app/Http/Controllers/Api/DocumentController.php` | Fixed `issue_date` → `issued_at` |
| `app/Http/Controllers/Api/BudgetController.php` | Fixed `Money::fromFloat` → `fromDecimal` |
| `app/Models/Tax.php` | Added `documentItems()` relationship |
| `database/factories/TaxFactory.php` | Added states |
| `database/factories/DocumentFactory.php` | Added states |
| `database/factories/BudgetAccountFactory.php` | Created new |

---

## 📋 GITHUB ISSUES STATUS

### Phases 1-8: CLOSED ✅ (64 issues)
Issues #1-64 are all closed and implemented.

### Phases 9-16: OPEN 📝 (64 issues)
Issues #65-128 are all still OPEN in GitHub.

**IMPORTANT:** Even though code exists for Phases 9, 10, 11, 14, 15, the GitHub issues have NOT been closed yet. Before closing any issue, ensure:
1. The related tests pass
2. Filament resource works
3. API endpoints respond correctly

### Issue Ranges by Phase
| Phase | Issues | Description |
|-------|--------|-------------|
| 9 | #65-72 | Contacts & Parties |
| 10 | #73-83 | Documents/Invoicing |
| 11 | #84-90 | Budgeting |
| 12 | #91-99 | Banking Import |
| 13 | #100-107 | Advanced Reports |
| 14 | #108-113 | Scheduled Transactions |
| 15 | #114-120 | Tax Management |
| 16 | #121-128 | Mobile/Offline |

---

## 🎬 HOW TO RESUME

### Step 1: Verify Current State
```powershell
cd c:\Users\maxmm\Herd\shillings
php artisan test --filter "Unit"          # Should be 106/106
php artisan test --filter "ContactApiTest" # Should be 12/12  
php artisan test --filter "TaxApiTest"     # Should be 14/14
```

### Step 2: Fix Failing Tests
1. Fix `DocumentApiTest` (10 failing) - Fix the summary endpoint
2. Fix `BudgetApiTest` (7 failing) - Fix the distribute amount logic

### Step 3: Close GitHub Issues
Once tests pass, close the related GitHub issues:
```powershell
# After fixing Contact tests (already passing)
gh issue close 65 66 67 68 69 70 71 72 --comment "Phase 9 complete"

# After fixing Tax tests (already passing)
gh issue close 114 115 116 117 118 119 120 --comment "Phase 15 complete"
```

### Step 4: Continue Implementation
Phases that need work:
- Phase 12: Banking Import (no tests yet)
- Phase 13: Advanced Reports (no tests yet)
- Phase 14: Scheduled Transactions (no tests yet)
- Phase 16: Mobile/Offline (not started)

---

## 🔧 USEFUL COMMANDS

```powershell
# Run all tests
php artisan test

# Run specific test file
php artisan test --filter "DocumentApiTest"

# Fresh migration with seeds
php artisan migrate:fresh --seed

# Check GitHub issues
gh issue list --state open --limit 100
gh issue list --state closed --limit 100

# Close issues
gh issue close 65 --comment "Implemented contacts migration"
```

---

## 📊 OVERALL PROGRESS

```
Phases 1-8:  ████████████████████ 100% (64/64 issues closed)
Phase 9:     ████████████████████ Code done, tests pass, issues OPEN
Phase 10:    ████████████░░░░░░░░ 50% tests passing
Phase 11:    ██████████░░░░░░░░░░ 53% tests passing
Phase 12:    ██████░░░░░░░░░░░░░░ Code scaffolded, no tests
Phase 13:    ██████░░░░░░░░░░░░░░ Code scaffolded, no tests
Phase 14:    ████████░░░░░░░░░░░░ Code scaffolded, no tests
Phase 15:    ████████████████████ Code done, tests pass, issues OPEN
Phase 16:    ░░░░░░░░░░░░░░░░░░░░ Not started
```

---

**Last Updated:** 2026-02-01
**Session Paused At:** Test fixing for Phases 9-16
**Next Action:** Fix DocumentApiTest and BudgetApiTest, then close GitHub issues
