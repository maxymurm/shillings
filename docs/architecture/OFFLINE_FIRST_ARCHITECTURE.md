# Shillings: Offline-First Multi-Platform Architecture

**Generated:** January 30, 2026  
**Purpose:** Complete technical architecture for offline-first accounting application  
**Platforms:** Web (Laravel/Filament), Mobile (Compose Multiplatform), Progressive Web App

---

## 🎯 Vision

**Shillings** is a modern, offline-first, multi-platform accounting application that combines:
- The **accounting rigor** of GnuCash (double-entry, splits, precision)
- The **modern UX** of Akaunting (web-based, intuitive, multi-company)
- **Industry-leading offline capabilities** (work anywhere, sync everywhere)
- **Mobile-first experience** (Compose Multiplatform for iOS/Android)

---

## 🏗️ High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     CLIENT LAYER                             │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   Web App    │  │  Mobile App  │  │     PWA      │     │
│  │   (Vite +    │  │  (Compose    │  │  (Service    │     │
│  │  Livewire)   │  │Multiplatform)│  │   Worker)    │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│         │                  │                  │              │
│         └──────────────────┴──────────────────┘              │
│                            │                                  │
│                     OFFLINE LAYER                            │
│         ┌──────────────────┴──────────────────┐             │
│         │                                       │             │
│  ┌──────▼──────┐                      ┌───────▼────────┐   │
│  │ IndexedDB / │                      │  SQLite (Room) │   │
│  │ LocalStorage│                      │    Database    │   │
│  └─────────────┘                      └────────────────┘   │
│         │                                       │             │
│         └───────────────┬───────────────────────┘             │
│                         │                                     │
└─────────────────────────┼─────────────────────────────────────┘
                          │
                   SYNC ENGINE
                          │
┌─────────────────────────┼─────────────────────────────────────┐
│                    API GATEWAY                                │
│              (GraphQL + REST + WebSocket)                     │
└─────────────────────────┼─────────────────────────────────────┘
                          │
┌─────────────────────────▼─────────────────────────────────────┐
│                   BACKEND LAYER                               │
├───────────────────────────────────────────────────────────────┤
│                                                                │
│  ┌─────────────────────────────────────────────────────┐    │
│  │          Laravel 12 Application                      │    │
│  │  ┌───────────┐  ┌──────────┐  ┌─────────────┐      │    │
│  │  │  Filament │  │  Domain  │  │   Sync      │      │    │
│  │  │   Admin   │  │  Services│  │  Service    │      │    │
│  │  └───────────┘  └──────────┘  └─────────────┘      │    │
│  └─────────────────────────────────────────────────────┘    │
│                            │                                  │
│  ┌─────────────────────────▼──────────────────────────┐     │
│  │           Database Layer (PostgreSQL)              │     │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────────┐     │     │
│  │  │ Business │  │  Sync    │  │   Audit      │     │     │
│  │  │   Data   │  │  Queue   │  │   Logs       │     │     │
│  │  └──────────┘  └──────────┘  └──────────────┘     │     │
│  └─────────────────────────────────────────────────────┘    │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

---

## 🗄️ Data Model

### Core Entities (GnuCash-Inspired)

```sql
-- Companies (multi-tenancy)
CREATE TABLE companies (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    currency_code VARCHAR(3) NOT NULL,
    fiscal_year_start DATE,
    settings JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Accounts (hierarchical chart of accounts)
CREATE TABLE accounts (
    id UUID PRIMARY KEY,
    company_id UUID REFERENCES companies(id),
    parent_id UUID REFERENCES accounts(id),
    code VARCHAR(50),
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL, -- BANK, CASH, ASSET, LIABILITY, EQUITY, INCOME, EXPENSE, STOCK, MUTUAL_FUND, CREDIT_CARD, etc.
    description TEXT,
    notes TEXT,
    commodity_id UUID REFERENCES commodities(id),
    opening_balance NUMERIC(19,4),
    is_placeholder BOOLEAN DEFAULT FALSE,
    is_hidden BOOLEAN DEFAULT FALSE,
    tax_related BOOLEAN DEFAULT FALSE,
    path VARCHAR(1000), -- Materialized path for hierarchy
    level INTEGER, -- Depth in hierarchy
    metadata JSONB, -- Key-value frame (GnuCash style)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    
    -- Sync fields
    sync_version INTEGER DEFAULT 1,
    last_synced_at TIMESTAMP,
    device_id VARCHAR(100)
);

-- Commodities (currencies, stocks, etc.)
CREATE TABLE commodities (
    id UUID PRIMARY KEY,
    namespace VARCHAR(50) NOT NULL, -- CURRENCY, NASDAQ, NYSE, etc.
    mnemonic VARCHAR(20) NOT NULL, -- USD, IBM, AAPL
    full_name VARCHAR(255),
    fraction INTEGER DEFAULT 100, -- Smallest unit (100 for USD cents)
    quote_source VARCHAR(100), -- For price updates
    quote_timezone VARCHAR(50),
    metadata JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE(namespace, mnemonic)
);

-- Transactions (header)
CREATE TABLE transactions (
    id UUID PRIMARY KEY,
    company_id UUID REFERENCES companies(id),
    transaction_number VARCHAR(50),
    description VARCHAR(255) NOT NULL,
    notes TEXT,
    posted_at TIMESTAMP NOT NULL, -- Transaction date
    entered_at TIMESTAMP NOT NULL, -- Entry date
    currency_id UUID REFERENCES commodities(id),
    is_balanced BOOLEAN DEFAULT TRUE, -- Validation flag
    metadata JSONB,
    created_by UUID REFERENCES users(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    
    -- Sync fields
    sync_version INTEGER DEFAULT 1,
    last_synced_at TIMESTAMP,
    device_id VARCHAR(100),
    conflict_resolution VARCHAR(20) -- 'server', 'client', 'merged'
);

-- Splits (the fundamental accounting unit - GnuCash model)
CREATE TABLE splits (
    id UUID PRIMARY KEY,
    transaction_id UUID REFERENCES transactions(id) ON DELETE CASCADE,
    account_id UUID REFERENCES accounts(id),
    
    -- Amount in account's commodity
    amount_numerator BIGINT NOT NULL,
    amount_denominator BIGINT NOT NULL,
    
    -- Value in transaction's currency
    value_numerator BIGINT NOT NULL,
    value_denominator BIGINT NOT NULL,
    
    memo VARCHAR(255),
    action VARCHAR(50), -- Deposit, Withdrawal, Transfer, etc.
    reconcile_state CHAR(1) DEFAULT 'n', -- n=not, c=cleared, y=reconciled, f=frozen, v=void
    reconcile_date TIMESTAMP,
    lot_id UUID REFERENCES lots(id), -- For capital gains
    
    metadata JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    -- Sync fields
    sync_version INTEGER DEFAULT 1,
    last_synced_at TIMESTAMP,
    device_id VARCHAR(100)
);

-- Lots (for capital gains tracking)
CREATE TABLE lots (
    id UUID PRIMARY KEY,
    account_id UUID REFERENCES accounts(id),
    title VARCHAR(255),
    is_closed BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Prices (historical exchange rates and stock prices)
CREATE TABLE prices (
    id UUID PRIMARY KEY,
    commodity_id UUID REFERENCES commodities(id),
    currency_id UUID REFERENCES commodities(id),
    date TIMESTAMP NOT NULL,
    source VARCHAR(100), -- user, yahoo, etc.
    type VARCHAR(50), -- last, bid, ask, nav
    value_numerator BIGINT NOT NULL,
    value_denominator BIGINT NOT NULL,
    created_at TIMESTAMP,
    
    UNIQUE(commodity_id, currency_id, date, source, type)
);

-- Budgets
CREATE TABLE budgets (
    id UUID PRIMARY KEY,
    company_id UUID REFERENCES companies(id),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    period_type VARCHAR(20), -- monthly, quarterly, yearly
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Budget amounts
CREATE TABLE budget_amounts (
    id UUID PRIMARY KEY,
    budget_id UUID REFERENCES budgets(id) ON DELETE CASCADE,
    account_id UUID REFERENCES accounts(id),
    period_start DATE,
    period_end DATE,
    amount_numerator BIGINT NOT NULL,
    amount_denominator BIGINT NOT NULL
);

-- Scheduled Transactions
CREATE TABLE scheduled_transactions (
    id UUID PRIMARY KEY,
    company_id UUID REFERENCES companies(id),
    name VARCHAR(255) NOT NULL,
    frequency VARCHAR(50), -- daily, weekly, monthly, yearly
    interval INTEGER DEFAULT 1,
    start_date DATE NOT NULL,
    end_date DATE,
    occurrence_count INTEGER,
    occurrences_created INTEGER DEFAULT 0,
    is_enabled BOOLEAN DEFAULT TRUE,
    auto_create BOOLEAN DEFAULT FALSE,
    advance_days INTEGER DEFAULT 0,
    remind_days INTEGER DEFAULT 0,
    last_occurred_at TIMESTAMP,
    template_transaction_id UUID, -- Template for creating transactions
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Contacts (customers and vendors)
CREATE TABLE contacts (
    id UUID PRIMARY KEY,
    company_id UUID REFERENCES companies(id),
    type VARCHAR(20) NOT NULL, -- customer, vendor, employee
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    tax_number VARCHAR(100),
    currency_id UUID REFERENCES commodities(id),
    billing_address JSONB,
    shipping_address JSONB,
    payment_terms INTEGER, -- Days
    credit_limit NUMERIC(19,4),
    metadata JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    
    -- Sync fields
    sync_version INTEGER DEFAULT 1,
    last_synced_at TIMESTAMP
);

-- Documents (invoices, bills)
CREATE TABLE documents (
    id UUID PRIMARY KEY,
    company_id UUID REFERENCES companies(id),
    type VARCHAR(20) NOT NULL, -- invoice, bill, quote, credit_note
    document_number VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL, -- draft, sent, viewed, approved, paid, cancelled
    contact_id UUID REFERENCES contacts(id),
    contact_name VARCHAR(255),
    issued_at DATE NOT NULL,
    due_at DATE,
    currency_id UUID REFERENCES commodities(id),
    currency_rate NUMERIC(12,6),
    discount_type VARCHAR(20), -- fixed, percentage
    discount_rate NUMERIC(6,2),
    notes TEXT,
    footer TEXT,
    transaction_id UUID REFERENCES transactions(id), -- Linked accounting transaction
    metadata JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    
    -- Sync fields
    sync_version INTEGER DEFAULT 1,
    last_synced_at TIMESTAMP
);

-- Document items (line items)
CREATE TABLE document_items (
    id UUID PRIMARY KEY,
    document_id UUID REFERENCES documents(id) ON DELETE CASCADE,
    item_id UUID REFERENCES items(id),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    quantity NUMERIC(12,4) NOT NULL,
    price NUMERIC(19,4) NOT NULL,
    tax_id UUID REFERENCES taxes(id),
    discount NUMERIC(19,4),
    total NUMERIC(19,4),
    sort_order INTEGER,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Items (products/services)
CREATE TABLE items (
    id UUID PRIMARY KEY,
    company_id UUID REFERENCES companies(id),
    type VARCHAR(20) NOT NULL, -- product, service
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100),
    description TEXT,
    sale_price NUMERIC(19,4),
    purchase_price NUMERIC(19,4),
    category_id UUID REFERENCES categories(id),
    tax_id UUID REFERENCES taxes(id),
    track_inventory BOOLEAN DEFAULT FALSE,
    quantity_on_hand NUMERIC(12,4) DEFAULT 0,
    reorder_level NUMERIC(12,4),
    income_account_id UUID REFERENCES accounts(id),
    expense_account_id UUID REFERENCES accounts(id),
    asset_account_id UUID REFERENCES accounts(id),
    metadata JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    
    -- Sync fields
    sync_version INTEGER DEFAULT 1,
    last_synced_at TIMESTAMP
);

-- Taxes
CREATE TABLE taxes (
    id UUID PRIMARY KEY,
    company_id UUID REFERENCES companies(id),
    name VARCHAR(100) NOT NULL,
    rate NUMERIC(6,2) NOT NULL,
    type VARCHAR(20), -- normal, compound, inclusive
    is_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Categories (hierarchical)
CREATE TABLE categories (
    id UUID PRIMARY KEY,
    company_id UUID REFERENCES companies(id),
    parent_id UUID REFERENCES categories(id),
    type VARCHAR(20) NOT NULL, -- income, expense, item
    name VARCHAR(255) NOT NULL,
    color VARCHAR(7),
    is_enabled BOOLEAN DEFAULT TRUE,
    path VARCHAR(1000),
    level INTEGER,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Reconciliations
CREATE TABLE reconciliations (
    id UUID PRIMARY KEY,
    account_id UUID REFERENCES accounts(id),
    statement_date DATE NOT NULL,
    starting_balance NUMERIC(19,4),
    ending_balance NUMERIC(19,4),
    status VARCHAR(20), -- draft, completed
    difference NUMERIC(19,4),
    completed_at TIMESTAMP,
    created_by UUID REFERENCES users(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Reconciliation items
CREATE TABLE reconciliation_items (
    id UUID PRIMARY KEY,
    reconciliation_id UUID REFERENCES reconciliations(id) ON DELETE CASCADE,
    split_id UUID REFERENCES splits(id),
    is_reconciled BOOLEAN DEFAULT FALSE
);
```

### Sync Infrastructure Tables

```sql
-- Device registration
CREATE TABLE devices (
    id UUID PRIMARY KEY,
    user_id UUID REFERENCES users(id),
    device_identifier VARCHAR(255) UNIQUE NOT NULL,
    device_name VARCHAR(255),
    device_type VARCHAR(50), -- web, ios, android, pwa
    last_sync_at TIMESTAMP,
    last_active_at TIMESTAMP,
    push_token VARCHAR(500), -- For notifications
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Sync queue (changes waiting to be synced)
CREATE TABLE sync_queue (
    id BIGSERIAL PRIMARY KEY,
    device_id UUID REFERENCES devices(id),
    entity_type VARCHAR(100) NOT NULL, -- accounts, transactions, splits, etc.
    entity_id UUID NOT NULL,
    operation VARCHAR(20) NOT NULL, -- create, update, delete
    data JSONB NOT NULL, -- Snapshot of entity
    sync_version INTEGER NOT NULL,
    created_at TIMESTAMP,
    synced_at TIMESTAMP,
    
    INDEX idx_sync_queue_device (device_id, synced_at),
    INDEX idx_sync_queue_entity (entity_type, entity_id)
);

-- Conflict log
CREATE TABLE sync_conflicts (
    id BIGSERIAL PRIMARY KEY,
    device_id UUID REFERENCES devices(id),
    entity_type VARCHAR(100) NOT NULL,
    entity_id UUID NOT NULL,
    server_version INTEGER,
    client_version INTEGER,
    server_data JSONB,
    client_data JSONB,
    resolution VARCHAR(20), -- server_wins, client_wins, merged, manual
    resolved_data JSONB,
    resolved_at TIMESTAMP,
    resolved_by UUID REFERENCES users(id),
    created_at TIMESTAMP
);

-- Sync log (audit trail)
CREATE TABLE sync_logs (
    id BIGSERIAL PRIMARY KEY,
    device_id UUID REFERENCES devices(id),
    sync_type VARCHAR(20), -- push, pull, bidirectional
    entities_sent INTEGER DEFAULT 0,
    entities_received INTEGER DEFAULT 0,
    conflicts_detected INTEGER DEFAULT 0,
    errors INTEGER DEFAULT 0,
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    duration_ms INTEGER,
    status VARCHAR(20) -- success, partial, failed
);
```

---

## 🔄 Offline-First Sync Architecture

### Sync Strategy

**Bidirectional Sync with Conflict Resolution**

```
Client (Offline)                    Server (Always Online)
┌──────────────┐                    ┌──────────────┐
│              │                    │              │
│  Local DB    │                    │ PostgreSQL   │
│  (IndexedDB  │                    │              │
│   or SQLite) │                    │              │
│              │                    │              │
│ version: 5   │◄──────Pull────────┤ version: 7   │
│              │                    │              │
│              │                    │              │
│ Changes:     │──────Push────────►│              │
│ - Account A  │                    │ Receives:    │
│   (v5→v6)    │                    │ - Account A  │
│ - Trans B    │                    │   (v5→v6)    │
│   (v3→v4)    │                    │ - Trans B    │
│              │                    │   (v3→v4)    │
│              │                    │              │
│              │◄────Conflict──────┤ Detects:     │
│              │                    │ Trans B      │
│              │                    │ already v5!  │
│              │                    │              │
│ Resolves:    │◄───Resolution────┤ Server wins  │
│ Trans B v5   │                    │ or Merge     │
│              │                    │              │
└──────────────┘                    └──────────────┘
```

### Sync Algorithm (Pseudocode)

```typescript
interface SyncEngine {
    // Main sync function
    async sync(): Promise<SyncResult> {
        try {
            // 1. Pull changes from server
            const serverChanges = await this.pullFromServer();
            
            // 2. Push local changes to server
            const localChanges = await this.getLocalChanges();
            const pushResult = await this.pushToServer(localChanges);
            
            // 3. Handle conflicts
            const conflicts = pushResult.conflicts;
            const resolved = await this.resolveConflicts(conflicts);
            
            // 4. Apply server changes locally
            await this.applyServerChanges(serverChanges);
            
            // 5. Update sync metadata
            await this.updateSyncMetadata();
            
            return {
                success: true,
                changesPulled: serverChanges.length,
                changesPushed: localChanges.length,
                conflictsResolved: resolved.length
            };
        } catch (error) {
            return { success: false, error };
        }
    }
    
    // Pull changes from server since last sync
    async pullFromServer(): Promise<EntityChange[]> {
        const lastSyncTime = await this.getLastSyncTime();
        const deviceId = await this.getDeviceId();
        
        const response = await api.post('/sync/pull', {
            device_id: deviceId,
            last_sync_at: lastSyncTime,
            entity_types: ['accounts', 'transactions', 'splits', 'contacts']
        });
        
        return response.data.changes;
    }
    
    // Push local changes to server
    async pushToServer(changes: EntityChange[]): Promise<PushResult> {
        const deviceId = await this.getDeviceId();
        
        const response = await api.post('/sync/push', {
            device_id: deviceId,
            changes: changes
        });
        
        return response.data;
    }
    
    // Get changes made locally since last sync
    async getLocalChanges(): Promise<EntityChange[]> {
        const lastSyncTime = await this.getLastSyncTime();
        
        // Query all tables for changes
        const accounts = await db.accounts
            .where('updated_at').above(lastSyncTime)
            .toArray();
        
        const transactions = await db.transactions
            .where('updated_at').above(lastSyncTime)
            .toArray();
        
        const splits = await db.splits
            .where('updated_at').above(lastSyncTime)
            .toArray();
        
        // Convert to EntityChange format
        return this.convertToEntityChanges([
            ...accounts, ...transactions, ...splits
        ]);
    }
    
    // Conflict resolution
    async resolveConflicts(conflicts: Conflict[]): Promise<Resolution[]> {
        const resolutions = [];
        
        for (const conflict of conflicts) {
            let resolution;
            
            // Strategy 1: Last-Write-Wins (LWW)
            if (conflict.client_updated_at > conflict.server_updated_at) {
                resolution = { type: 'client_wins', data: conflict.client_data };
            } else {
                resolution = { type: 'server_wins', data: conflict.server_data };
            }
            
            // Strategy 2: Smart Merge (for specific fields)
            if (this.isMergeable(conflict)) {
                resolution = {
                    type: 'merged',
                    data: this.mergeChanges(
                        conflict.server_data,
                        conflict.client_data
                    )
                };
            }
            
            // Strategy 3: User Resolution (for critical data)
            if (this.requiresUserInput(conflict)) {
                resolution = await this.promptUser(conflict);
            }
            
            resolutions.push(resolution);
            
            // Log conflict
            await this.logConflict(conflict, resolution);
        }
        
        return resolutions;
    }
    
    // Apply server changes to local database
    async applyServerChanges(changes: EntityChange[]): Promise<void> {
        await db.transaction('rw', db.accounts, db.transactions, db.splits, async () => {
            for (const change of changes) {
                switch (change.operation) {
                    case 'create':
                    case 'update':
                        await db[change.entity_type].put(change.data);
                        break;
                    case 'delete':
                        await db[change.entity_type].delete(change.entity_id);
                        break;
                }
                
                // Mark as synced
                await this.markAsSynced(change.entity_type, change.entity_id);
            }
        });
    }
    
    // Smart merge for specific types
    mergeChanges(serverData: any, clientData: any): any {
        // For transactions: merge metadata, keep server balance
        if (serverData.type === 'transaction') {
            return {
                ...serverData,
                metadata: {
                    ...serverData.metadata,
                    ...clientData.metadata
                }
            };
        }
        
        // For accounts: merge notes, keep server balance
        if (serverData.type === 'account') {
            return {
                ...serverData,
                notes: clientData.notes || serverData.notes,
                metadata: {
                    ...serverData.metadata,
                    ...clientData.metadata
                }
            };
        }
        
        return serverData; // Default to server
    }
}
```

### Offline Capabilities by Platform

#### Web (Browser - IndexedDB + LocalStorage)

```typescript
// Service Worker for offline support
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open('shillings-v1').then((cache) => {
            return cache.addAll([
                '/',
                '/app.js',
                '/app.css',
                '/offline.html'
            ]);
        })
    );
});

// Intercept network requests
self.addEventListener('fetch', (event) => {
    event.respondWith(
        fetch(event.request)
            .catch(() => {
                return caches.match(event.request);
            })
    );
});

// IndexedDB schema
const dbSchema = {
    companies: 'id, name, currency_code',
    accounts: 'id, company_id, parent_id, name, type, sync_version',
    transactions: 'id, company_id, posted_at, sync_version',
    splits: 'id, transaction_id, account_id, sync_version',
    commodities: 'id, [namespace+mnemonic]',
    contacts: 'id, company_id, type, name',
    // ... other tables
    
    // Sync metadata
    _sync_metadata: 'key',
    _sync_queue: '++id, entity_type, entity_id, synced'
};

// No sign-in when offline
const authCache = {
    // Store encrypted token in IndexedDB
    async cacheCredentials(token: string, user: User) {
        await db._sync_metadata.put({
            key: 'auth_token',
            value: await encrypt(token),
            expires_at: Date.now() + (30 * 24 * 60 * 60 * 1000) // 30 days
        });
        
        await db._sync_metadata.put({
            key: 'current_user',
            value: user
        });
    },
    
    // Check if valid cached credentials exist
    async hasValidCache(): Promise<boolean> {
        const token = await db._sync_metadata.get('auth_token');
        return token && token.expires_at > Date.now();
    },
    
    // Use cached credentials when offline
    async getCachedAuth(): Promise<AuthData | null> {
        if (!await this.hasValidCache()) return null;
        
        const token = await db._sync_metadata.get('auth_token');
        const user = await db._sync_metadata.get('current_user');
        
        return {
            token: await decrypt(token.value),
            user: user.value
        };
    }
};
```

#### Mobile (Compose Multiplatform - Room/SQLite)

```kotlin
// Room database schema
@Database(
    entities = [
        Company::class,
        Account::class,
        Transaction::class,
        Split::class,
        Contact::class,
        // ... other entities
        SyncMetadata::class,
        SyncQueueItem::class
    ],
    version = 1
)
abstract class ShillingsDatabase : RoomDatabase() {
    abstract fun accountDao(): AccountDao
    abstract fun transactionDao(): TransactionDao
    abstract fun splitDao(): SplitDao
    abstract fun syncDao(): SyncDao
    // ... other DAOs
}

// Offline-first repository pattern
class TransactionRepository(
    private val api: ShillingsApi,
    private val db: ShillingsDatabase,
    private val syncEngine: SyncEngine
) {
    // Always read from local database
    fun getTransactions(
        accountId: UUID,
        from: LocalDate,
        to: LocalDate
    ): Flow<List<Transaction>> {
        return db.transactionDao()
            .getTransactionsByAccountAndDateRange(accountId, from, to)
            .map { entities -> entities.map { it.toDomain() } }
    }
    
    // Write to local database, queue for sync
    suspend fun createTransaction(transaction: Transaction): Result<Transaction> {
        return try {
            // Validate double-entry
            if (!transaction.isBalanced()) {
                return Result.failure(Exception("Transaction must be balanced"))
            }
            
            // Save to local database
            val entity = transaction.toEntity()
            db.transactionDao().insert(entity)
            
            // Queue for sync
            syncEngine.queueForSync(
                entityType = "transaction",
                entityId = transaction.id,
                operation = "create",
                data = transaction
            )
            
            // Trigger sync if online
            if (networkMonitor.isOnline()) {
                syncEngine.syncNow()
            }
            
            Result.success(transaction)
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    // Background sync when coming online
    init {
        networkMonitor.isOnline.collect { online ->
            if (online) {
                syncEngine.syncNow()
            }
        }
    }
}

// Cached authentication
class AuthCache(private val db: ShillingsDatabase) {
    // Store encrypted credentials
    suspend fun cacheAuth(token: String, user: User) {
        val encrypted = encrypt(token)
        db.syncDao().putMetadata(
            SyncMetadata(
                key = "auth_token",
                value = encrypted,
                expiresAt = System.currentTimeMillis() + (30 * 24 * 60 * 60 * 1000)
            )
        )
        db.syncDao().putMetadata(
            SyncMetadata(key = "current_user", value = Json.encodeToString(user))
        )
    }
    
    // Check if valid cache exists
    suspend fun hasValidCache(): Boolean {
        val metadata = db.syncDao().getMetadata("auth_token") ?: return false
        return metadata.expiresAt > System.currentTimeMillis()
    }
    
    // Use cached auth when offline
    suspend fun getCachedAuth(): AuthData? {
        if (!hasValidCache()) return null
        
        val tokenMeta = db.syncDao().getMetadata("auth_token") ?: return null
        val userMeta = db.syncDao().getMetadata("current_user") ?: return null
        
        return AuthData(
            token = decrypt(tokenMeta.value),
            user = Json.decodeFromString(userMeta.value)
        )
    }
}
```

---

## 🚀 Technology Stack

### Backend (Laravel 12)
- **Framework:** Laravel 12 + PHP 8.3
- **Admin Panel:** Filament 4.3
- **Database:** PostgreSQL 16
- **Cache:** Redis 7
- **Queue:** Laravel Queue (Redis driver)
- **API:** GraphQL (Lighthouse) + REST (Laravel Sanctum)
- **WebSocket:** Laravel Reverb
- **Search:** Meilisearch or Typesense

### Web Frontend
- **Build:** Vite 5
- **Framework:** Livewire 3 (for Filament)
- **Styling:** Tailwind CSS 4
- **Offline:** IndexedDB (Dexie.js)
- **PWA:** Workbox
- **Charts:** ApexCharts

### Mobile (Compose Multiplatform)
- **UI:** Compose Multiplatform
- **Database:** Room (Android) / SQLDelight (iOS)
- **Networking:** Ktor Client
- **Serialization:** Kotlinx Serialization
- **DI:** Koin
- **Coroutines:** Kotlinx Coroutines
- **Navigation:** Compose Navigation

### Deployment
- **Container:** Docker + Docker Compose
- **Orchestration:** Kubernetes (optional)
- **CI/CD:** GitHub Actions
- **Hosting:** AWS / DigitalOcean / Self-hosted
- **CDN:** CloudFlare
- **Monitoring:** Sentry + Laravel Telescope

---

## 📱 Platform-Specific Features

### Web Application
- ✅ Full accounting functionality
- ✅ Filament admin panel
- ✅ Responsive design
- ✅ Offline mode (IndexedDB)
- ✅ Service Worker
- ✅ Push notifications
- ✅ Print-optimized reports

### Progressive Web App (PWA)
- ✅ Installable
- ✅ Offline-first
- ✅ App-like experience
- ✅ Background sync
- ✅ Share target API
- ✅ File system access API (for imports)

### Mobile App (iOS/Android)
- ✅ Native performance
- ✅ Biometric authentication
- ✅ Camera for receipt scanning
- ✅ OCR for receipt data extraction
- ✅ Barcode scanning
- ✅ Push notifications
- ✅ Background sync
- ✅ Siri shortcuts (iOS) / Google Assistant (Android)
- ✅ Widgets for quick access
- ✅ Offline-first architecture

---

## 🔐 Security Architecture

### Authentication
- JWT tokens (short-lived)
- Refresh tokens (long-lived, stored securely)
- Biometric authentication on mobile
- 2FA (TOTP)
- Magic link login
- OAuth (Google, Apple, Microsoft)

### Authorization
- Role-based access control (RBAC)
- Permission-based (granular)
- Company-level isolation
- Account-level permissions
- Audit trail for all actions

### Data Security
- End-to-end encryption (E2EE) option
- At-rest encryption (database)
- In-transit encryption (TLS 1.3)
- Encrypted local storage
- Secure key storage (Keychain/KeyStore)

### Offline Security
- Cached credentials encrypted
- Biometric unlock required
- Auto-lock after inactivity
- Remote wipe capability
- Device verification

---

**End of Architecture Document**  
**Next:** Phase Planning → Issue Creation → Project Board Setup
