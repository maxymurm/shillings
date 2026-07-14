/**
 * Shillings IndexedDB Database
 * Local storage for offline-first architecture using Dexie.js
 * Stores transactions, accounts, contacts with sync tracking
 */

import Dexie from 'dexie';

const db = new Dexie('ShillingsDB');

// Schema version 1
db.version(1).stores({
    // Core accounting data (cached from server)
    accounts: 'id, company_id, account_type_id, parent_id, name, code, [company_id+name]',
    accountTypes: 'id, name, classification',
    transactions: 'id, company_id, transaction_date, is_posted, is_void, [company_id+transaction_date]',
    splits: 'id, transaction_id, account_id, tax_id, action, [transaction_id+account_id]',
    contacts: 'id, company_id, type, name, [company_id+type]',
    currencies: 'code, name',
    taxes: 'id, company_id, name, enabled',

    // Sync queue: offline mutations pending upload
    syncQueue: '++id, type, status, timestamp, entityType, entityId',

    // Sync metadata: track what has been synced
    syncMeta: 'key, value, updatedAt',

    // Offline-created transactions pending sync
    pendingTransactions: '++localId, company_id, status, createdAt',

    // Push notification subscriptions
    notificationPrefs: 'key, value',

    // Receipt images stored locally
    receipts: '++id, transactionId, localId, fileName, syncStatus',
});

export default db;

/**
 * Clear all cached data (not sync queue)
 */
export async function clearCachedData() {
    await Promise.all([
        db.accounts.clear(),
        db.accountTypes.clear(),
        db.transactions.clear(),
        db.splits.clear(),
        db.contacts.clear(),
        db.currencies.clear(),
        db.taxes.clear(),
    ]);
}

/**
 * Get database storage usage estimate
 */
export async function getStorageEstimate() {
    if (navigator.storage?.estimate) {
        return await navigator.storage.estimate();
    }
    return { usage: 0, quota: 0 };
}
