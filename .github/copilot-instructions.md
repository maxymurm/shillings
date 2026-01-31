---
applyTo: '**'
lastUpdated: '2026-01-31 20:00:00'
chatSession: 'session-003'
projectName: 'Shillings - Offline-First Accounting'
---

# Project Memory - Shillings

> **AGENT INSTRUCTIONS:** Always read this file FIRST before starting any new conversation. Update after completing tasks, making decisions, or when user says "remember this".

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

**Active Phase:** Phase 2 - Double-Entry Logic (Phase 1 COMPLETE!)  
**Active Milestone:** Phase 2: Double-Entry Logic  
**Current Branch:** develop  
**Last Activity:** 2026-01-31 - Completed all 15 Phase 1 issues

**GitHub Milestones (exact names):**
1. ✅ Phase 1: Database Schema & Core Models (COMPLETED)
2. Phase 2: Double-Entry Logic
3. Phase 3: Financial Reports & Queries
4. Phase 4: Admin Panel - Accounts
5. Phase 5: Transactions & Reconciliation
6. Phase 6: Reports & Charts
7. Phase 7: Testing & QA
8. Phase 8: Deployment & Documentation

---

## ✅ Completed Tasks (Phase 1 Implementation)

### Issues Closed via Commits:
1. ✅ #6: Initialize Laravel 12 Project
2. ✅ #7: Configure Database Connections (SQLite dev / PostgreSQL prod)
3. ✅ #8: Configure Filament 4 Admin Panel (Emerald theme, dark mode)
4. ✅ #9: Companies Migration & Model
5. ✅ #10: Currencies Migration & Model (50+ currencies seeded)
6. ✅ #11: Account Types Migration & Model (5 standard types)
7. ✅ #12: Accounts Migration & Model (hierarchical tree structure)
8. ✅ #13: Transactions Migration & Model
9. ✅ #14: Splits Migration & Model (GnuCash precision arithmetic)
10. ✅ #15: AccountService (balance calculations)
11. ✅ #16: TransactionService (double-entry validation)
12. ✅ #17: Laravel Sanctum Authentication
13. ✅ #18: Roles & Permissions (spatie/laravel-permission)
14. ✅ #19: Accounts REST API
15. ✅ #20: Transactions REST API

### Key Deliverables:
- 12 database migrations (including Sanctum & permissions)
- 7 Eloquent models with full relationships
- 2 core services (AccountService, TransactionService)
- 3 API controllers (Auth, Account, Transaction)
- 27 REST API endpoints
- 5 roles (owner, admin, accountant, bookkeeper, viewer)
- 50+ currencies seeded
- Full test suite passing

---

## 📁 Key Files Created in Phase 1

### Migrations:
- database/migrations/2026_01_31_000001_create_currencies_table.php
- database/migrations/2026_01_31_000002_create_companies_table.php
- database/migrations/2026_01_31_000003_create_company_user_table.php
- database/migrations/2026_01_31_000004_create_account_types_table.php
- database/migrations/2026_01_31_000005_create_accounts_table.php
- database/migrations/2026_01_31_000006_create_transactions_table.php
- database/migrations/2026_01_31_000007_create_splits_table.php

### Models:
- app/Models/Currency.php
- app/Models/Company.php
- app/Models/AccountType.php
- app/Models/Account.php
- app/Models/Transaction.php
- app/Models/Split.php
- app/Models/Concerns/BelongsToCompany.php (multi-tenancy trait)

### Services:
- app/Services/AccountService.php
- app/Services/TransactionService.php

### API Controllers:
- app/Http/Controllers/Api/AuthController.php
- app/Http/Controllers/Api/AccountController.php
- app/Http/Controllers/Api/TransactionController.php

### Seeders:
- database/seeders/CurrencySeeder.php
- database/seeders/AccountTypeSeeder.php
- database/seeders/RoleAndPermissionSeeder.php
---

## 📋 Next Steps (Phase 2)

1. Begin Phase 2: Double-Entry Logic
2. Issues #21-27 in the backlog
3. Implement transaction validation rules
4. Add balance calculations and reporting

---

## 👤 User Preferences

- Solo developer workflow
- Cloud-only deployment (no Docker/K8s)
- Offline can wait until Phase 2
- Multi-company support required in Phase 1
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

**Memory Updated:** 2026-01-31 20:00  
*Phase 1 Complete! Ready for Phase 2.*
