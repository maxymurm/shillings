/**
 * Shillings PWA - Main Entry Point
 * Service worker registration, online/offline events, PWA install prompt
 */

import { fullSync, onSyncStatusChange, getSyncStats } from './offline/sync.js';
import { getPendingTransactions } from './offline/transactions.js';
import { getUnresolvedConflicts } from './offline/conflicts.js';
import { showLocalNotification, isNotificationEnabled, NotificationType } from './offline/notifications.js';

/**
 * Register the service worker
 */
export async function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        console.warn('[PWA] Service workers not supported');
        return null;
    }

    try {
        const registration = await navigator.serviceWorker.register('/sw.js', {
            scope: '/',
        });

        // Handle updates
        registration.addEventListener('updatefound', () => {
            const newWorker = registration.installing;
            newWorker.addEventListener('statechange', () => {
                if (newWorker.state === 'activated') {
                    // New service worker activated - notify user
                    dispatchPwaEvent('sw-updated');
                }
            });
        });

        console.info('[PWA] Service worker registered');
        return registration;
    } catch (error) {
        console.error('[PWA] Service worker registration failed:', error);
        return null;
    }
}

/**
 * Initialize PWA features
 */
export async function initPwa() {
    // Register service worker
    await registerServiceWorker();

    // Listen for online/offline events
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    // Listen for messages from service worker
    navigator.serviceWorker?.addEventListener('message', handleSwMessage);

    // Handle PWA install prompt
    setupInstallPrompt();

    // Update UI with current connection status
    updateConnectionStatus(navigator.onLine);

    // Set up sync status listener
    onSyncStatusChange(handleSyncStatusChange);

    // Initial sync stats
    await updateSyncBadge();
}

/**
 * Handle coming back online
 */
async function handleOnline() {
    updateConnectionStatus(true);
    dispatchPwaEvent('online');

    // Show notification
    const enabled = await isNotificationEnabled(NotificationType.SYNC_COMPLETE);
    if (enabled) {
        showLocalNotification('Back Online', 'Syncing your offline changes...');
    }

    // Auto-sync pending changes
    const authToken = getAuthToken();
    if (authToken) {
        const result = await fullSync(authToken);
        if (result.status === 'success') {
            const stats = result.uploaded;
            if (stats.processed > 0) {
                showLocalNotification(
                    'Sync Complete',
                    `${stats.processed} change(s) synced successfully`
                );
            }
        }
    }
}

/**
 * Handle going offline
 */
function handleOffline() {
    updateConnectionStatus(false);
    dispatchPwaEvent('offline');
}

/**
 * Update the connection status indicator in the DOM
 */
function updateConnectionStatus(isOnline) {
    // Dispatch event for Filament/Alpine components to listen to
    document.dispatchEvent(
        new CustomEvent('pwa:connection-change', {
            detail: { online: isOnline },
        })
    );

    // Update meta theme for offline indication
    const metaTheme = document.querySelector('meta[name="theme-color"]');
    if (metaTheme) {
        metaTheme.content = isOnline ? '#10b981' : '#ef4444';
    }
}

/**
 * Handle messages from the service worker
 */
function handleSwMessage(event) {
    const { type, payload } = event.data || {};

    switch (type) {
        case 'QUEUE_SYNC':
            // Service worker queued an offline mutation
            import('./offline/database.js').then(({ default: db }) => {
                db.syncQueue.add({
                    type: payload.method === 'POST' ? 'CREATE' : 'UPDATE',
                    entityType: 'transaction',
                    entityId: `sw_${payload.timestamp}`,
                    status: 'pending',
                    timestamp: payload.timestamp,
                    payload: {
                        url: payload.url,
                        method: payload.method,
                        body: payload.body,
                        headers: payload.headers,
                    },
                    attempts: 0,
                });
                updateSyncBadge();
            });
            break;

        case 'PROCESS_SYNC_QUEUE':
            const authToken = getAuthToken();
            if (authToken) {
                import('./offline/sync.js').then(({ processSyncQueue }) => {
                    processSyncQueue(authToken);
                });
            }
            break;
    }
}

/**
 * Handle sync status changes
 */
function handleSyncStatusChange(status) {
    dispatchPwaEvent('sync-status', status);
    updateSyncBadge();
}

/**
 * Update the pending sync badge count
 */
async function updateSyncBadge() {
    try {
        const pending = await getPendingTransactions();
        const conflicts = await getUnresolvedConflicts();
        const stats = await getSyncStats();

        dispatchPwaEvent('sync-badge', {
            pending: stats.pending,
            conflicts: conflicts.length,
            pendingTransactions: pending.length,
        });
    } catch {
        // Silently fail if DB not ready
    }
}

/**
 * Setup PWA install prompt
 */
let deferredInstallPrompt = null;

function setupInstallPrompt() {
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredInstallPrompt = e;
        dispatchPwaEvent('install-available');
    });

    window.addEventListener('appinstalled', () => {
        deferredInstallPrompt = null;
        dispatchPwaEvent('install-complete');
    });
}

/**
 * Trigger the PWA install prompt
 */
export async function promptInstall() {
    if (!deferredInstallPrompt) return false;

    deferredInstallPrompt.prompt();
    const { outcome } = await deferredInstallPrompt.userChoice;
    deferredInstallPrompt = null;

    return outcome === 'accepted';
}

/**
 * Check if the app is in standalone (installed) mode
 */
export function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
}

/**
 * Get the auth token from the page (Sanctum/session-based)
 */
function getAuthToken() {
    // Check meta tag first
    const meta = document.querySelector('meta[name="api-token"]');
    if (meta?.content) return meta.content;

    // Check localStorage
    return localStorage.getItem('shillings_api_token');
}

/**
 * Dispatch a custom PWA event
 */
function dispatchPwaEvent(name, detail = {}) {
    document.dispatchEvent(new CustomEvent(`pwa:${name}`, { detail }));
}
