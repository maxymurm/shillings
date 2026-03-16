import { Network } from '@capacitor/network';
import { App } from '@capacitor/app';
import api from '@/services/api';
import { db, type SyncQueueEntry } from '@/offline/database';

let isSyncing = false;
let syncCallbacks: Array<(result: SyncResult) => void> = [];

export interface SyncResult {
  uploaded: number;
  downloaded: number;
  conflicts: number;
  errors: string[];
}

export async function isOnline(): Promise<boolean> {
  const status = await Network.getStatus();
  return status.connected;
}

export async function syncAll(): Promise<SyncResult> {
  if (isSyncing) return { uploaded: 0, downloaded: 0, conflicts: 0, errors: [] };
  isSyncing = true;

  const result: SyncResult = { uploaded: 0, downloaded: 0, conflicts: 0, errors: [] };

  try {
    if (!(await isOnline())) {
      result.errors.push('No network connection');
      return result;
    }

    // Upload pending changes
    const pending = await db.syncQueue.where('status').equals('pending').toArray();
    if (pending.length > 0) {
      const batchPayload = pending.map((entry) => ({
        entity_type: entry.entity_type,
        entity_id: entry.entity_id,
        action: entry.action,
        data: JSON.parse(entry.payload),
      }));

      try {
        const response = await api.post('/sync/batch', { changes: batchPayload });
        const syncResponse = response.data;

        // Mark successful items
        for (const entry of pending) {
          await db.syncQueue.delete(entry.id!);
        }
        result.uploaded = pending.length;

        if (syncResponse.conflicts?.length) {
          result.conflicts = syncResponse.conflicts.length;
        }
      } catch (error: any) {
        // Mark failed and increment retries
        for (const entry of pending) {
          await db.syncQueue.update(entry.id!, {
            status: entry.retries >= 3 ? 'failed' : 'pending',
            retries: entry.retries + 1,
          });
        }
        result.errors.push(error.message);
      }
    }

    // Download server changes
    try {
      const lastSync = (await db.deviceInfo.get('last_sync'))?.value || '1970-01-01T00:00:00Z';
      const response = await api.get('/sync/changes', { params: { since: lastSync } });
      const changes = response.data;

      if (changes.accounts?.length) {
        await db.accounts.bulkPut(changes.accounts);
        result.downloaded += changes.accounts.length;
      }
      if (changes.transactions?.length) {
        await db.transactions.bulkPut(changes.transactions);
        result.downloaded += changes.transactions.length;
      }
      if (changes.splits?.length) {
        await db.splits.bulkPut(changes.splits);
        result.downloaded += changes.splits.length;
      }
      if (changes.contacts?.length) {
        await db.contacts.bulkPut(changes.contacts);
        result.downloaded += changes.contacts.length;
      }

      await db.deviceInfo.put({ key: 'last_sync', value: new Date().toISOString() });
    } catch (error: any) {
      result.errors.push('Download failed: ' + error.message);
    }
  } finally {
    isSyncing = false;
    syncCallbacks.forEach((cb) => cb(result));
  }

  return result;
}

export async function queueOfflineChange(
  entityType: string,
  entityId: number | null,
  action: 'create' | 'update' | 'delete',
  payload: Record<string, any>,
): Promise<void> {
  await db.syncQueue.add({
    entity_type: entityType,
    entity_id: entityId,
    action,
    payload: JSON.stringify(payload),
    created_at: new Date().toISOString(),
    retries: 0,
    status: 'pending',
  });

  // Try immediate sync if online
  if (await isOnline()) {
    syncAll();
  }
}

export async function getPendingCount(): Promise<number> {
  return db.syncQueue.where('status').equals('pending').count();
}

export function onSyncComplete(callback: (result: SyncResult) => void): () => void {
  syncCallbacks.push(callback);
  return () => {
    syncCallbacks = syncCallbacks.filter((cb) => cb !== callback);
  };
}

export function setupAutoSync(): void {
  // Sync when network comes back
  Network.addListener('networkStatusChange', async (status) => {
    if (status.connected) {
      await syncAll();
    }
  });

  // Sync when app comes to foreground
  App.addListener('appStateChange', async ({ isActive }) => {
    if (isActive && (await isOnline())) {
      await syncAll();
    }
  });
}
