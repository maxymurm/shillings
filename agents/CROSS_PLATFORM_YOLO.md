---
version: "1.0"
date: "2026-03-16"
mode: YOLO
scope: cross-platform
---

# Autonomous Cross-Platform Execution Prompt — Shillings
## All Backlog Work (Phases A–E) — Both Projects

> **PARALLEL PARITY MANDATORY:** Every phase touches BOTH projects unless explicitly marked
> `[WEB ONLY]` or `[MOBILE ONLY]`. Do not complete a phase for one project without
> completing or explicitly skipping it for the other.

---

## HOW TO START — COPY THIS INTO OPUS / ANY YOLO AGENT

```
Read this file completely before doing anything else.
Also read:
  - c:\Users\maxmm\Herd\shillings\.github\copilot-instructions.md
  - c:\Users\maxmm\shillings-mobile\agents\memory.instructions.md

Then execute ALL phases top to bottom in YOLO mode:
  - No user confirmation needed for any decision
  - Commit + push after EVERY phase (paired commits: shillings/develop + shillings-mobile/main)
  - **CLOSE the GitHub issue(s) listed in each task as soon as you push that phase**
    - Use: gh issue close <NUMBER> --repo maxymurm/shillings (or shillings-mobile)
    - Add a comment with the commit SHA when closing
  - Run backend tests (php artisan test) after each backend change — all 259 must remain green
  - Update this file's PROGRESS section as you complete each phase
  - Tell me what you finished and what blocked you when done

PROJECTS:
  Backend + Web:  c:\Users\maxmm\Herd\shillings        branch: develop
  Mobile:         c:\Users\maxmm\shillings-mobile       branch: main

Go.
```

---

## AGENT PERMISSIONS

- ✅ Create, modify, delete any files in both projects
- ✅ Commit + push to both repos with conventional commit messages
- ✅ Create and close GitHub issues on both project boards
- ✅ Run `php artisan`, `npm`, `ionic`, `npx` commands
- ✅ Make all architectural decisions autonomously
- ✅ Fix bugs discovered along the way
- ❌ Do NOT touch `parent_shillings/` PHP source (mobile symlink is read-only)
- ❌ Do NOT submit to app stores (out of scope)
- ❌ Do NOT break existing 259 passing backend tests

---

## ECOSYSTEM CONTEXT

### Backend
- **Laravel 12** + **Filament 4** + **PHP 8.4** | 259/259 tests passing
- **Money pattern:** `Money::fromFraction($num, $denom, $currency)` — NEVER `new Money()`
- **DB columns:** `issued_at` (NOT `issue_date`), `enabled` (NOT `is_enabled`)
- **Auth:** Laravel Sanctum

### Mobile
- **Ionic 8** + **Capacitor 6** + **Vue 3** + **TypeScript** + **Pinia**
- **Money pattern:** `toDecimal(num, denom)` / `formatCurrency()` from `@/utils/money`
- **Offline:** Dexie.js IndexedDB via `src/offline/database.ts` (symlinked)
- **Auth:** Token in Capacitor Preferences (`auth_token` key)

### Already Confirmed Working (do NOT re-implement)
- Custom Report Builder — `CustomReportResource.php` fully implemented in Filament [WEB ONLY]
- Account Register search/pagination on backend — `GET /accounts/{id}/transactions?search=&start_date=&end_date=`
- Transaction search on backend — `GET /transactions?search=&start_date=&end_date=`
- ConflictModal.vue exists at `src/components/ConflictModal.vue` — just needs mounting [MOBILE ONLY]
- `camera.ts` exists at `src/services/camera.ts` — just needs wiring to views [MOBILE ONLY]
- Biometric service at `src/services/biometric.ts` — `loginWithBiometric()` stub needs implementation [MOBILE ONLY]

---

## GitHub Issue Reference

| Task | Backend Issue | Mobile Issue |
|------|--------------|-------------|
| A1 — Tax Summary bug fix | [#132](https://github.com/maxymurm/shillings/issues/132) | N/A |
| A2 — Conflict UI + sync tests | [#134](https://github.com/maxymurm/shillings/issues/134) | [#106](https://github.com/maxymurm/shillings-mobile/issues/106) |
| A3 — Biometric login | N/A | [#107](https://github.com/maxymurm/shillings-mobile/issues/107) |
| A4 — Account Register filters | N/A (already exists) | [#108](https://github.com/maxymurm/shillings-mobile/issues/108) |
| A5 — Receipt capture | [#133](https://github.com/maxymurm/shillings/issues/133) | [#109](https://github.com/maxymurm/shillings-mobile/issues/109) |
| B1 — Bulk API endpoint | [#135](https://github.com/maxymurm/shillings/issues/135) | N/A |
| B2 — Filament bulk actions | [#136](https://github.com/maxymurm/shillings/issues/136) | N/A |
| B3 — Mobile bulk select | N/A | [#110](https://github.com/maxymurm/shillings-mobile/issues/110) |
| C1 — Report text search (backend) | [#137](https://github.com/maxymurm/shillings/issues/137) | N/A |
| C2 — Report filter bar (mobile) | N/A | [#111](https://github.com/maxymurm/shillings-mobile/issues/111) |
| D1 — Fill backend test stubs | [#138](https://github.com/maxymurm/shillings/issues/138) | N/A |
| D2 — Mobile Vitest coverage | N/A | [#112](https://github.com/maxymurm/shillings-mobile/issues/112) |
| D3 — Cypress E2E scaffold | N/A | [#113](https://github.com/maxymurm/shillings-mobile/issues/113) |
| E1 — Backend performance | [#139](https://github.com/maxymurm/shillings/issues/139) | N/A |
| E2 — Mobile performance | N/A | [#114](https://github.com/maxymurm/shillings-mobile/issues/114) |
| F1 — Tax Summary Filament page | [#140](https://github.com/maxymurm/shillings/issues/140) | N/A |
| F2 — Tax Summary mobile view | N/A | [#115](https://github.com/maxymurm/shillings-mobile/issues/115) |

---

## PHASE A — Quick Wins & Bug Fixes
**Paired commits required. Both tasks must be done before committing.**

### A1 — Fix Tax Summary Column Bug [WEB ONLY]
**Close on completion:** `gh issue close 132 --repo maxymurm/shillings`

**File:** `app/Services/TaxService.php`
**Bug:** `getTaxSummary()` queries `whereBetween('issue_date', ...)` but the Document
model column is `issued_at`. This silently returns no data.

**Fix:**
```php
// WRONG (current):
->whereBetween('issue_date', [$startDate, $endDate])

// CORRECT:
->whereBetween('issued_at', [$startDate, $endDate])
```

Search the entire `TaxService.php` for any reference to `issue_date` and replace
with `issued_at`. Run `php artisan test` — all 259 must pass.

**Mobile counterpart:** No equivalent bug. Write a comment in mobile `src/services/api.ts`
noting the correct tax summary endpoint signature: `GET /api/taxes/summary?start_date=&end_date=`.

---

### A2 — Wire Conflict Resolution UI [MOBILE ONLY]
**Close on completion:** `gh issue close 106 --repo maxymurm/shillings-mobile` + `gh issue close 134 --repo maxymurm/shillings` (backend tests)

**Problem:** `ConflictModal.vue` exists but is mounted nowhere, so users never see conflicts.

**Fix steps:**
1. In `src/views/TabsLayout.vue` (or `App.vue` if it exists), add `<ConflictModal />` at the
   root level so it's available on every tab.
2. In `src/stores/sync.ts` (or wherever `syncStore` is defined), confirm that `conflicts[]` is
   reactive and `hasConflicts` computed getter exists. Add if missing.
3. In `ConflictModal.vue`, verify it watches `syncStore.hasConflicts` and auto-opens via
   `v-if` or `IonModal :is-open`. Wire it up if not.
4. In `src/offline/transactions.js` (or `.ts`), find `markTransactionConflict()` and ensure
   it calls `syncStore.addConflict(conflict)` to populate the store.
5. Fix `MANUAL` strategy in `src/offline/conflicts.ts`: if strategy is `MANUAL`, push to
   `syncStore.conflicts` array instead of silently falling back to server data.

**Backend counterpart:** Add integration test `tests/Feature/Api/MobileOfflineSyncApiTest.php`
(currently empty) — add at minimum:
- `test_sync_batch_accepts_array_of_offline_transactions()`
- `test_sync_batch_returns_conflicts_when_server_version_newer()`
- `test_sync_changes_returns_updates_since_timestamp()`

---

### A3 — Implement Biometric Login [MOBILE ONLY]
**Close on completion:** `gh issue close 107 --repo maxymurm/shillings-mobile`

**File:** `src/views/auth/LoginPage.vue`
**Problem:** `loginWithBiometric()` function exists but is a stub with only a comment.

**Implementation:**
```typescript
async function loginWithBiometric() {
  try {
    const available = await isBiometricAvailable();
    if (!available) return;

    const result = await authenticateWithBiometric();
    if (!result.verified) return;

    // Restore the stored token (set during normal login)
    const { value: savedToken } = await Preferences.get({ key: 'auth_token' });
    if (!savedToken) {
      // No stored token — fall back to password login
      biometricError.value = 'No saved session. Please log in with your password first.';
      return;
    }
    // Re-init the auth store using the stored token
    await authStore.init();
    if (authStore.isAuthenticated) {
      router.replace('/tabs/dashboard');
    }
  } catch (e: any) {
    biometricError.value = e.message ?? 'Biometric authentication failed';
  }
}
```

Also add a `biometricError` ref display in the template (small red `<ion-text color="danger">` below the biometric button).

Add a biometric toggle to `SettingsPage.vue`:
```vue
<ion-item>
  <ion-label>Unlock with Biometrics</ion-label>
  <ion-toggle slot="end" :checked="biometricEnabled" @ionChange="toggleBiometric" />
</ion-item>
```
Implement `toggleBiometric()` calling `setBiometricEnabled(event.detail.checked)`.

**Backend counterpart:** No code change needed. Add a note to `docs/ECOSYSTEM.md`
under "Authentication" clarifying that biometric restore uses the stored Sanctum token
and does not make a new `/auth/login` request.

---

### A4 — Add Search + Date Filters to Mobile Account Register [MOBILE ONLY]
**Close on completion:** `gh issue close 108 --repo maxymurm/shillings-mobile`

**File:** `src/views/reports/AccountRegisterPage.vue`
**Problem:** The backend `GET /accounts/{id}/transactions` supports `search`, `start_date`,
`end_date` params, but the mobile UI has no way to use them.

**Add to the register detail view (second panel) a filter toolbar:**
```vue
<!-- In the detail view header, below IonToolbar with back button: -->
<ion-toolbar>
  <ion-searchbar
    v-model="searchQuery"
    placeholder="Search transactions..."
    debounce="400"
    @ionInput="load(true)"
  />
</ion-toolbar>
<ion-toolbar>
  <ion-item lines="none">
    <ion-label>From</ion-label>
    <ion-datetime-button datetime="start-dt" slot="end" />
    <ion-modal :keep-contents-mounted="true">
      <ion-datetime id="start-dt" presentation="date" v-model="startDate" @ionChange="load(true)" />
    </ion-modal>
  </ion-item>
  <ion-item lines="none">
    <ion-label>To</ion-label>
    <ion-datetime-button datetime="end-dt" slot="end" />
    <ion-modal :keep-contents-mounted="true">
      <ion-datetime id="end-dt" presentation="date" v-model="endDate" @ionChange="load(true)" />
    </ion-modal>
  </ion-item>
</ion-toolbar>
```

Update `fetchAccountRegister()` call to pass params:
```typescript
const data = await fetchAccountRegister(selectedAccountId.value, page.value, {
  search: searchQuery.value || undefined,
  start_date: startDate.value || undefined,
  end_date: endDate.value || undefined,
});
```

Update `src/services/reports.ts` — `fetchAccountRegister` signature:
```typescript
export async function fetchAccountRegister(
  accountId: number,
  page = 1,
  filters: { search?: string; start_date?: string; end_date?: string } = {}
): Promise<AccountRegisterEntry[]> {
  const response = await api.get(`/accounts/${accountId}/transactions`, {
    params: { page, ...filters }
  });
  return response.data.data ?? response.data;
}
```

**Backend counterpart:** The search filter already exists on the backend. Write backend
test `tests/Feature/Api/AdvancedReportApiTest.php` (currently empty stub) — add:
- `test_account_register_supports_search_filter()`
- `test_account_register_supports_date_range_filter()`
- `test_account_register_pagination()`
- `test_general_ledger_filter_by_account_types()`
- `test_general_ledger_filter_by_account_ids()`

---

### A5 — Wire Receipt Capture to Transaction Forms
**Close on completion:** `gh issue close 109 --repo maxymurm/shillings-mobile` + `gh issue close 133 --repo maxymurm/shillings`

**File:** `src/services/camera.ts` (exists), target: transaction create/edit views
**Problem:** `captureReceipt()` and `pickFromGallery()` exist but no view uses them.

**Steps:**
1. Find `src/views/transactions/TransactionCreatePage.vue` (or equivalent create/edit page).
   Add a receipt attachment section at the bottom of the form:
   ```vue
   <ion-item-group>
     <ion-item-divider><ion-label>Receipt (optional)</ion-label></ion-item-divider>
     <ion-item v-if="receiptPreview">
       <ion-thumbnail slot="start">
         <img :src="receiptPreview" alt="Receipt" />
       </ion-thumbnail>
       <ion-button fill="clear" color="danger" slot="end" @click="receiptPreview = null; receiptData = null">
         Remove
       </ion-button>
     </ion-item>
     <ion-item v-else>
       <ion-button fill="outline" size="small" @click="takePhoto">
         <ion-icon :icon="cameraOutline" slot="start" /> Camera
       </ion-button>
       <ion-button fill="outline" size="small" @click="choosePhoto" style="margin-left: 8px">
         <ion-icon :icon="imageOutline" slot="start" /> Gallery
       </ion-button>
     </ion-item>
   </ion-item-group>
   ```

2. In the script:
   ```typescript
   import { captureReceipt, pickFromGallery } from '@/services/camera';
   import { cameraOutline, imageOutline } from 'ionicons/icons';

   const receiptPreview = ref<string | null>(null);
   const receiptData = ref<string | null>(null); // base64 for upload

   async function takePhoto() {
     const base64 = await captureReceipt();
     if (base64) { receiptData.value = base64; receiptPreview.value = `data:image/jpeg;base64,${base64}`; }
   }

   async function choosePhoto() {
     const base64 = await pickFromGallery();
     if (base64) { receiptData.value = base64; receiptPreview.value = `data:image/jpeg;base64,${base64}`; }
   }
   ```

3. When submitting the transaction, if `receiptData.value` is set, include it in the
   POST body as `receipt_image` (base64). The backend `DocumentService` already handles
   file attachments — confirm the transactions API accepts `receipt_image` or add handling.

**Backend counterpart:** Check `TransactionController::store()` — if it doesn't accept
`receipt_image`, add it: store base64 as a file in `storage/app/receipts/{company_id}/`
using `Storage::putFileAs()`, save the path to `transactions.receipt_path` column.
If `receipt_path` column doesn't exist, create a migration:
```php
Schema::table('transactions', function (Blueprint $table) {
    $table->string('receipt_path')->nullable()->after('voided_at');
});
```
Add `receipt_path` to `TransactionController` store/update fillable handling. Add test
`test_transaction_can_store_receipt_image()` to `tests/Feature/Api/TransactionTest.php`.

---

## PHASE B — Bulk Transaction Operations

### B1 — Backend Bulk Endpoint [WEB]
**Close on completion:** `gh issue close 135 --repo maxymurm/shillings`

**No bulk operations exist at all.** Add `POST /api/transactions/bulk` endpoint.

**New route in `routes/api.php`** (inside transactions middleware group):
```php
Route::post('transactions/bulk', [TransactionController::class, 'bulk'])->middleware('ability:transactions:write');
```

**New `TransactionController::bulk()` method:**
```php
public function bulk(Request $request): JsonResponse
{
    $validated = $request->validate([
        'action'          => ['required', Rule::in(['post', 'void', 'delete'])],
        'transaction_ids' => ['required', 'array', 'min:1', 'max:100'],
        'transaction_ids.*' => ['integer', 'exists:transactions,id'],
    ]);

    $company = $request->user()->activeCompany();
    $transactions = Transaction::whereIn('id', $validated['transaction_ids'])
        ->where('company_id', $company->id) // scoped to current company
        ->get();

    $results = ['success' => [], 'failed' => []];

    foreach ($transactions as $txn) {
        try {
            match ($validated['action']) {
                'post'   => $this->transactionService->postTransaction($txn),
                'void'   => $this->transactionService->voidTransaction($txn),
                'delete' => $txn->delete(),
            };
            $results['success'][] = $txn->id;
        } catch (\Throwable $e) {
            $results['failed'][] = ['id' => $txn->id, 'error' => $e->getMessage()];
        }
    }

    return response()->json($results);
}
```

Add tests to `tests/Feature/Api/TransactionTest.php`:
- `test_bulk_post_transactions()`
- `test_bulk_void_transactions()`
- `test_bulk_delete_transactions()`
- `test_bulk_action_scoped_to_company()`
- `test_bulk_action_max_100_transactions()`

### B2 — Filament Bulk Actions [WEB]
**Close on completion:** `gh issue close 136 --repo maxymurm/shillings`

In `app/Filament/Resources/TransactionResource/Pages/ListTransactions.php`
(or `TransactionResource.php` table definition), add bulk actions:
```php
->bulkActions([
    BulkActionGroup::make([
        Tables\Actions\BulkAction::make('post')
            ->label('Post Selected')
            ->icon('heroicon-o-check-circle')
            ->requiresConfirmation()
            ->action(fn (Collection $records) => $records->each(
                fn ($txn) => app(TransactionService::class)->postTransaction($txn)
            )),
        Tables\Actions\BulkAction::make('void')
            ->label('Void Selected')
            ->icon('heroicon-o-x-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->action(fn (Collection $records) => $records->each(
                fn ($txn) => app(TransactionService::class)->voidTransaction($txn)
            )),
        Tables\Actions\DeleteBulkAction::make(),
    ]),
])
```

### B3 — Mobile Bulk Select [MOBILE]
**Close on completion:** `gh issue close 110 --repo maxymurm/shillings-mobile`

In `src/views/transactions/TransactionListPage.vue` (or equivalent):
1. Add a long-press handler (`@long-press` or `@touchstart` with timer) that enters
   "select mode" — shows checkboxes on each item, a "Select All" button in header,
   and an action bar at the bottom with "Post", "Void", "Delete" buttons.
2. Maintain `selectedIds = ref<number[]>([])`.
3. On action button tap, call `POST /api/transactions/bulk` with action + ids.
4. Show `IonToast` with result summary (e.g., "12 transactions posted, 0 failed").
5. Exit select mode after action completes or on back button.

---

## PHASE C — Advanced Report Filters (Backend + Mobile)

### C1 — Add Text Search to Summary Reports [WEB]
**Close on completion:** `gh issue close 137 --repo maxymurm/shillings`

Currently `trial-balance`, `balance-sheet`, `income-statement` have no account-level
text search. Add optional `search` param to each:

In `ReportController`, for each report that returns account rows:
```php
$search = $request->input('search');
// When building account query, add:
->when($search, fn ($q) => $q->where(function ($q) use ($search) {
    $q->where('accounts.name', 'like', "%{$search}%")
      ->orWhere('accounts.code', 'like', "%{$search}%");
}))
```

Add `date_preset` parameter support (`this_month`, `last_month`, `this_quarter`,
`last_quarter`, `ytd`, `last_year`) to income-statement, cash-flow, and general-ledger
— resolve preset to `start_date`/`end_date` in a private `resolvePreset()` helper method.

Add tests to `AdvancedReportApiTest.php`:
- `test_trial_balance_search_by_account_name()`
- `test_income_statement_date_preset_this_month()`
- `test_income_statement_date_preset_ytd()`

### C2 — Report Filters in Mobile Reports [MOBILE]
**Close on completion:** `gh issue close 111 --repo maxymurm/shillings-mobile`

For each report page (`TrialBalancePage.vue`, `IncomeStatementPage.vue`, etc.):
1. Add a collapsible filter bar toggled by a filter icon button in the toolbar.
2. Include: text search `IonSearchbar`, date preset `IonSelect` (with options: This Month,
   Last Month, This Quarter, YTD, Custom), and if Custom: two `IonDatetime` pickers.
3. On filter change, refetch the report passing `search` and date params to the API.
4. Show a "Filters active" chip in the toolbar when any filter is set, tappable to clear all.

Update `src/services/reports.ts` — each fetch function to accept optional filters:
```typescript
export async function fetchTrialBalance(
  filters: { search?: string; as_of_date?: string } = {}
): Promise<TrialBalanceRow[]> {
  const response = await api.get('/reports/trial-balance', { params: filters });
  return response.data.data ?? response.data;
}
// same pattern for balance-sheet, income-statement, cash-flow
```

---

## PHASE D — Test Coverage (Both Projects)

### D1 — Fill Empty Backend Test Stubs [WEB]
**Close on completion:** `gh issue close 138 --repo maxymurm/shillings`

These test files exist but are **empty** (only `<?php`). Fill them all:

**`tests/Feature/Api/AdvancedReportApiTest.php`** — see tests specified in Phase A4 and C1.

**`tests/Feature/Api/MobileOfflineSyncApiTest.php`** — see tests specified in Phase A2.

**`tests/Feature/Api/TaxApiTest.php`:**
- `test_tax_summary_returns_sales_taxes()`
- `test_tax_summary_returns_purchase_taxes()`
- `test_tax_summary_uses_issued_at_column()` (regression for Phase A1 bugfix)

**`tests/Feature/Api/DocumentApiTest.php`:**
- `test_can_create_invoice()`
- `test_can_create_bill()`
- `test_invoice_calculates_totals_with_taxes()`
- `test_invoice_status_transitions()`
- `test_document_issued_at_stores_correctly()`

**`tests/Feature/Api/ImportBatchApiTest.php`:**
- `test_can_upload_csv_file()`
- `test_can_list_import_batches()`
- `test_import_batch_processes_transactions()`

**`tests/Unit/ValueObjects/MoneyTest.php`:**
- `test_from_fraction_creates_money_object()`
- `test_from_decimal_creates_money_object()`
- `test_from_cents_creates_money_object()`
- `test_addition()`
- `test_subtraction()`
- `test_debit_increases_asset_account_balance()`
- `test_credit_decreases_asset_account_balance()`

After filling stubs, run `php artisan test` — all must pass.

### D2 — Mobile Vitest Coverage [MOBILE]
**Close on completion:** `gh issue close 112 --repo maxymurm/shillings-mobile`

Expand existing test files. In `src/tests/`:

**`offline.spec.ts`** — add to existing conflict tests:
- `test('ConflictModal receives conflict from syncStore')`
- `test('MANUAL strategy adds to syncStore conflicts')`
- `test('LOCAL_WINS strategy takes local values')`

**`auth.spec.ts`** — add:
- `test('biometric login restores session from stored token')`
- `test('biometric login redirects to dashboard on success')`
- `test('biometric login shows error when no stored token')`

**`components.spec.ts`** — add:
- `test('OrgSwitcher shows abbreviated org name')`
- `test('OrgSwitcher opens picker when multiple orgs')`
- `test('OrgSwitcher does not open picker with single org')`

### D3 — E2E Test Scaffolding [MOBILE ONLY]
**Close on completion:** `gh issue close 113 --repo maxymurm/shillings-mobile`

Set up Cypress for mobile E2E testing against the Ionic app in browser mode:

```bash
cd c:\Users\maxmm\shillings-mobile
npm install --save-dev cypress @cypress/vue
npx cypress open  # generates cypress.config.ts
```

Create `cypress/e2e/`:
- `auth.cy.ts` — login flow, biometric toggle, logout
- `dashboard.cy.ts` — dashboard loads, org switcher visible, cards show values
- `account-register.cy.ts` — accounts list, drill into register, search filter

Create `cypress.config.ts`:
```typescript
import { defineConfig } from 'cypress';
export default defineConfig({
  e2e: {
    baseUrl: 'http://localhost:8100',
    specPattern: 'cypress/e2e/**/*.cy.ts',
  },
});
```

Add `"cy:open": "cypress open"` and `"cy:run": "cypress run"` to `package.json` scripts.

---

## PHASE E — Performance & Optimization

### E1 — Backend Query Optimization [WEB]
**Close on completion:** `gh issue close 139 --repo maxymurm/shillings`

1. **Identify N+1 queries:** In `TransactionController::index()` and `AccountController::index()`,
   ensure eager loading:
   ```php
   Transaction::with(['splits.account', 'company'])->...
   Account::with(['accountType', 'parent'])->...
   ```

2. **Add DB indexes** (if missing) — check existing migrations and add a migration for:
   ```php
   Schema::table('splits', function (Blueprint $table) {
       $table->index(['account_id', 'transaction_id']);
   });
   Schema::table('transactions', function (Blueprint $table) {
       $table->index(['company_id', 'post_date']);
       $table->index(['company_id', 'is_posted']);
   });
   ```
   Check `database/migrations/` first — do NOT add duplicate indexes.

3. **Cache report queries** — wrap expensive report calculations (balance sheet, income
   statement) in `Cache::remember()` with a 5-minute TTL, keyed by `company_id`+date params.

4. **Add `PerformanceTest.php` assertions** — the file exists but check if it covers report
   endpoints. Add assertions that:
   - `GET /reports/balance-sheet` responds in < 500ms
   - `GET /reports/trial-balance` responds in < 500ms
   - `GET /accounts` with 100 accounts responds in < 300ms

### E2 — Mobile Bundle & Runtime Performance [MOBILE]
**Close on completion:** `gh issue close 114 --repo maxymurm/shillings-mobile`

1. **Lazy-load heavy pages** — in `src/router/index.ts`, verify all page imports use
   `() => import(...)` (dynamic imports). Fix any that use static imports.

2. **Virtual scroll for account register** — if the transaction list gets large, replace
   `IonList` + `IonInfiniteScroll` in `AccountRegisterPage.vue` with Ionic `IonVirtualScroll`
   (or keep infinite scroll but ensure DOM recycling is happening).

3. **Dexie query optimization** — in `src/offline/database.ts`, verify that frequently
   queried fields (`company_id`, `post_date`, `account_id`) have Dexie indexes defined:
   ```typescript
   transactions: '++id, company_id, post_date, is_posted, [company_id+post_date]',
   splits: '++id, transaction_id, account_id',
   ```
   Add missing indexes and note they require a `db.version(X+1)` schema bump.

4. **Capacitor splash/startup** — ensure lazy-loading is working at app boot. The auth
   store `init()` should timeout after 5s (add `Promise.race` with a timeout) so the
   offline fallback kicks in fast.

---

## PHASE F — Tax Summary Enhancements [WEB]

The tax summary API exists but has no Filament UI report page and no mobile view.

### F1 — Tax Summary Filament Report Page [WEB ONLY — exempt from parity]
**Close on completion:** `gh issue close 140 --repo maxymurm/shillings`

Create `app/Filament/Pages/TaxSummaryReportPage.php`:
- Date range picker (from/to) — defaults to current month
- Summary cards: Total Sales Tax, Total Purchase Tax, Net Tax Payable/Recoverable
- Table of taxes: name, rate, sales tax collected, purchase tax paid
- Export button calling `POST /api/reports/export` with `report: tax-summary`

### F2 — Tax Summary in Mobile Reports [MOBILE]
**Close on completion:** `gh issue close 115 --repo maxymurm/shillings-mobile`

Create `src/views/reports/TaxSummaryPage.vue`:
- Call `GET /api/taxes/summary?start_date=&end_date=`
- Show three summary cards (Sales Tax / Purchase Tax / Net Payable)
- Show a list of tax line items
- Add to the Reports section of the app (wherever `TrialBalancePage` is linked from)

Add to `src/router/index.ts`:
```typescript
{ path: 'reports/tax-summary', component: () => import('@/views/reports/TaxSummaryPage.vue') },
```

---

## PROGRESS LOG (Update as you complete each phase)

| Phase | Description | Backend Status | Mobile Status | Commit(s) |
|-------|-------------|---------------|---------------|-----------|
| A1 | Tax Summary Column Bug Fix | ⬜ pending | N/A | — |
| A2 | Wire Conflict Resolution UI | N/A | ⬜ pending | — |
| A3 | Implement Biometric Login | N/A | ⬜ pending | — |
| A4 | Account Register Search/Date | ✅ Exists | ⬜ pending | — |
| A5 | Wire Receipt Capture | ⬜ pending | ⬜ pending | — |
| B1 | Bulk Endpoint (API) | ⬜ pending | — | — |
| B2 | Filament Bulk Actions | ⬜ pending | — | — |
| B3 | Mobile Bulk Select | — | ⬜ pending | — |
| C1 | Report Text Search (Backend) | ⬜ pending | — | — |
| C2 | Report Filters (Mobile) | — | ⬜ pending | — |
| D1 | Fill Empty Backend Tests | ⬜ pending | — | — |
| D2 | Mobile Vitest Coverage | — | ⬜ pending | — |
| D3 | E2E Cypress Scaffolding | — | ⬜ pending | — |
| E1 | Backend Query Optimization | ⬜ pending | — | — |
| E2 | Mobile Bundle Performance | — | ⬜ pending | — |
| F1 | Tax Summary Filament Page | ⬜ pending | — | — |
| F2 | Tax Summary Mobile View | — | ⬜ pending | — |

---

## COMMIT CONVENTION

```
fix(tax): replace issue_date with issued_at in TaxService summary query
feat(transactions): add bulk post/void/delete API endpoint and tests
feat(transactions): add Filament bulk actions for post, void, delete
feat(mobile/register): add search and date range filters to account register
feat(mobile/biometrics): implement loginWithBiometric with stored token restore
feat(mobile/conflicts): mount ConflictModal and wire MANUAL strategy to syncStore
feat(mobile/receipts): wire camera capture to transaction create/edit forms
feat(reports): add text search filter to trial balance and balance sheet
feat(mobile/reports): add collapsible filter bar to all report pages
feat(mobile/tax): add tax summary report page
feat(admin): add tax summary Filament report page
test(api): fill empty test stubs for reports, tax, documents, import, sync
test(mobile): expand vitest coverage for biometrics, conflict UI, OrgSwitcher
test(e2e): scaffold Cypress with auth, dashboard, account register specs
perf(api): add eager loading, missing DB indexes, report query caching
perf(mobile): verify lazy routing, Dexie indexes, Capacitor boot timeout
```

---

*Generated: 2026-03-16 | Scope: Backend (shillings/develop) + Mobile (shillings-mobile/main)*
*All phases except F1 and D3 require paired commits. Run `php artisan test` after every backend change.*
