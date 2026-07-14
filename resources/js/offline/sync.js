/**
 * Background Sync Service
 * Syncs queued changes when back online
 * Uses exponential backoff for retries
 */

import db from './database.js';
import {
    cacheAccounts,
    cacheContacts,
    cacheTransactions,
    setLastSyncTime,
    getLastSyncTime,
} from './store.js';
import { markTransactionSynced, markTransactionConflict } from './transactions.js';

const MAX_RETRY_ATTEMPTS = 5;
const BASE_BACKOFF_MS = 1000;

/**
 * Sync status tracking
 */
let syncInProgress = false;
let syncListeners = [];

export function onSyncStatusChange(callback) {
    syncListeners.push(callback);
    return () => {
        syncListeners = syncListeners.filter((cb) => cb !== callback);
    };
}

function notifySyncStatus(status) {
    syncListeners.forEach((cb) => cb(status));
}

/**
 * Full sync: upload pending changes, then download fresh data
 */
export async function fullSync(authToken) {
    if (syncInProgress) return { status: 'already_running' };
    if (!navigator.onLine) return { status: 'offline' };

    syncInProgress = true;
    notifySyncStatus({ phase: 'starting', progress: 0 });

    try {
        // Phase 1: Upload pending changes
        notifySyncStatus({ phase: 'uploading', progress: 10 });
        const uploadResult = await processSyncQueue(authToken);

        // Phase 2: Download fresh data
        notifySyncStatus({ phase: 'downloading', progress: 50 });
        const downloadResult = await downloadFreshData(authToken);

        notifySyncStatus({ phase: 'complete', progress: 100 });

        return {
            status: 'success',
            uploaded: uploadResult,
            downloaded: downloadResult,
            timestamp: new Date().toISOString(),
        };
    } catch (error) {
        notifySyncStatus({ phase: 'error', error: error.message });
        return { status: 'error', error: error.message };
    } finally {
        syncInProgress = false;
    }
}

/**
 * Process the sync queue: upload pending mutations
 */
export async function processSyncQueue(authToken) {
    const pendingItems = await db.syncQueue
        .where('status')
        .equals('pending')
        .toArray();

    if (pendingItems.length === 0) return { processed: 0, failed: 0 };

    let processed = 0;
    let failed = 0;

    // Sort by timestamp to maintain order
    pendingItems.sort((a, b) => a.timestamp - b.timestamp);

    for (const item of pendingItems) {
        try {
            const success = await syncItem(item, authToken);
            if (success) {
                await db.syncQueue.update(item.id, { status: 'completed' });
                processed++;
            } else {
                failed++;
            }
        } catch (error) {
            const attempts = (item.attempts || 0) + 1;

            if (attempts >= MAX_RETRY_ATTEMPTS) {
                await db.syncQueue.update(item.id, {
                    status: 'failed',
                    attempts,
                    lastError: error.message,
                });
                failed++;
            } else {
                await db.syncQueue.update(item.id, {
                    attempts,
                    lastError: error.message,
                    nextRetry: Date.now() + calculateBackoff(attempts),
                });
                failed++;
            }
        }
    }

    return { processed, failed, total: pendingItems.length };
}

/**
 * Sync a single queued item to the server
 */
async function syncItem(item, authToken) {
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${authToken}`,
    };

    let url, method, body;

    switch (item.entityType) {
        case 'transaction':
            if (item.type === 'CREATE') {
                url = '/api/transactions';
                method = 'POST';
                body = JSON.stringify(item.payload);
            } else if (item.type === 'UPDATE') {
                url = `/api/transactions/${item.entityId}`;
                method = 'PUT';
                body = JSON.stringify(item.payload);
            } else if (item.type === 'DELETE') {
                url = `/api/transactions/${item.entityId}`;
                method = 'DELETE';
            }
            break;
        default:
            console.warn(`Unknown entity type: ${item.entityType}`);
            return false;
    }

    const response = await fetch(url, { method, headers, body });

    if (response.status === 409) {
        // Conflict - server has a different version
        const serverData = await response.json();
        await markTransactionConflict(item.entityId, serverData);
        return false;
    }

    if (!response.ok) {
        throw new Error(`Sync failed: ${response.status} ${response.statusText}`);
    }

    // On success, mark the local transaction as synced
    if (item.type === 'CREATE' && item.entityType === 'transaction') {
        const result = await response.json();
        await markTransactionSynced(item.entityId, result.data?.id);
    }

    return true;
}

/**
 * Download fresh data from server
 */
async function downloadFreshData(authToken) {
    const headers = {
        'Accept': 'application/json',
        'Authorization': `Bearer ${authToken}`,
    };

    const results = { accounts: 0, contacts: 0, transactions: 0 };

    try {
        // Download accounts
        const lastAccountSync = await getLastSyncTime('accounts');
        let accountsUrl = '/api/accounts?per_page=500';
        if (lastAccountSync) {
            accountsUrl += `&updated_after=${lastAccountSync}`;
        }

        const accountsRes = await fetch(accountsUrl, { headers });
        if (accountsRes.ok) {
            const accountsData = await accountsRes.json();
            const accounts = accountsData.data || accountsData;
            if (Array.isArray(accounts)) {
                await cacheAccounts(accounts);
                results.accounts = accounts.length;
            }
            await setLastSyncTime('accounts');
        }

        // Download contacts
        const contactsRes = await fetch('/api/contacts?per_page=500', { headers });
        if (contactsRes.ok) {
            const contactsData = await contactsRes.json();
            const contacts = contactsData.data || contactsData;
            if (Array.isArray(contacts)) {
                await cacheContacts(contacts);
                results.contacts = contacts.length;
            }
            await setLastSyncTime('contacts');
        }

        // Download recent transactions
        const txnRes = await fetch('/api/transactions?per_page=100&sort=-transaction_date', { headers });
        if (txnRes.ok) {
            const txnData = await txnRes.json();
            const txns = txnData.data || txnData;
            if (Array.isArray(txns)) {
                await cacheTransactions(txns);
                results.transactions = txns.length;
            }
            await setLastSyncTime('transactions');
        }
    } catch (error) {
        console.warn('[Sync] Download failed:', error.message);
    }

    return results;
}

/**
 * Calculate exponential backoff delay
 */
function calculateBackoff(attempt) {
    const delay = BASE_BACKOFF_MS * Math.pow(2, attempt - 1);
    // Add jitter (±25%)
    const jitter = delay * (0.75 + Math.random() * 0.5);
    return Math.min(jitter, 30000); // Cap at 30s
}

/**
 * Get current sync status
 */
export function isSyncing() {
    return syncInProgress;
}

/**
 * Get sync queue statistics
 */
export async function getSyncStats() {
    const pending = await db.syncQueue.where('status').equals('pending').count();
    const failed = await db.syncQueue.where('status').equals('failed').count();
    const completed = await db.syncQueue.where('status').equals('completed').count();

    return { pending, failed, completed, total: pending + failed + completed };
}

/**
 * Clean up completed sync items older than 7 days
 */
export async function cleanupSyncQueue() {
    const oneWeekAgo = Date.now() - 7 * 24 * 60 * 60 * 1000;
    await db.syncQueue
        .where('status')
        .equals('completed')
        .filter((item) => item.timestamp < oneWeekAgo)
        .delete();
}
