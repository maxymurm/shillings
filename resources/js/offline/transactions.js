/**
 * Offline Transaction Entry
 * Queue transactions while offline, validate locally, sync when online
 */

import db from './database.js';
import { SyncStatus } from './store.js';

/**
 * Validation result
 */
class ValidationResult {
    constructor(valid, errors = []) {
        this.valid = valid;
        this.errors = errors;
    }

    static success() {
        return new ValidationResult(true);
    }

    static failure(errors) {
        return new ValidationResult(false, Array.isArray(errors) ? errors : [errors]);
    }
}

/**
 * Create a transaction offline
 * Stores in IndexedDB with pending status, validates using cached accounts
 */
export async function createOfflineTransaction(transactionData) {
    // Validate locally
    const validation = await validateTransaction(transactionData);
    if (!validation.valid) {
        return { success: false, errors: validation.errors };
    }

    const now = new Date().toISOString();
    const localId = `offline_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;

    // Store the pending transaction
    const pendingTxn = {
        localId,
        company_id: transactionData.company_id,
        description: transactionData.description,
        transaction_date: transactionData.transaction_date,
        currency_code: transactionData.currency_code,
        splits: transactionData.splits,
        status: SyncStatus.PENDING,
        createdAt: now,
        updatedAt: now,
        syncAttempts: 0,
        lastError: null,
    };

    const id = await db.pendingTransactions.add(pendingTxn);

    // Add to sync queue
    await db.syncQueue.add({
        type: 'CREATE',
        entityType: 'transaction',
        entityId: localId,
        status: 'pending',
        timestamp: Date.now(),
        payload: transactionData,
        attempts: 0,
        lastError: null,
    });

    // Try to register background sync
    if ('serviceWorker' in navigator && 'SyncManager' in window) {
        try {
            const registration = await navigator.serviceWorker.ready;
            await registration.sync.register('shillings-sync');
        } catch {
            // Background sync not available, will sync on reconnect
        }
    }

    return { success: true, localId, id };
}

/**
 * Get all pending (unsent) transactions
 */
export async function getPendingTransactions() {
    return await db.pendingTransactions
        .where('status')
        .equals(SyncStatus.PENDING)
        .toArray();
}

/**
 * Get all offline transactions (all statuses)
 */
export async function getAllOfflineTransactions() {
    return await db.pendingTransactions.toArray();
}

/**
 * Mark a pending transaction as synced (remove from pending)
 */
export async function markTransactionSynced(localId, serverId) {
    const pending = await db.pendingTransactions
        .where('localId')
        .equals(localId)
        .first();

    if (pending) {
        await db.pendingTransactions.update(pending.id ?? pending.localId, {
            status: SyncStatus.SYNCED,
            serverId,
            syncedAt: new Date().toISOString(),
        });
    }
}

/**
 * Mark a pending transaction as having a conflict
 */
export async function markTransactionConflict(localId, serverVersion) {
    const pending = await db.pendingTransactions
        .where('localId')
        .equals(localId)
        .first();

    if (pending) {
        await db.pendingTransactions.update(pending.id ?? pending.localId, {
            status: SyncStatus.CONFLICT,
            serverVersion,
            conflictAt: new Date().toISOString(),
        });

        // Push conflict to sync store so ConflictModal can display it
        try {
            const { useSyncStore } = await import('@/stores/sync');
            const syncStore = useSyncStore();
            syncStore.addConflict({
                entity_type: 'transaction',
                entity_id: pending.id ?? pending.localId,
                local_data: pending,
                server_data: serverVersion,
                local_updated_at: pending.updatedAt,
                server_updated_at: serverVersion.updated_at ?? new Date().toISOString(),
            });
        } catch {
            // Store may not be available during background sync
        }
    }
}

/**
 * Validate a transaction locally using cached account data
 */
export async function validateTransaction(data) {
    const errors = [];

    // Required fields
    if (!data.company_id) errors.push('Company is required');
    if (!data.description?.trim()) errors.push('Description is required');
    if (!data.transaction_date) errors.push('Transaction date is required');
    if (!data.currency_code) errors.push('Currency is required');

    // Splits validation
    if (!data.splits || !Array.isArray(data.splits) || data.splits.length < 2) {
        errors.push('At least 2 splits are required for double-entry');
    } else {
        let totalDebits = 0;
        let totalCredits = 0;

        for (const split of data.splits) {
            if (!split.account_id) {
                errors.push('Each split must have an account');
                continue;
            }

            // Validate account exists in local cache
            const account = await db.accounts.get(split.account_id);
            if (!account) {
                errors.push(`Account ${split.account_id} not found in local cache`);
            }

            const amount = parseFloat(split.amount) || 0;
            if (amount === 0) {
                errors.push('Split amount cannot be zero');
            }

            if (split.action === 'DEBIT') {
                totalDebits += amount;
            } else if (split.action === 'CREDIT') {
                totalCredits += amount;
            } else {
                errors.push('Split action must be DEBIT or CREDIT');
            }
        }

        // Verify debits equal credits (with floating point tolerance)
        if (Math.abs(totalDebits - totalCredits) > 0.01) {
            errors.push(`Debits (${totalDebits.toFixed(2)}) must equal credits (${totalCredits.toFixed(2)})`);
        }
    }

    // Date validation
    if (data.transaction_date) {
        const txnDate = new Date(data.transaction_date);
        if (isNaN(txnDate.getTime())) {
            errors.push('Invalid transaction date');
        }
    }

    return errors.length === 0
        ? ValidationResult.success()
        : ValidationResult.failure(errors);
}

/**
 * Delete a pending transaction (if not yet synced)
 */
export async function deletePendingTransaction(localId) {
    const pending = await db.pendingTransactions
        .where('localId')
        .equals(localId)
        .first();

    if (pending && pending.status === SyncStatus.PENDING) {
        await db.pendingTransactions.delete(pending.id ?? pending.localId);

        // Remove from sync queue
        await db.syncQueue
            .where('entityId')
            .equals(localId)
            .delete();

        return true;
    }
    return false;
}
