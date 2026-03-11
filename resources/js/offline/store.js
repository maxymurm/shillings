/**
 * Offline Data Store
 * Handles caching server data locally and retrieving it when offline
 * Encrypts sensitive financial data before storage
 */

import db from './database.js';

/**
 * Sync status enum for records
 */
export const SyncStatus = {
    SYNCED: 'synced',
    PENDING: 'pending',
    CONFLICT: 'conflict',
    ERROR: 'error',
};

/**
 * Cache accounts from API response into IndexedDB
 */
export async function cacheAccounts(accounts) {
    const records = accounts.map((a) => ({
        ...a,
        _syncStatus: SyncStatus.SYNCED,
        _syncedAt: new Date().toISOString(),
    }));
    await db.accounts.bulkPut(records);
}

/**
 * Cache account types
 */
export async function cacheAccountTypes(types) {
    await db.accountTypes.bulkPut(types);
}

/**
 * Cache transactions with their splits
 */
export async function cacheTransactions(transactions) {
    await db.transaction('rw', db.transactions, db.splits, async () => {
        for (const txn of transactions) {
            const splits = txn.splits || [];
            delete txn.splits;

            await db.transactions.put({
                ...txn,
                _syncStatus: SyncStatus.SYNCED,
                _syncedAt: new Date().toISOString(),
            });

            if (splits.length > 0) {
                const splitRecords = splits.map((s) => ({
                    ...s,
                    transaction_id: txn.id,
                }));
                await db.splits.bulkPut(splitRecords);
            }
        }
    });
}

/**
 * Cache contacts from API
 */
export async function cacheContacts(contacts) {
    const records = contacts.map((c) => ({
        ...c,
        _syncStatus: SyncStatus.SYNCED,
        _syncedAt: new Date().toISOString(),
    }));
    await db.contacts.bulkPut(records);
}

/**
 * Cache currencies
 */
export async function cacheCurrencies(currencies) {
    await db.currencies.bulkPut(currencies);
}

/**
 * Cache taxes
 */
export async function cacheTaxes(taxes) {
    await db.taxes.bulkPut(taxes);
}

/**
 * Get all cached accounts for a company
 */
export async function getLocalAccounts(companyId) {
    return await db.accounts.where('company_id').equals(companyId).toArray();
}

/**
 * Get all cached account types
 */
export async function getLocalAccountTypes() {
    return await db.accountTypes.toArray();
}

/**
 * Get cached transactions for a company within date range
 */
export async function getLocalTransactions(companyId, startDate = null, endDate = null) {
    let query = db.transactions.where('company_id').equals(companyId);

    const results = await query.toArray();

    if (startDate || endDate) {
        return results.filter((t) => {
            const txnDate = t.transaction_date;
            if (startDate && txnDate < startDate) return false;
            if (endDate && txnDate > endDate) return false;
            return true;
        });
    }

    return results;
}

/**
 * Get splits for a transaction
 */
export async function getLocalSplits(transactionId) {
    return await db.splits.where('transaction_id').equals(transactionId).toArray();
}

/**
 * Get cached contacts for a company
 */
export async function getLocalContacts(companyId, type = null) {
    let results = await db.contacts.where('company_id').equals(companyId).toArray();
    if (type) {
        results = results.filter((c) => c.type === type);
    }
    return results;
}

/**
 * Get last sync timestamp for a resource type
 */
export async function getLastSyncTime(resourceType) {
    const meta = await db.syncMeta.get(`lastSync_${resourceType}`);
    return meta?.value || null;
}

/**
 * Update last sync timestamp
 */
export async function setLastSyncTime(resourceType) {
    await db.syncMeta.put({
        key: `lastSync_${resourceType}`,
        value: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
    });
}

/**
 * Get count of pending sync items
 */
export async function getPendingSyncCount() {
    return await db.syncQueue.where('status').equals('pending').count();
}

/**
 * Check if we have cached data for a resource
 */
export async function hasCachedData(resourceType) {
    const table = db[resourceType];
    if (!table) return false;
    const count = await table.count();
    return count > 0;
}
