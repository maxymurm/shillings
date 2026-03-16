# Shillings Ecosystem Guide

> A unified reference for all Shillings platform components: Web Admin, PWA, and Mobile App.

## Architecture Overview

```
┌─────────────────────────────────────────────────────┐
│                   Shillings API                      │
│         Laravel 12 + Sanctum + REST JSON             │
│               (Single Source of Truth)                │
└──────────┬──────────────┬───────────────┬────────────┘
           │              │               │
    ┌──────▼──────┐ ┌─────▼──────┐ ┌──────▼──────┐
    │  Web Admin  │ │    PWA     │ │   Mobile    │
    │  Filament 4 │ │  SW + Dexie│ │ Ionic + Cap │
    │  Alpine.js  │ │  (in-web)  │ │  Vue 3      │
    └─────────────┘ └────────────┘ └─────────────┘
```

## Projects

| Component | Repository | Local Path | Stack |
|-----------|-----------|------------|-------|
| **Backend + Web + PWA** | [maxymurm/shillings](https://github.com/maxymurm/shillings) | `c:\Users\maxmm\Herd\shillings` | Laravel 12, Filament 4, PHP 8.4 |
| **Mobile App** | [maxymurm/shillings-mobile](https://github.com/maxymurm/shillings-mobile) | `c:\Users\maxmm\shillings-mobile` | Ionic 8, Capacitor 6, Vue 3, TypeScript |

## Shared Code (Symlinks)

The offline sync layer is shared between the PWA and mobile app via symlinks:

| Module | Web Source | Mobile Target |
|--------|-----------|---------------|
| Dexie DB schema | `resources/js/offline/database.ts` | `src/offline/database.ts` |
| Sync engine | `resources/js/offline/sync.ts` | `src/offline/sync.ts` |
| Conflict resolver | `resources/js/offline/conflicts.ts` | `src/offline/conflicts.ts` |

**Setup symlinks** (Windows — run as Administrator):

```powershell
cd c:\Users\maxmm\shillings-mobile
mklink /D src\offline ..\Herd\shillings\resources\js\offline
```

See [PWA_SYMLINKS.md](PWA_SYMLINKS.md) and [setup-pwa-symlinks.ps1](setup-pwa-symlinks.ps1) for full setup.

## API Contract

All clients share the same REST API authenticated via Laravel Sanctum tokens.

### Authentication
- **Login**: `POST /api/auth/login` → `{ token, user }`
- **Logout**: `POST /api/auth/logout`
- **Current User**: `GET /api/user` → `{ ...user, companies: [...] }`
- **Biometric (mobile only)**: Restores the stored Sanctum token from Capacitor Preferences (`auth_token`). Does NOT make a new `/auth/login` request — it calls `authStore.init()` to rehydrate the session from the saved token.

### Multi-Organisation
Every API request includes:
```
X-Company-ID: {company_id}
Authorization: Bearer {token}
```

The backend resolves the active company from this header. Organisation switching is client-side — the selected company ID is stored:
- **Web**: PHP session (`active_company_id`)
- **Mobile**: Capacitor Preferences (`selected_company_id`)

### Core Endpoints
| Resource | Endpoints | Notes |
|----------|-----------|-------|
| Accounts | `GET/POST /accounts`, `GET/PUT /accounts/{id}` | Includes `balance_num`, `balance_denom` |
| Transactions | `GET/POST /transactions`, `GET/PUT /transactions/{id}` | With splits nested |
| Contacts | `GET/POST /contacts`, `GET/PUT/DELETE /contacts/{id}` | |
| Documents | `GET/POST /documents`, `GET/PUT /documents/{id}` | Invoices, bills, quotes |
| Reports | `GET /reports/{type}` | balance-sheet, income-statement, trial-balance, cash-flow, summary |
| Account Register | `GET /accounts/{id}/transactions?page=N` | Paginated, 15 per page |
| Sync | `POST /sync/batch` | Offline batch upload |

## Development Workflow

### Backend (Web + PWA)

```powershell
cd c:\Users\maxmm\Herd\shillings

# Run tests (259 tests, 814 assertions)
php artisan test

# Fresh database
php artisan migrate:fresh --seed

# Serve (via Laravel Herd — automatic)
# URL: http://shillings.test
```

### Mobile App

```powershell
cd c:\Users\maxmm\shillings-mobile

# Install dependencies
npm install

# Dev server (browser)
ionic serve

# Build for iOS
ionic cap build ios

# Build for Android
ionic cap build android

# Run on device
ionic cap run ios --livereload --external
ionic cap run android --livereload --external
```

### Git Workflow

Both projects use `develop` as the primary branch:

```
main (production) ← develop (active) ← feature/* (branches)
```

## Key Design Decisions

### Money Handling
All monetary values use **numerator/denominator** pairs (GnuCash precision model):
- Backend: `Money::fromFraction($num, $denom, $currency)` value object
- Mobile: `toDecimal(num, denom)` utility function
- Never use floating-point for money

### Offline-First
Both PWA and mobile are offline-first:
1. Write to IndexedDB (Dexie.js) immediately
2. Queue sync operations
3. Background sync with exponential backoff
4. Conflict resolution: LOCAL_WINS, SERVER_WINS, or MANUAL

### Organisation Terminology
UI consistently uses "Organisation" (not "Company"). The database model remains `Company` for backward compatibility, but all user-facing labels say "Organisation".

### Filament SPA Mode
The web admin runs in SPA mode (`->spa()` in AdminPanelProvider) for faster navigation. All render hooks use inline styles (not Tailwind) since the CSS is pre-compiled.

## Environment Configuration

### Backend `.env`
```env
APP_NAME=Shillings
APP_URL=http://shillings.test
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite
SANCTUM_STATEFUL_DOMAINS=shillings.test,localhost
```

### Mobile `.env`
```env
VITE_API_URL=http://shillings.test/api
```

## Feature Parity

See [docs/FEATURE_PARITY_MATRIX.md](docs/FEATURE_PARITY_MATRIX.md) for the full comparison of features across Web, PWA, and Mobile platforms.

## Project Boards

- **Backend**: [GitHub Project Board](https://github.com/users/maxymurm/projects/4)
- **Mobile**: [GitHub Project Board](https://github.com/users/maxymurm/projects/9)

## Milestones

All 16 backend phases are complete (259/259 tests passing). The mobile app covers phases 1-15. See the [Feature Parity Matrix](docs/FEATURE_PARITY_MATRIX.md) for detailed coverage.
