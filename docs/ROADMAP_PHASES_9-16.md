# Shillings Roadmap: Phases 9-16

> Based on analysis of **Akaunting** (Laravel accounting) and **GnuCash** (desktop accounting), scoped for Shillings' offline-first, GnuCash-style precision architecture.

---

## Executive Summary

| Phase | Name | Issues | Priority | Complexity |
|-------|------|--------|----------|------------|
| 9 | Contacts & Parties | 8 | High | Medium |
| 10 | Invoicing & Documents | 10 | High | High |
| 11 | Budgeting | 7 | Medium | Medium |
| 12 | Banking & Import/Export | 9 | High | High |
| 13 | Advanced Reporting | 8 | Medium | Medium |
| 14 | Scheduled Transactions | 6 | Medium | Medium |
| 15 | Tax Management | 7 | Medium | High |
| 16 | Mobile & Offline Sync | 8 | High | Very High |

**Total New Issues: 63**

---

## Phase 9: Contacts & Parties (8 issues)
> **Goal:** Add customer, vendor, and employee management like Akaunting/GnuCash

### From Akaunting:
- `Contact` model with types (customer, vendor, employee)
- Contact persons (multiple per contact)
- Contact addresses & locations

### From GnuCash:
- `gncCustomer`, `gncVendor`, `gncEmployee` entities
- Address handling (`gncAddress`)
- Owner relationships for invoices/bills

### Issues:
| # | Title | Description | Labels |
|---|-------|-------------|--------|
| 65 | Create contacts migration & model | UUID, company_id, type (customer/vendor/employee), name, email, phone, tax_number, address fields, currency_code, enabled, reference | `database`, `priority:high` |
| 66 | Create contact_persons table | Multiple contacts per organization - name, email, phone, position, is_primary | `database` |
| 67 | Create addresses migration | Polymorphic addresses - street, city, state, zip, country, type (billing/shipping) | `database` |
| 68 | ContactService implementation | CRUD, validation, duplicate detection, merge contacts | `backend`, `service` |
| 69 | Contacts REST API | Full CRUD endpoints with filtering, search, pagination | `api` |
| 70 | Filament ContactResource | List, create, edit, view with tabs for persons/addresses/history | `admin-panel` |
| 71 | Contact balance tracking | Track customer receivables, vendor payables per contact | `backend` |
| 72 | Contact import/export | CSV import with mapping, Excel export | `feature` |

---

## Phase 10: Invoicing & Documents (10 issues)
> **Goal:** Full invoicing system like Akaunting with GnuCash precision

### From Akaunting:
- `Document` model (invoices, bills, quotes, orders)
- `DocumentItem` with taxes
- Status workflow (draft → sent → paid → cancelled)
- PDF generation, email sending

### From GnuCash:
- `gncInvoice` with entries (`gncEntry`)
- Bill terms (`gncBillTerm`)
- Jobs for project tracking

### Issues:
| # | Title | Description | Labels |
|---|-------|-------------|--------|
| 73 | Create documents migration | UUID, company_id, type (invoice/bill/quote/credit-note), document_number, contact_id, status, issued_at, due_at, currency, amounts using num/denom | `database`, `priority:high` |
| 74 | Create document_items table | line items - quantity, price_num/denom, tax_id, account_id, description | `database` |
| 75 | Create bill_terms migration | Payment terms - name, due_days, discount_days, discount_percent | `database` |
| 76 | DocumentService implementation | Number generation, total calculation, status transitions, posting to journal | `backend`, `service`, `priority:high` |
| 77 | Invoice REST API | CRUD, status updates, payment recording, PDF generation | `api` |
| 78 | Filament InvoiceResource | Full invoice management with line items, preview, email | `admin-panel` |
| 79 | Filament BillResource | Vendor bills/purchase orders with approval workflow | `admin-panel` |
| 80 | PDF invoice generation | Blade templates, company branding, multiple templates | `feature` |
| 81 | Email invoice functionality | Queue-based email with attachments, tracking | `feature` |
| 82 | Payment recording & matching | Record payments, auto-match to invoices, partial payments | `backend`, `priority:high` |

---

## Phase 11: Budgeting (7 issues)
> **Goal:** Full budgeting system like GnuCash with period comparisons

### From GnuCash:
- `GncBudget` with periods (monthly, quarterly, yearly)
- Budget values per account per period
- Inclusive of sub-accounts option
- Budget vs Actual reports

### Issues:
| # | Title | Description | Labels |
|---|-------|-------------|--------|
| 83 | Create budgets migration | UUID, company_id, name, description, fiscal_year, num_periods, recurrence | `database` |
| 84 | Create budget_accounts table | budget_id, account_id, period_num, amount_num/denom | `database` |
| 85 | BudgetService implementation | Create, clone, rollover budgets, calculate variances | `backend`, `service` |
| 86 | Budget REST API | CRUD, period values, variance calculations | `api` |
| 87 | Filament BudgetResource | Budget entry grid, copy from previous, account selection | `admin-panel` |
| 88 | Budget vs Actual report | Period comparison, variance analysis, % of budget | `report` |
| 89 | Budget forecasting widget | Dashboard widget showing budget progress, alerts | `dashboard` |

---

## Phase 12: Banking & Import/Export (9 issues)
> **Goal:** Bank connections, transaction import matching like GnuCash/Akaunting

### From GnuCash:
- OFX/QIF import with smart matching
- `import-account-matcher` for auto-categorization
- Reconciliation workflows

### From Akaunting:
- Bank connections (Plaid/Yodlee style)
- Transaction matching algorithms
- Transfer tracking between accounts

### Issues:
| # | Title | Description | Labels |
|---|-------|-------------|--------|
| 90 | Create bank_connections table | account_id, provider, credentials (encrypted), last_sync, status | `database` |
| 91 | Create import_batches table | Track imports - file, status, matched/created counts, errors | `database` |
| 92 | OFX file parser | Parse OFX/QFX bank files, extract transactions | `backend`, `priority:high` |
| 93 | CSV import with mapping | Flexible CSV import, column mapping, date formats | `backend` |
| 94 | Transaction matching engine | Smart matching by amount, date, description, reference | `backend`, `priority:high` |
| 95 | ImportService implementation | Batch processing, duplicate detection, rule-based categorization | `backend`, `service` |
| 96 | Filament import wizard | Multi-step import: upload → map → review → confirm | `admin-panel` |
| 97 | Export to common formats | CSV, OFX, Excel, PDF statement exports | `feature` |
| 98 | GnuCash XML import | Import existing GnuCash files for migration | `feature`, `priority:medium` |

---

## Phase 13: Advanced Reporting (8 issues)
> **Goal:** Comprehensive reports like GnuCash's 40+ report types

### From GnuCash:
- General Ledger, Account Register
- Equity Statement, Portfolio reports
- Transaction reports with filtering
- Aging reports (A/R, A/P)

### From Akaunting:
- Tax summary reports
- Profit & Loss comparisons
- Expense/Income summaries by category

### Issues:
| # | Title | Description | Labels |
|---|-------|-------------|--------|
| 99 | General Ledger report | All transactions by account with running balances | `report` |
| 100 | Account Register report | Single account detailed register with reconciliation marks | `report` |
| 101 | Accounts Receivable Aging | Customer aging by 30/60/90/120+ days | `report`, `priority:high` |
| 102 | Accounts Payable Aging | Vendor aging by 30/60/90/120+ days | `report`, `priority:high` |
| 103 | Tax Summary report | Tax collected/paid by period, category, tax rate | `report` |
| 104 | Equity Statement | Changes in equity over period | `report` |
| 105 | Comparative reports | Period-over-period comparison for any report | `feature` |
| 106 | Custom report builder | User-defined reports with saved filters, column selection | `feature`, `priority:medium` |

---

## Phase 14: Scheduled Transactions (6 issues)
> **Goal:** Recurring transactions like GnuCash's SchedXaction

### From GnuCash:
- `SchedXaction` with recurrence patterns
- Template transactions
- Auto-create or remind options
- Last/next occurrence tracking

### From Akaunting:
- `Recurring` model for invoices/bills
- Frequency, interval, limits
- Auto-send options

### Issues:
| # | Title | Description | Labels |
|---|-------|-------------|--------|
| 107 | Enhance scheduled_transactions | Add reminder_days, auto_create flag, last_created_at, occurrence_count | `database` |
| 108 | RecurrenceService | Calculate next dates, handle complex patterns (last day of month, etc.) | `backend`, `service` |
| 109 | Scheduled transaction processor | Artisan command to create due transactions, queue job | `backend`, `priority:high` |
| 110 | Filament ScheduledTransactionResource | Full UI for recurring setup with preview | `admin-panel` |
| 111 | Notification for upcoming scheduled | Email/in-app notifications for upcoming transactions | `feature` |
| 112 | Scheduled transaction reports | List upcoming, overdue, history of created transactions | `report` |

---

## Phase 15: Tax Management (7 issues)
> **Goal:** Comprehensive tax handling for multiple jurisdictions

### From Akaunting:
- `Tax` model with rates, compounds
- Tax summaries and reports
- Tax-inclusive/exclusive pricing

### From GnuCash:
- `gncTaxTable` for tax rates
- Tax line items tracking
- Country-specific tax reports

### Issues:
| # | Title | Description | Labels |
|---|-------|-------------|--------|
| 113 | Create taxes migration | UUID, company_id, name, rate_num/denom, type (percentage/fixed), is_compound, account_id | `database` |
| 114 | Create tax_rules table | Rules for automatic tax application - account, contact type, region | `database` |
| 115 | TaxService implementation | Tax calculation, compound handling, reverse charge | `backend`, `service` |
| 116 | Tax REST API | CRUD for taxes and rules | `api` |
| 117 | Filament TaxResource | Tax rate management with rules | `admin-panel` |
| 118 | VAT/GST return report | Tax period summary for filing | `report`, `priority:high` |
| 119 | Multi-jurisdiction tax | Handle multiple tax rates per transaction (state + federal) | `feature` |

---

## Phase 16: Mobile & Offline Sync (8 issues)
> **Goal:** PWA with offline-first architecture, background sync

### Architecture Decisions:
- Service Worker for offline caching
- IndexedDB for local transaction storage
- Background sync when online
- Conflict resolution strategy

### Issues:
| # | Title | Description | Labels |
|---|-------|-------------|--------|
| 120 | PWA manifest & service worker | Install prompts, offline page, asset caching | `frontend`, `priority:high` |
| 121 | IndexedDB local storage | Store transactions, accounts locally with encryption | `frontend`, `priority:high` |
| 122 | Offline transaction entry | Queue transactions while offline | `frontend` |
| 123 | Background sync service | Sync queued changes when back online | `backend`, `frontend`, `priority:high` |
| 124 | Conflict resolution | Last-write-wins with audit trail, manual merge UI | `backend`, `priority:critical` |
| 125 | Mobile-optimized Filament | Responsive tweaks, touch-friendly interactions | `admin-panel` |
| 126 | Push notifications | Transaction reminders, sync status, balance alerts | `feature` |
| 127 | Mobile receipt capture | Camera upload, OCR integration (optional), attach to transactions | `feature` |

---

## Implementation Priority Order

### Tier 1: Core Business Features (Phases 9-10)
- Contacts are required for invoicing
- Invoicing is essential for accounts receivable/payable
- **Estimated: 4-6 weeks**

### Tier 2: Financial Planning (Phases 11, 14)
- Budgeting adds planning capabilities
- Scheduled transactions automate recurring entries
- **Estimated: 3-4 weeks**

### Tier 3: Banking Integration (Phase 12)
- Import/export critical for data migration
- Bank matching saves time on reconciliation
- **Estimated: 3-4 weeks**

### Tier 4: Advanced Features (Phases 13, 15)
- Advanced reports for compliance
- Tax management for multi-jurisdiction
- **Estimated: 3-4 weeks**

### Tier 5: Mobile/Offline (Phase 16)
- Complex architecture, defer until core stable
- Requires significant frontend work
- **Estimated: 4-6 weeks**

---

## Technical Considerations

### Database Schema Patterns (from analysis):
1. **Amount Storage**: Always use `amount_num`/`amount_denom` pairs (GnuCash style)
2. **Multi-tenancy**: All tables have `company_id` foreign key
3. **Soft Deletes**: Use for all financial records
4. **Audit Trail**: Add `created_by`, `updated_by`, `created_from` columns

### API Consistency:
- RESTful endpoints following Phase 1-8 patterns
- Sanctum abilities per resource
- JSON:API style responses with relationships

### Filament Patterns:
- Resources with custom forms/tables
- Widgets for dashboards
- Actions for status changes
- Export/Import actions

---

## Feature Comparison Matrix

| Feature | GnuCash | Akaunting | Shillings (Current) | Shillings (Planned) |
|---------|---------|-----------|---------------------|---------------------|
| Double-entry | ✅ | ✅ | ✅ | ✅ |
| Precision arithmetic | ✅ (num/denom) | ❌ (float) | ✅ (num/denom) | ✅ |
| Multi-currency | ✅ | ✅ | ✅ | ✅ |
| Multi-company | ❌ | ✅ | ✅ | ✅ |
| Contacts (CRM) | ✅ | ✅ | ❌ | Phase 9 |
| Invoicing | ✅ | ✅ | ❌ | Phase 10 |
| Budgeting | ✅ | ❌ | ❌ | Phase 11 |
| Bank import (OFX) | ✅ | ❌ | ❌ | Phase 12 |
| Scheduled transactions | ✅ | ✅ | Partial | Phase 14 |
| Tax management | ✅ | ✅ | ❌ | Phase 15 |
| Offline support | ✅ (desktop) | ❌ | ❌ | Phase 16 |
| REST API | ❌ | ✅ | ✅ | ✅ |
| Admin panel | ❌ | ✅ | ✅ | ✅ |

---

## Next Steps

1. **Create GitHub Milestones** for Phases 9-16
2. **Create Issues** with labels and assignments
3. **Prioritize** based on user feedback
4. **Begin Phase 9** (Contacts) as foundation for invoicing

---

*Document Created: 2026-02-01*  
*Based on analysis of Akaunting 3.x and GnuCash 5.x*
