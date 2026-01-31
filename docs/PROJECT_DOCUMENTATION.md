# Shillings - Development Documentation

**Last Updated:** January 30, 2026 17:30  
**Current Phase:** Phase 1: Foundation & Core Accounting  
**Status:** 🟢 Active Development

---

## 📊 Project Overview

### Description
Shillings is a modern, offline-first, multi-platform accounting application that combines the accounting rigor of GnuCash (double-entry, splits, precision arithmetic) with the modern UX of Akaunting (web-based, intuitive), plus industry-leading offline capabilities for both web and mobile platforms.

### Technology Stack
- **Backend:** Laravel 12 + PHP 8.3
- **Database:** PostgreSQL 16
- **Admin Panel:** Filament 4.3
- **Frontend:** Livewire 3 + Tailwind CSS 4
- **Mobile:** Compose Multiplatform (iOS/Android)
- **Offline Web:** IndexedDB + Service Workers (PWA)
- **API:** REST (Laravel Sanctum) + GraphQL (future)
- **Deployment:** Cloud-hosted (Laravel Forge/Vapor)
- **CI/CD:** GitHub Actions

### Key Features
- ✅ Pure double-entry bookkeeping (GnuCash model)
- ✅ Split-based transactions with precision arithmetic
- ✅ Multi-currency with historical exchange rates
- ✅ Multi-company support
- ✅ Hierarchical chart of accounts
- ⏳ Offline-first mobile apps (Compose Multiplatform)
- ⏳ Offline web via PWA
- ⏳ Cross-device sync with conflict resolution
- ⏳ No sign-in required when offline

### Repository
- **GitHub:** https://github.com/maxymurm/shillings
- **Project Board:** https://github.com/users/maxymurm/projects/[NUMBER]
- **Production:** [TBD]
- **Staging:** [TBD]

### Team
- **Developer:** Maxwell Murunga (@maxymurm)
- **Company:** Advent Digital

---

## 🎯 Phase Breakdown

### Phase 1: Foundation & Core Accounting [🔄 IN PROGRESS]
**Timeline:** Weeks 1-10 (~8-10 weeks)  
**Status:** 10% - Planning Complete

**Milestones:**
| ID | Milestone | Status | Issues |
|----|-----------|--------|--------|
| 1.1 | Project Setup | ⏳ Ready | 7 issues |
| 1.2 | Database Schema | ⏳ Ready | 13 issues |
| 1.3 | Core Models & Services | ⏳ Ready | 10 issues |
| 1.4 | Authentication & Authorization | ⏳ Ready | 8 issues |
| 1.5 | Basic API Endpoints | ⏳ Ready | 7 issues |
| 1.6 | Double-Entry Engine | ⏳ Ready | 9 issues |

**Key Deliverables:**
- [ ] Working database with all core tables
- [ ] Eloquent models with relationships
- [ ] Double-entry accounting engine with precision math
- [ ] Authentication system with RBAC
- [ ] Basic REST API
- [ ] Multi-company support
- [ ] Test suite foundation

---

### Phase 2: Web Application (Filament Admin) [⏳ UPCOMING]
**Timeline:** Weeks 11-18 (~6-8 weeks)  
**Status:** 0% - Not Started

**Milestones:**
| ID | Milestone | Status | Issues |
|----|-----------|--------|--------|
| 2.1 | Filament Setup | ⏳ Planned | 4 issues |
| 2.2 | Account Management | ⏳ Planned | 4 issues |
| 2.3 | Transaction Management | ⏳ Planned | 6 issues |
| 2.4 | Reports | ⏳ Planned | 6 issues |
| 2.5 | Settings & Configuration | ⏳ Planned | 6 issues |

**Key Deliverables:**
- [ ] Complete Filament admin panel
- [ ] Account management UI with tree view
- [ ] Transaction entry with splits
- [ ] Core financial reports (BS, P&L, CF)
- [ ] Company and user settings

---

### Phase 3: Mobile Foundation (Compose Multiplatform) [⏳ UPCOMING]
**Timeline:** Weeks 19-26 (~6-8 weeks)  
**Status:** 0% - Not Started

**Key Deliverables:**
- [ ] iOS app (TestFlight ready)
- [ ] Android app (Play Store ready)
- [ ] Core accounting features on mobile
- [ ] Biometric authentication
- [ ] Company switching

---

### Phase 4: Offline & Sync Engine [⏳ UPCOMING]
**Timeline:** Weeks 27-36 (~8-10 weeks)  
**Status:** 0% - Not Started

**Key Deliverables:**
- [ ] Full offline mode (web and mobile)
- [ ] Automatic sync when online
- [ ] Conflict resolution
- [ ] No sign-in when offline (cached auth)
- [ ] PWA capabilities

---

### Phase 5: Business Features [⏳ UPCOMING]
**Timeline:** Weeks 37-44 (~6-8 weeks)  
**Status:** 0% - Not Started

**Key Deliverables:**
- [ ] Customer/vendor management
- [ ] Professional invoicing
- [ ] A/R and A/P tracking
- [ ] Aging reports
- [ ] Payment processing

---

### Phase 6: Advanced Features [⏳ UPCOMING]
**Timeline:** Weeks 45-52 (~6-8 weeks)  
**Status:** 0% - Not Started

**Key Deliverables:**
- [ ] Budget management
- [ ] Bank reconciliation
- [ ] Investment portfolio tracking
- [ ] Scheduled/recurring transactions

---

### Phase 7: Automation & AI [⏳ FUTURE]
**Timeline:** Weeks 53-58 (~4-6 weeks)  
**Status:** 0% - Not Started

**Key Deliverables:**
- [ ] Smart transaction categorization
- [ ] Receipt OCR scanning
- [ ] Automated bank feeds (Plaid)

---

### Phase 8: Polish & Launch [⏳ FUTURE]
**Timeline:** Weeks 59-64 (~4-6 weeks)  
**Status:** 0% - Not Started

**Key Deliverables:**
- [ ] Production-ready application
- [ ] Complete documentation
- [ ] iOS App Store listing
- [ ] Google Play Store listing
- [ ] Web application launched

---

## 📝 Change Log

### 2026-01-30

#### 17:30 - Project Planning Complete
**Type:** Documentation  
**Branch:** main

**Changes:**
- Created comprehensive project documentation
- Analyzed Akaunting and GnuCash for feature porting
- Designed offline-first sync architecture
- Defined 8 phases with detailed milestones
- Created 80+ GitHub issues for Phase 1-2
- Built issue automation script (create_issues.ps1)

**Files Created:**
- `docs/planning/AKAUNTING_ANALYSIS.md`
- `docs/planning/GNUCASH_ANALYSIS.md`
- `docs/planning/FEATURE_COMPARISON.md`
- `docs/planning/PHASES_AND_MILESTONES.md`
- `docs/planning/ISSUES_BACKLOG.md`
- `docs/architecture/OFFLINE_FIRST_ARCHITECTURE.md`
- `agents/create_issues.ps1`

**Notes:**
Planning phase complete. Ready to begin development with Phase 1: Foundation & Core Accounting.

---

## 🏗️ Architecture Decisions

### Database Design
- **ORM:** Eloquent (Laravel 12)
- **Primary Keys:** UUIDs (offline-first compatibility)
- **Multi-tenancy:** company_id scoping on all entities
- **Soft Deletes:** All core models (audit trail)
- **Version Tracking:** sync_version for conflict resolution
- **Migrations:** Sequential Laravel migrations

### Accounting Model (GnuCash-style)
```
Account (tree structure with parent_id)
  └── Split (links account to transaction portion)
       └── Transaction (groups related splits)
```
- **Precision Math:** numerator/denominator fractions (not floating point)
- **Double-Entry Enforcement:** All splits in a transaction MUST sum to zero
- **Account Types:** ASSET, LIABILITY, INCOME, EXPENSE, EQUITY
- **Debit/Credit:** Explicit action field on splits

### API Design
- **Style:** RESTful with resource-based endpoints
- **Authentication:** Laravel Sanctum (token-based)
- **Versioning:** URI-based (/api/v1/...)
- **Response Format:** JSON with standard envelope

**Standard Response:**
```json
{
  "success": true,
  "data": {},
  "message": "Success message",
  "meta": { "pagination": {} }
}
```

### Sync Architecture
- **Strategy:** Bidirectional with version vectors
- **Offline Storage:** IndexedDB (web), Room/SQLDelight (mobile)
- **Conflict Resolution:** Last-Writer-Wins with smart merge
- **Queue:** sync_operations table for pending changes
- **Auth:** Cached encrypted credentials (30-day expiry)

---

## 🔧 Setup Instructions

### Prerequisites
- PHP 8.3+
- Composer 2.x
- PostgreSQL 16+
- Node.js 20+ & npm
- Git

### Local Development Setup

1. **Clone repository:**
   ```bash
   git clone https://github.com/maxymurm/shillings.git
   cd shillings
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure environment variables in .env:**
   ```
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=shillings
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Database setup:**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Start development server:**
   ```bash
   php artisan serve
   npm run dev
   ```

7. **Access application:**
   - Web App: http://localhost:8000
   - Admin Panel: http://localhost:8000/admin
   - API: http://localhost:8000/api/v1

### Running Tests
```bash
php artisan test
php artisan test --coverage
```

---

## 📱 API Documentation

### Base URL
```
[Local] http://localhost:8000/api/v1
[Production] https://api.shillings.app/v1
```

### Authentication
All API endpoints require Bearer token authentication via Laravel Sanctum.

```
Authorization: Bearer {token}
```

### Core Resources (Phase 1)
- `GET /companies` - List user's companies
- `GET /accounts` - List accounts (supports tree view)
- `POST /accounts` - Create account
- `GET /transactions` - List transactions
- `POST /transactions` - Create transaction with splits
- `GET /splits` - List splits (filtered by account/transaction)

### Standard Response Format
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful",
  "meta": {
    "pagination": {
      "current_page": 1,
      "total": 100
    }
  }
}
```

*Full API documentation will be generated with OpenAPI/Swagger once endpoints are built.*

---

## 🧪 Testing

### Test Strategy
- **Unit Tests:** Models, Services, Accounting Engine
- **Feature Tests:** API Endpoints, Auth flows
- **Browser Tests:** Filament admin (Dusk)
- **Mobile Tests:** Compose Multiplatform tests

### Running Tests
```bash
# All tests
php artisan test

# With coverage
php artisan test --coverage

# Specific suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

### Test Files
- Unit tests: `tests/Unit/`
- Feature tests: `tests/Feature/`
- Browser tests: `tests/Browser/`

---

## 🚀 Deployment

### Cloud Deployment (Laravel Forge/Vapor)
```bash
# Production deployment via Forge
git push origin main  # Auto-deploys via webhook
```

### Environment Variables (Production)
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://shillings.app`
- Database credentials (managed by Forge)
- API keys (managed via Forge secrets)

---

## 📋 Next Steps

### Immediate (This Week)
1. Create GitHub issues using `agents/create_issues.ps1`
2. Set up GitHub Project Board
3. Begin Milestone 1.1: Project Setup

### Short Term (Next 2 Weeks)
1. Complete database migrations (Milestone 1.2)
2. Build core Eloquent models (Milestone 1.3)
3. Set up authentication system (Milestone 1.4)

### Long Term (Next Month+)
1. Complete Phase 1 (Foundation & Core Accounting)
2. Begin Phase 2 (Filament Admin Panel)
3. Start mobile app planning (Phase 3)

---

## 🐛 Known Issues

### Current Bugs
- None yet (fresh project)

### Technical Debt
- [ ] Set up proper CI/CD pipeline
- [ ] Configure code quality tools (PHPStan, Pint)
- [ ] Add comprehensive test coverage

---

## 📖 Additional Documentation

### Planning
- [Feature Comparison: `docs/planning/FEATURE_COMPARISON.md`](planning/FEATURE_COMPARISON.md)
- [Akaunting Analysis: `docs/planning/AKAUNTING_ANALYSIS.md`](planning/AKAUNTING_ANALYSIS.md)
- [GnuCash Analysis: `docs/planning/GNUCASH_ANALYSIS.md`](planning/GNUCASH_ANALYSIS.md)
- [Phases & Milestones: `docs/planning/PHASES_AND_MILESTONES.md`](planning/PHASES_AND_MILESTONES.md)
- [Issues Backlog: `docs/planning/ISSUES_BACKLOG.md`](planning/ISSUES_BACKLOG.md)

### Architecture
- [Offline-First Architecture: `docs/architecture/OFFLINE_FIRST_ARCHITECTURE.md`](architecture/OFFLINE_FIRST_ARCHITECTURE.md)

### Agents
- [Agent Memory Template: `agents/MEMORY_TEMPLATE.md`](../agents/MEMORY_TEMPLATE.md)
- [Issue Creation Script: `agents/create_issues.ps1`](../agents/create_issues.ps1)

---

## 👥 Team & Contacts

**Developer:** Maxwell Murunga  
**Email:** maxmm@adventit.digital  
**GitHub:** @maxymurm  
**Company:** Advent Digital

---

## 📄 License

MIT License

---

**End of Documentation**  
*Maintained with AI agent assistance*  
*Last updated: January 30, 2026*
