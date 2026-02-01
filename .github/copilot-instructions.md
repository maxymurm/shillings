---
applyTo: '**'
lastUpdated: '2026-01-31 21:15:00'
chatSession: 'session-004'
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

**Active Phase:** Phase 9 - Contacts & Parties (Pending)
**Active Milestone:** None - Roadmap scoped, awaiting issue creation
**Current Branch:** develop  
**Last Activity:** 2026-02-01 - Scoped Phases 9-16 (63 new issues)

**GitHub Milestones (COMPLETED):**
1. ✅ Phase 1: Database Schema & Core Models (15 issues)
2. ✅ Phase 2: Double-Entry Logic (7 issues)
3. ✅ Phase 3: Financial Reports & Queries (7 issues)
4. ✅ Phase 4: Admin Panel - Accounts (6 issues)
5. ✅ Phase 5: Transactions & Reconciliation (6 issues)
6. ✅ Phase 6: Reports & Charts (6 issues)
7. ✅ Phase 7: Testing & QA (6 issues)
8. ✅ Phase 8: Deployment & Documentation (6 issues)

**GitHub Milestones (PLANNED - see docs/ROADMAP_PHASES_9-16.md):**
9. 🔲 Phase 9: Contacts & Parties (8 issues)
10. 🔲 Phase 10: Invoicing & Documents (10 issues)
11. 🔲 Phase 11: Budgeting (7 issues)
12. 🔲 Phase 12: Banking & Import/Export (9 issues)
13. 🔲 Phase 13: Advanced Reporting (8 issues)
14. 🔲 Phase 14: Scheduled Transactions (6 issues)
15. 🔲 Phase 15: Tax Management (7 issues)
16. 🔲 Phase 16: Mobile & Offline Sync (8 issues)

---

## ✅ Project Completion Summary (Phases 1-8)

### Test Coverage:
- **105 tests passing** (193 assertions)
- Unit tests: 72 (Models, Services, Value Objects)
- Feature tests: 33 (API, Security, Performance)
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

**Memory Updated:** 2026-02-01 21:15  
*Phases 9-16 scoped (63 new issues) based on Akaunting/GnuCash analysis. See docs/ROADMAP_PHASES_9-16.md*
