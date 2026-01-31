# Shillings - Development Phases & Milestones

**Last Updated:** January 30, 2026  
**Project Type:** Offline-First Multi-Platform Accounting Application  
**Status:** 🔄 Phase 1 - Planning & Setup

---

## 📋 Project Summary

**Shillings** is a modern accounting application that combines:
- **GnuCash's** double-entry accounting rigor
- **Akaunting's** modern web-based UX
- **Offline-first** architecture for web and mobile
- **Compose Multiplatform** for iOS/Android

### Key Decisions (January 30, 2026)
- ✅ Solo developer project
- ✅ Cloud-only deployment (no self-hosted Docker/K8s)
- ✅ Web-first, then mobile
- ✅ Offline functionality in Phase 2
- ✅ Mobile apps as subset of web initially
- ✅ Multi-company support in Phase 1
- ⏳ Investment tracking - later phases
- ⏳ Invoicing/billing - later phases
- ⏳ AI/automation - later phases
- ⏳ Migration tools - later phases

---

## 🎯 Phase Overview

| Phase | Name | Duration | Status | Key Deliverables |
|-------|------|----------|--------|------------------|
| **1** | Foundation & Core Accounting | 8-10 weeks | 🔄 In Progress | Database, Models, Basic CRUD, Auth |
| **2** | Web Application (Filament) | 6-8 weeks | ⏳ Upcoming | Admin Panel, Reports, Multi-Company |
| **3** | Mobile Foundation (Compose MP) | 6-8 weeks | ⏳ Upcoming | iOS/Android apps, Core features |
| **4** | Offline & Sync Engine | 8-10 weeks | ⏳ Upcoming | Offline mode, Sync, Conflict resolution |
| **5** | Business Features | 6-8 weeks | ⏳ Upcoming | Invoicing, A/R, A/P, Contacts |
| **6** | Advanced Features | 6-8 weeks | ⏳ Upcoming | Investments, Budgets, Reconciliation |
| **7** | Automation & AI | 4-6 weeks | ⏳ Future | Auto-categorization, OCR, Bank feeds |
| **8** | Polish & Launch | 4-6 weeks | ⏳ Future | Testing, Performance, Documentation |

**Total Estimated Duration:** 12-18 months (solo developer)

---

## 📍 Phase 1: Foundation & Core Accounting

**Timeline:** Weeks 1-10  
**Goal:** Build the accounting engine and data foundation  
**Status:** 🔄 In Progress

### Milestone 1.1: Project Setup (Week 1-2)
- [ ] Laravel 12 project initialization
- [ ] PostgreSQL database setup
- [ ] Filament 4.3 installation
- [ ] Git repository and CI/CD pipeline
- [ ] Development environment configuration
- [ ] Coding standards and linting setup

### Milestone 1.2: Database Schema (Week 2-4)
- [ ] Companies table (multi-tenancy)
- [ ] Users and authentication tables
- [ ] Commodities table (currencies, stocks)
- [ ] Accounts table (chart of accounts)
- [ ] Transactions table
- [ ] Splits table (GnuCash model)
- [ ] Prices table (exchange rates)
- [ ] Categories table
- [ ] Settings table
- [ ] Audit/activity log tables
- [ ] Database migrations
- [ ] Model relationships

### Milestone 1.3: Core Models & Services (Week 4-6)
- [ ] Company model with multi-tenancy trait
- [ ] User model with roles/permissions
- [ ] Account model with hierarchy
- [ ] Transaction model
- [ ] Split model with precision arithmetic
- [ ] Commodity model
- [ ] Price model
- [ ] Account service (balance calculations)
- [ ] Transaction service (double-entry enforcement)
- [ ] Currency service (exchange rates)

### Milestone 1.4: Authentication & Authorization (Week 6-7)
- [ ] User registration
- [ ] User login/logout
- [ ] Password reset
- [ ] Email verification
- [ ] Role-based access control (RBAC)
- [ ] Permission system
- [ ] Company-level isolation
- [ ] API authentication (Sanctum)

### Milestone 1.5: Basic API Endpoints (Week 7-8)
- [ ] Companies CRUD API
- [ ] Accounts CRUD API
- [ ] Transactions CRUD API
- [ ] Commodities API
- [ ] Prices API
- [ ] API documentation (OpenAPI/Swagger)
- [ ] API rate limiting

### Milestone 1.6: Double-Entry Engine (Week 8-10)
- [ ] Transaction validation (must balance)
- [ ] Split creation with precision math
- [ ] Account balance calculation
- [ ] Multi-currency transactions
- [ ] Currency conversion
- [ ] Transaction reversal
- [ ] Unit tests for accounting logic

### Phase 1 Deliverables
- ✅ Working database with all core tables
- ✅ Eloquent models with relationships
- ✅ Double-entry accounting engine
- ✅ Authentication system
- ✅ Basic REST API
- ✅ Multi-company support
- ✅ Test suite foundation

---

## 📍 Phase 2: Web Application (Filament Admin)

**Timeline:** Weeks 11-18  
**Goal:** Build the complete web-based admin panel  
**Status:** ⏳ Upcoming

### Milestone 2.1: Filament Setup (Week 11-12)
- [ ] Filament panel configuration
- [ ] Custom theme and branding
- [ ] Navigation structure
- [ ] Dashboard layout
- [ ] Multi-company switcher widget

### Milestone 2.2: Account Management (Week 12-14)
- [ ] Account list resource
- [ ] Account create/edit forms
- [ ] Account hierarchy tree view
- [ ] Account type management
- [ ] Account balance display
- [ ] Bulk account operations
- [ ] Account search/filter

### Milestone 2.3: Transaction Management (Week 14-16)
- [ ] Transaction list resource
- [ ] Transaction create form (with splits)
- [ ] Transaction edit form
- [ ] Split entry interface
- [ ] Transaction search/filter
- [ ] Transaction import (CSV)
- [ ] Transaction templates

### Milestone 2.4: Reports (Week 16-18)
- [ ] Balance Sheet report
- [ ] Profit & Loss report
- [ ] Cash Flow Statement
- [ ] Trial Balance
- [ ] General Ledger
- [ ] Account register view
- [ ] Report date range picker
- [ ] Report export (PDF, Excel)

### Milestone 2.5: Settings & Configuration (Week 18)
- [ ] Company settings page
- [ ] Currency management
- [ ] Category management
- [ ] User management
- [ ] Role/permission management
- [ ] Fiscal year settings
- [ ] Default account settings

### Phase 2 Deliverables
- ✅ Complete Filament admin panel
- ✅ Account management UI
- ✅ Transaction entry with splits
- ✅ Core financial reports
- ✅ Settings management
- ✅ Multi-company switching

---

## 📍 Phase 3: Mobile Foundation (Compose Multiplatform)

**Timeline:** Weeks 19-26  
**Goal:** Build iOS and Android apps with core features  
**Status:** ⏳ Upcoming

### Milestone 3.1: Project Setup (Week 19-20)
- [ ] Compose Multiplatform project structure
- [ ] Shared module setup
- [ ] iOS target configuration
- [ ] Android target configuration
- [ ] Dependencies (Ktor, Room, Koin)
- [ ] Build configuration

### Milestone 3.2: Core Architecture (Week 20-22)
- [ ] Repository pattern implementation
- [ ] API client (Ktor)
- [ ] Local database (Room/SQLDelight)
- [ ] Dependency injection (Koin)
- [ ] Navigation setup
- [ ] State management
- [ ] Error handling

### Milestone 3.3: Authentication (Week 22-23)
- [ ] Login screen
- [ ] Registration screen
- [ ] Biometric authentication
- [ ] Token storage (secure)
- [ ] Session management
- [ ] Auto-refresh tokens

### Milestone 3.4: Core Features (Week 23-25)
- [ ] Dashboard screen
- [ ] Account list screen
- [ ] Account detail screen
- [ ] Transaction list screen
- [ ] Transaction entry screen
- [ ] Company switcher
- [ ] Search functionality

### Milestone 3.5: Polish & Testing (Week 25-26)
- [ ] UI polish and animations
- [ ] Dark mode support
- [ ] Accessibility
- [ ] Unit tests
- [ ] UI tests
- [ ] Beta testing setup

### Phase 3 Deliverables
- ✅ iOS app (TestFlight ready)
- ✅ Android app (Play Store ready)
- ✅ Core accounting features on mobile
- ✅ Biometric authentication
- ✅ Company switching

---

## 📍 Phase 4: Offline & Sync Engine

**Timeline:** Weeks 27-36  
**Goal:** Full offline functionality and cross-device sync  
**Status:** ⏳ Upcoming

### Milestone 4.1: Sync Architecture (Week 27-28)
- [ ] Sync protocol design
- [ ] Conflict resolution strategy
- [ ] Version tracking system
- [ ] Sync queue tables
- [ ] Device registration API

### Milestone 4.2: Server-Side Sync (Week 28-30)
- [ ] Sync endpoint (pull)
- [ ] Sync endpoint (push)
- [ ] Conflict detection
- [ ] Conflict resolution
- [ ] Sync logging
- [ ] Batch processing

### Milestone 4.3: Mobile Offline (Week 30-33)
- [ ] Local database sync schema
- [ ] Offline-first repository pattern
- [ ] Background sync service
- [ ] Sync status UI
- [ ] Conflict resolution UI
- [ ] Offline indicator
- [ ] Queue management

### Milestone 4.4: Web Offline (PWA) (Week 33-35)
- [ ] Service Worker setup
- [ ] IndexedDB schema
- [ ] Offline storage (Dexie.js)
- [ ] Sync engine (JavaScript)
- [ ] PWA manifest
- [ ] Offline UI indicators
- [ ] Background sync API

### Milestone 4.5: Cached Authentication (Week 35-36)
- [ ] Encrypted credential storage
- [ ] Offline login flow
- [ ] Token refresh on reconnect
- [ ] Device verification
- [ ] Security audit

### Phase 4 Deliverables
- ✅ Full offline mode (web and mobile)
- ✅ Automatic sync when online
- ✅ Conflict resolution
- ✅ No sign-in when offline
- ✅ PWA capabilities

---

## 📍 Phase 5: Business Features

**Timeline:** Weeks 37-44  
**Goal:** Add invoicing, A/R, A/P, and contact management  
**Status:** ⏳ Upcoming

### Milestone 5.1: Contact Management (Week 37-38)
- [ ] Contact model (customer/vendor)
- [ ] Contact CRUD API
- [ ] Contact Filament resource
- [ ] Contact mobile screens
- [ ] Contact import (CSV)

### Milestone 5.2: Invoicing (Week 38-41)
- [ ] Document model (invoice/bill)
- [ ] Document item model
- [ ] Invoice creation UI
- [ ] Invoice templates
- [ ] Invoice PDF generation
- [ ] Invoice email
- [ ] Invoice status tracking

### Milestone 5.3: Accounts Receivable (Week 41-42)
- [ ] Customer balances
- [ ] Aging report
- [ ] Payment receipt
- [ ] Payment application
- [ ] Overdue notifications

### Milestone 5.4: Accounts Payable (Week 42-44)
- [ ] Vendor bills
- [ ] Bill payment
- [ ] Bill aging report
- [ ] Payment scheduling
- [ ] Bill reminders

### Phase 5 Deliverables
- ✅ Customer/vendor management
- ✅ Professional invoicing
- ✅ A/R and A/P tracking
- ✅ Aging reports
- ✅ Payment processing

---

## 📍 Phase 6: Advanced Features

**Timeline:** Weeks 45-52  
**Goal:** Investments, budgets, reconciliation  
**Status:** ⏳ Upcoming

### Milestone 6.1: Budgeting (Week 45-47)
- [ ] Budget model
- [ ] Budget allocation model
- [ ] Budget creation UI
- [ ] Budget vs actual report
- [ ] Budget alerts

### Milestone 6.2: Bank Reconciliation (Week 47-49)
- [ ] Reconciliation model
- [ ] Reconciliation workflow UI
- [ ] Statement import
- [ ] Auto-matching
- [ ] Reconciliation report

### Milestone 6.3: Investment Tracking (Week 49-51)
- [ ] Lot model
- [ ] Stock/fund account types
- [ ] Buy/sell transactions
- [ ] Dividend tracking
- [ ] Capital gains calculation
- [ ] Portfolio report

### Milestone 6.4: Scheduled Transactions (Week 51-52)
- [ ] Scheduled transaction model
- [ ] Recurrence patterns
- [ ] Auto-creation
- [ ] Reminder notifications
- [ ] Schedule management UI

### Phase 6 Deliverables
- ✅ Budget management
- ✅ Bank reconciliation
- ✅ Investment portfolio tracking
- ✅ Scheduled/recurring transactions

---

## 📍 Phase 7: Automation & AI

**Timeline:** Weeks 53-58  
**Goal:** Smart categorization, OCR, bank feeds  
**Status:** ⏳ Future

### Milestone 7.1: Auto-Categorization (Week 53-54)
- [ ] Rule-based categorization
- [ ] ML model for suggestions
- [ ] Category training from history
- [ ] Bulk categorization

### Milestone 7.2: Receipt Scanning (Week 54-56)
- [ ] Camera integration (mobile)
- [ ] OCR service integration
- [ ] Data extraction
- [ ] Receipt attachment

### Milestone 7.3: Bank Feed Integration (Week 56-58)
- [ ] Plaid/Teller integration
- [ ] Bank account connection
- [ ] Transaction import
- [ ] Auto-matching
- [ ] Sync scheduling

### Phase 7 Deliverables
- ✅ Smart transaction categorization
- ✅ Receipt OCR scanning
- ✅ Automated bank feeds

---

## 📍 Phase 8: Polish & Launch

**Timeline:** Weeks 59-64  
**Goal:** Testing, optimization, documentation, launch  
**Status:** ⏳ Future

### Milestone 8.1: Testing (Week 59-60)
- [ ] Comprehensive test coverage
- [ ] Performance testing
- [ ] Security audit
- [ ] Accessibility audit
- [ ] Cross-browser testing
- [ ] Mobile device testing

### Milestone 8.2: Performance Optimization (Week 60-61)
- [ ] Database query optimization
- [ ] API response caching
- [ ] Frontend bundle optimization
- [ ] Mobile app size reduction
- [ ] Lazy loading implementation

### Milestone 8.3: Documentation (Week 61-62)
- [ ] User documentation
- [ ] API documentation
- [ ] Developer documentation
- [ ] Video tutorials
- [ ] FAQ/Help center

### Milestone 8.4: Launch Preparation (Week 62-64)
- [ ] Production environment setup
- [ ] Monitoring and alerting
- [ ] Backup and recovery
- [ ] App store submissions
- [ ] Marketing website
- [ ] Beta testing feedback
- [ ] Launch!

### Phase 8 Deliverables
- ✅ Production-ready application
- ✅ Complete documentation
- ✅ iOS App Store listing
- ✅ Google Play Store listing
- ✅ Web application launched

---

## 📊 Progress Tracking

### Current Status
- **Phase:** 1 - Foundation & Core Accounting
- **Milestone:** 1.1 - Project Setup
- **Completion:** 10%
- **Last Updated:** January 30, 2026

### Quick Stats
- **Total Issues:** 0 created / ~200 planned
- **Issues Completed:** 0
- **Current Sprint:** Planning
- **Blockers:** None

---

## 🔗 Related Documents

- [Akaunting Analysis](planning/AKAUNTING_ANALYSIS.md)
- [GnuCash Analysis](planning/GNUCASH_ANALYSIS.md)
- [Feature Comparison](planning/FEATURE_COMPARISON.md)
- [Offline-First Architecture](architecture/OFFLINE_FIRST_ARCHITECTURE.md)
- [Issues Backlog](planning/ISSUES_BACKLOG.md)
- [Project Board](https://github.com/users/maxymurm/projects/XXX)

---

## 📝 Revision History

| Date | Changes | Author |
|------|---------|--------|
| 2026-01-30 | Initial phase planning based on Akaunting & GnuCash analysis | Agent |
| 2026-01-30 | Added offline/mobile clarifications from user | Agent |

---

**End of Phase Planning**  
*This document is maintained by AI agents and updated throughout development*
