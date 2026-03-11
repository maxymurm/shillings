# PWA & Mobile App - Symlinks & Documentation

## Overview

The **Shillings Progressive Web Application (PWA)** is built into the main Shillings Laravel project. There is no separate mobile app - the PWA IS the mobile app. It runs in browsers and can be installed as a native-like app on iOS and Android devices.

### What is a PWA?

A Progressive Web App (PWA) is a web application that:
- Works offline with cached content
- Can be installed on devices (Android/iOS)
- Supports push notifications
- Uses a service worker for background sync
- Provides an app-like experience with splash screens, app shortcuts, etc.

---

## File Locations

### Main Shillings Project
**Root:** `c:\Users\maxmm\Herd\shillings\`

#### PWA Static Assets (Public)
```
public/
├── manifest.json          # PWA manifest - defines app name, icons, shortcuts, theme
├── sw.js                  # Service worker - handles offline, caching, push notifications
└── offline.html           # Fallback page when offline
```

#### PWA JavaScript Modules (Source)
```
resources/js/
├── pwa.js                 # Main PWA initialization - SW registration, events
└── offline/
    ├── database.js        # IndexedDB schema via Dexie.js (accounts, transactions, etc.)
    ├── store.js           # Cache/retrieve functions, sync status tracking
    ├── transactions.js    # Offline transaction creation with local validation
    ├── sync.js            # Background sync orchestration (full sync cycle)
    ├── conflicts.js       # Conflict resolution (LOCAL_WINS, SERVER_WINS, MANUAL)
    ├── notifications.js   # Web Push API, push subscription management
    └── receipts.js        # Image capture, compression, base64 storage
```

#### Filament Integration
```
resources/views/filament/partials/
├── pwa-head.blade.php              # PWA meta tags, mobile CSS, safe-area padding
└── pwa-offline-indicator.blade.php  # Offline status bar, sync badge, install prompt

app/Providers/Filament/AdminPanelProvider.php  # SPA mode, renderHooks
```

#### Backend API
```
app/Http/Controllers/Api/
├── SyncController.php               # /api/sync/* endpoints
│   ├── GET  /status                 # Server time, user_id, sync_supported
│   ├── POST /batch                  # Batch operations (CREATE/UPDATE/DELETE)
│   └── GET  /changes                # Incremental sync since timestamp
│
└── PushSubscriptionController.php   # /api/push-subscriptions/* endpoints
    ├── POST /                       # Store/update push subscription
    └── DELETE /                     # Delete push subscription

app/Models/PushSubscription.php      # Push subscription model
database/migrations/*create_push_subscriptions_table.php
```

---

## Cross-Project Symlinks

To enable the other projects to reference Shillings PWA assets, create symlinks:

### In Akaunting Project
**Location:** `c:\Users\maxmm\OneDrive\المستندات\Clients\akaunting\`

```powershell
# Run from akaunting folder (requires admin or developer mode)
$shillingsPath = "c:\Users\maxmm\Herd\shillings"
$akauntingPath = (Get-Location).Path

New-Item -ItemType SymbolicLink -Path "$akauntingPath\pwa-shillings-assets" `
  -Target "$shillingsPath\public" -Force

New-Item -ItemType SymbolicLink -Path "$akauntingPath\pwa-shillings-modules" `
  -Target "$shillingsPath\resources\js\offline" -Force
```

### In GnuCash Project
**Location:** `c:\Users\maxmm\OneDrive\المستندات\Clients\gnucash\`

```powershell
# Run from gnucash folder (requires admin or developer mode)
$shillingsPath = "c:\Users\maxmm\Herd\shillings"
$gnucashPath = (Get-Location).Path

New-Item -ItemType SymbolicLink -Path "$gnucashPath\pwa-shillings-assets" `
  -Target "$shillingsPath\public" -Force

New-Item -ItemType SymbolicLink -Path "$gnucashPath\pwa-shillings-modules" `
  -Target "$shillingsPath\resources\js\offline" -Force
```

---

## PWA Access & Installation

### Local Development
```
URL: http://localhost:8000/admin
```
- Service worker registers at `/sw.js`
- Manifest loads from `/manifest.json`
- Offline page available at `/offline.html`

### Installation
1. Open Shillings app in browser (`http://localhost:8000/admin`)
2. Look for install prompt (browser-dependent)
3. Click install to add to home screen
4. App launches in standalone mode

### Features
- ✅ Works offline (cached pages/API responses)
- ✅ Background sync (auto-sync when reconnected)
- ✅ Push notifications (with Web Push API)
- ✅ Mobile-optimized UI (Filament SPA mode)
- ✅ Receipt capture (camera/file upload)
- ✅ Conflict resolution (sync merging strategies)

---

## Accessing PWA Assets from Other Projects

### From Akaunting
```javascript
// Import offline database
import { db } from '../pwa-shillings-modules/database.js';

// Access cached data
import { getCachedAccounts } from '../pwa-shillings-modules/store.js';

// Use sync functions
import { fullSync } from '../pwa-shillings-modules/sync.js';
```

### From GnuCash
```javascript
// Same pattern as Akaunting
import { db } from '../pwa-shillings-modules/database.js';
import { processReceiptImage } from '../pwa-shillings-modules/receipts.js';
```

---

## Symlink Management

### Create Symlinks (Admin/Developer Mode Required)
```powershell
# Shillings PWA symlinks
New-Item -ItemType SymbolicLink -Path "c:\Users\maxmm\Herd\shillings\pwa-symlinks\manifest.json" `
  -Target "c:\Users\maxmm\Herd\shillings\public\manifest.json"

New-Item -ItemType SymbolicLink -Path "c:\Users\maxmm\Herd\shillings\pwa-symlinks\sw.js" `
  -Target "c:\Users\maxmm\Herd\shillings\public\sw.js"

New-Item -ItemType SymbolicLink -Path "c:\Users\maxmm\Herd\shillings\pwa-symlinks\offline-modules" `
  -Target "c:\Users\maxmm\Herd\shillings\resources\js\offline"
```

### List Symlinks
```powershell
Get-ChildItem $path -Force | Where-Object { $_.LinkType -eq "SymbolicLink" } | Format-Table Name, LinkTarget
```

### Remove Symlinks
```powershell
Remove-Item "c:\Users\maxmm\Herd\shillings\pwa-symlinks\manifest.json"
```

---

## Deployment Notes

### To Production (Laravel Forge)
1. All PWA assets are served from Laravel's `public/` directory
2. Service Worker (`/sw.js`) must be served with proper CORS headers
3. HTTPS required for Web Push (push notifications)
4. Set `VAPID_PUBLIC_KEY` and `VAPID_PRIVATE_KEY` in `.env`

### Environment Variables
```
# .env
VAPID_PUBLIC_KEY=your_public_key_here
VAPID_PRIVATE_KEY=your_private_key_here
```

### CI/CD Pipeline
- PWA assets included in deployments
- Service worker cache invalidation via versioning (`STATIC_CACHE_v1`, etc.)
- Push subscriptions persisted in MySQL

---

## Architecture Diagram

```
User Browser
    ↓
    ├─→ index.html (loads resources/js/app.js)
    │      ↓
    │      └─→ resources/js/pwa.js (registers SW)
    │             ↓
    ↓             └─→ /sw.js (Service Worker)
                      ↓
                      ├─→ Cache API (static assets, images)
                      ├─→ IndexedDB (transactions, accounts)
                      └─→ Background Sync API (offline sync queue)
    │
    └─→ /api/sync/* (REST endpoints)
           ├─→ GET /status (check sync support)
           ├─→ POST /batch (upload offline changes)
           └─→ GET /changes (download new data)
```

---

## Testing

### Run PWA Tests
```powershell
cd c:\Users\maxmm\Herd\shillings
php artisan test --filter="MobileOfflineSyncApiTest"
```

**Expected:** 17/17 tests passing
- PWA manifest accessible ✓
- Service worker accessible ✓
- Offline page accessible ✓
- Sync API endpoints work ✓
- Push notifications work ✓
- Conflict resolution works ✓

---

## References

- **PWA Documentation:** https://web.dev/progressive-web-apps/
- **Service Workers:** https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API
- **Web Push API:** https://developer.mozilla.org/en-US/docs/Web/API/Push_API
- **Dexie.js:** https://dexie.org/
- **Filament PWA Support:** https://filamentphp.com/docs/3.x/admin/installation#pwa-support

---

**Last Updated:** 2026-03-11  
**Project:** Shillings - Offline-First Accounting  
**Phase:** 16 (Mobile & Offline Sync) - COMPLETE
