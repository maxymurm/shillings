/**
 * Conflict Resolution Module
 * Handles sync conflicts with last-write-wins + full audit trail
 * Manual merge UI support for complex conflicts
 */

import db from './database.js';

/**
 * Conflict resolution strategies
 */
export const ConflictStrategy = {
    LOCAL_WINS: 'local_wins',    // Keep local version
    SERVER_WINS: 'server_wins',  // Keep server version
    MANUAL: 'manual',            // User decides
};

/**
 * A stored conflict record
 */
function createConflictRecord(entityType, entityId, localVersion, serverVersion) {
    return {
        entityType,
        entityId,
        localVersion,
        serverVersion,
        detectedAt: new Date().toISOString(),
        resolvedAt: null,
        resolution: null,
        resolvedBy: null,
    };
}

/**
 * Detect and store a conflict
 */
export async function recordConflict(entityType, entityId, localVersion, serverVersion) {
    const conflict = createConflictRecord(entityType, entityId, localVersion, serverVersion);

    // Store in sync queue as a conflict entry
    await db.syncQueue.add({
        type: 'CONFLICT',
        entityType,
        entityId,
        status: 'conflict',
        timestamp: Date.now(),
        payload: conflict,
        attempts: 0,
        lastError: null,
    });

    return conflict;
}

/**
 * Get all unresolved conflicts
 */
export async function getUnresolvedConflicts() {
    return await db.syncQueue
        .where('status')
        .equals('conflict')
        .toArray();
}

/**
 * Resolve a conflict with the chosen strategy
 */
export async function resolveConflict(conflictId, strategy, authToken, mergedData = null) {
    const conflict = await db.syncQueue.get(conflictId);
    if (!conflict) throw new Error('Conflict not found');

    switch (strategy) {
        case ConflictStrategy.LOCAL_WINS:
            return await resolveWithLocalVersion(conflict, authToken);

        case ConflictStrategy.SERVER_WINS:
            return await resolveWithServerVersion(conflict);

        case ConflictStrategy.MANUAL:
            if (!mergedData) throw new Error('Merged data required for manual resolution');
            return await resolveWithMergedData(conflict, mergedData, authToken);

        default:
            throw new Error(`Unknown conflict strategy: ${strategy}`);
    }
}

/**
 * Resolve by pushing local version to server (local wins)
 */
async function resolveWithLocalVersion(conflict, authToken) {
    const { entityType, entityId, payload } = conflict;
    const localVersion = payload.localVersion;

    // Push local version to server with force flag
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${authToken}`,
        'X-Force-Overwrite': 'true',
    };

    const url = `/api/${entityType}s/${entityId}`;
    const response = await fetch(url, {
        method: 'PUT',
        headers,
        body: JSON.stringify(localVersion),
    });

    if (!response.ok) {
        throw new Error(`Failed to push local version: ${response.statusText}`);
    }

    // Mark conflict as resolved
    await db.syncQueue.update(conflict.id, {
        status: 'resolved',
        payload: {
            ...conflict.payload,
            resolvedAt: new Date().toISOString(),
            resolution: ConflictStrategy.LOCAL_WINS,
        },
    });

    return { strategy: ConflictStrategy.LOCAL_WINS, success: true };
}

/**
 * Resolve by accepting server version (server wins)
 */
async function resolveWithServerVersion(conflict) {
    const { entityType, payload } = conflict;
    const serverVersion = payload.serverVersion;

    // Update local data with server version
    const table = db[`${entityType}s`];
    if (table && serverVersion) {
        await table.put(serverVersion);
    }

    // Remove from pending transactions if applicable
    if (entityType === 'transaction' && conflict.entityId) {
        await db.pendingTransactions
            .where('localId')
            .equals(conflict.entityId)
            .modify({ status: 'resolved_server_wins' });
    }

    // Mark conflict as resolved
    await db.syncQueue.update(conflict.id, {
        status: 'resolved',
        payload: {
            ...conflict.payload,
            resolvedAt: new Date().toISOString(),
            resolution: ConflictStrategy.SERVER_WINS,
        },
    });

    return { strategy: ConflictStrategy.SERVER_WINS, success: true };
}

/**
 * Resolve with manually merged data
 */
async function resolveWithMergedData(conflict, mergedData, authToken) {
    const { entityType, entityId } = conflict;

    // Push merged data to server
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${authToken}`,
        'X-Force-Overwrite': 'true',
    };

    const url = `/api/${entityType}s/${entityId}`;
    const response = await fetch(url, {
        method: 'PUT',
        headers,
        body: JSON.stringify(mergedData),
    });

    if (!response.ok) {
        throw new Error(`Failed to push merged data: ${response.statusText}`);
    }

    // Update local store
    const table = db[`${entityType}s`];
    if (table) {
        await table.put(mergedData);
    }

    // Mark conflict as resolved
    await db.syncQueue.update(conflict.id, {
        status: 'resolved',
        payload: {
            ...conflict.payload,
            resolvedAt: new Date().toISOString(),
            resolution: ConflictStrategy.MANUAL,
            mergedData,
        },
    });

    return { strategy: ConflictStrategy.MANUAL, success: true };
}

/**
 * Compare two versions of data and find differences
 */
export function diffVersions(localVersion, serverVersion) {
    const differences = [];
    const allKeys = new Set([
        ...Object.keys(localVersion || {}),
        ...Object.keys(serverVersion || {}),
    ]);

    for (const key of allKeys) {
        if (key.startsWith('_')) continue; // Skip internal keys

        const localVal = localVersion?.[key];
        const serverVal = serverVersion?.[key];

        if (JSON.stringify(localVal) !== JSON.stringify(serverVal)) {
            differences.push({
                field: key,
                local: localVal,
                server: serverVal,
            });
        }
    }

    return differences;
}

/**
 * Get conflict resolution audit trail
 */
export async function getConflictHistory() {
    return await db.syncQueue
        .where('status')
        .equals('resolved')
        .filter((item) => item.type === 'CONFLICT')
        .toArray();
}
