# Feature Parity Matrix — Shillings Ecosystem

> Last updated: 2026-03-16

## Platform Overview

| Platform | Stack | Status | Location |
|----------|-------|--------|----------|
| **Web Admin** | Laravel 12 + Filament 4 + Alpine.js | ✅ Phases 1-16 complete | `c:\Users\maxmm\Herd\shillings` |
| **PWA** | Service Worker + Dexie.js (built into web) | ✅ Phase 16 complete | Integrated in web `public/` |
| **Mobile (iOS/Android)** | Ionic 8 + Capacitor 6 + Vue 3 + Pinia | ✅ Phases 1-15 complete | `c:\Users\maxmm\shillings-mobile` |

## Feature Parity

### Legend
- ✅ Fully implemented
- ⚡ Partial / simplified version
- ❌ Not available
- 🔲 Planned

### Core Accounting

| Feature | Web | PWA | Mobile | Notes |
|---------|-----|-----|--------|-------|
| Double-entry transactions | ✅ | ✅ (offline queue) | ✅ | Same API, same validation |
| Split editor | ✅ | ✅ | ✅ | Mobile uses `SplitEditor.vue` component |
| Chart of accounts (tree) | ✅ | ✅ | ✅ | Mobile: grouped by classification |
| Account types management | ✅ | ✅ | ⚡ | Mobile: read-only, admin via web |
| Multi-currency | ✅ | ✅ | ✅ | Currency stored per company |
| Exchange rates | ✅ | ✅ | ⚡ | Mobile: uses rates, no CRUD |
| Reconciliation | ✅ | ✅ | ❌ | Desktop-oriented workflow |
| Transaction templates | ✅ | ✅ | ❌ | Best suited to desktop |

### Organisation Management

| Feature | Web | PWA | Mobile | Notes |
|---------|-----|-----|--------|-------|
| Create organisation | ✅ (onboarding) | ✅ | ❌ | Mobile: orgs created via web |
| Organisation switcher | ✅ (header pill) | ✅ | ✅ (Settings) | Mobile: radio list in Settings page |
| Organisation CRUD | ✅ (Filament resource) | ✅ | ❌ | Admin task — web only |
| User/role management | ✅ | ✅ | ❌ | Admin task — web only |
| GnuCash CSV import | ✅ | ✅ | ❌ | Desktop-oriented feature |

### Contacts & Documents

| Feature | Web | PWA | Mobile | Notes |
|---------|-----|-----|--------|-------|
| Contacts CRUD | ✅ | ✅ | ✅ | Full parity |
| Contact types (customer/vendor/employee) | ✅ | ✅ | ✅ | |
| Documents (invoices/bills/quotes) | ✅ | ✅ | ✅ | Mobile: simplified create |
| Document PDF generation | ✅ | ✅ | ❌ | Server-side only |
| Receipt capture | ✅ (file upload) | ✅ | ✅ (native camera) | Mobile superior — native camera |

### Financial Reports

| Feature | Web | PWA | Mobile | Notes |
|---------|-----|-----|--------|-------|
| Trial Balance | ✅ | ✅ | ✅ | Mobile: table view |
| Balance Sheet | ✅ | ✅ | ✅ | Mobile: chart + table |
| Income Statement | ✅ | ✅ | ✅ | Mobile: chart + table |
| Cash Flow | ✅ | ✅ | ✅ | Mobile: chart |
| Account Register (ledger) | ✅ (GnuCash-style) | ✅ | ✅ | Web: quick-entry row + suggestions |
| Custom Reports | ✅ | ✅ | ❌ | Complex — web only |
| Aging Reports | ✅ | ✅ | ❌ | Desktop workflow |

### Planning & Budgets

| Feature | Web | PWA | Mobile | Notes |
|---------|-----|-----|--------|-------|
| Budgets CRUD | ✅ | ✅ | ✅ | Full parity |
| Budget variance analysis | ✅ | ✅ | ✅ | |
| Scheduled transactions | ✅ | ✅ | ✅ | Mobile: view + create |

### Banking

| Feature | Web | PWA | Mobile | Notes |
|---------|-----|-----|--------|-------|
| OFX/CSV import | ✅ | ✅ | ❌ | File-oriented — web only |
| Bank connections | ✅ | ✅ | ❌ | Web admin feature |
| Transaction matching | ✅ | ✅ | ❌ | Desktop workflow |
| Import history | ✅ | ✅ | ❌ | Web admin feature |

### Tax

| Feature | Web | PWA | Mobile | Notes |
|---------|-----|-----|--------|-------|
| Tax management | ✅ | ✅ | ❌ | Admin setup — web only |
| Tax calculation | ✅ | ✅ | ⚡ | Mobile: applied during tx create |
| Tax reports | ✅ | ✅ | ❌ | Desktop workflow |

### Offline & Sync

| Feature | Web | PWA | Mobile | Notes |
|---------|-----|-----|--------|-------|
| IndexedDB storage (Dexie.js) | ❌ | ✅ | ✅ | Shared schema |
| Background sync | ❌ | ✅ (Service Worker) | ✅ (Capacitor) | Different native layers |
| Conflict resolution | ❌ | ✅ | ✅ | LOCAL_WINS / SERVER_WINS / MANUAL |
| Offline indicator | ❌ | ✅ | ✅ | SyncBar component |
| Batched sync | ❌ | ✅ | ✅ | Same batch POST endpoint |

### Native / Platform-Specific

| Feature | Web | PWA | Mobile | Notes |
|---------|-----|-----|--------|-------|
| Push notifications | ❌ | ✅ (Web Push) | ✅ (FCM/APNs) | Both queue offline |
| Biometric auth | ❌ | ❌ | ✅ | Capacitor plugin |
| Native camera | ❌ | ❌ | ✅ | Receipt capture |
| Haptic feedback | ❌ | ❌ | ✅ | Capacitor Haptics |
| App store distribution | ❌ | ❌ | ✅ | iOS + Android |
| Dark mode | ✅ (Filament) | ✅ | ✅ | System-aware |
| SPA mode | ✅ | ✅ | ✅ | All use client-side routing |

## Design Philosophy

The **web admin** is the full-featured management interface for accountants and admins. The **mobile app** is optimised for on-the-go tasks: recording transactions, viewing balances, checking reports, and capturing receipts. Features like reconciliation, bank import, tax setup, and user management are intentionally web-only because they benefit from a desktop/tablet form factor.

## Shared Code

| Module | Path (web) | Path (mobile) | Mechanism |
|--------|-----------|---------------|-----------|
| Offline DB | `resources/js/offline/database.ts` | `src/offline/database.ts` | Symlink |
| Sync engine | `resources/js/offline/sync.ts` | `src/offline/sync.ts` | Symlink |
| Conflicts | `resources/js/offline/conflicts.ts` | `src/offline/conflicts.ts` | Symlink |
| Money utils | `app/ValueObjects/Money.php` | `src/utils/money.ts` | Reimplemented (same logic) |
| API auth | Laravel Sanctum | Axios `X-Company-ID` header | Same backend |
