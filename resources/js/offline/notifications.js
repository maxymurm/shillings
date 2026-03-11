/**
 * Push Notification Manager
 * Transaction reminders, sync status, balance alerts
 * Uses Web Push API with user preferences
 */

import db from './database.js';

const VAPID_PUBLIC_KEY_META = 'vapid-public-key';

/**
 * Notification types
 */
export const NotificationType = {
    SYNC_COMPLETE: 'sync_complete',
    SYNC_CONFLICT: 'sync_conflict',
    TRANSACTION_REMINDER: 'transaction_reminder',
    BALANCE_ALERT: 'balance_alert',
    SCHEDULED_TRANSACTION: 'scheduled_transaction',
};

/**
 * Check if push notifications are supported
 */
export function isPushSupported() {
    return 'serviceWorker' in navigator
        && 'PushManager' in window
        && 'Notification' in window;
}

/**
 * Request notification permission
 */
export async function requestPermission() {
    if (!isPushSupported()) return 'unsupported';

    const permission = await Notification.requestPermission();
    return permission; // 'granted', 'denied', or 'default'
}

/**
 * Subscribe to push notifications
 */
export async function subscribeToPush(vapidPublicKey) {
    if (!isPushSupported()) return null;

    const permission = await requestPermission();
    if (permission !== 'granted') return null;

    const registration = await navigator.serviceWorker.ready;

    const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
    });

    return subscription;
}

/**
 * Send subscription to server
 */
export async function registerSubscription(subscription, authToken) {
    const response = await fetch('/api/push-subscriptions', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${authToken}`,
        },
        body: JSON.stringify({
            endpoint: subscription.endpoint,
            keys: {
                p256dh: btoa(String.fromCharCode(...new Uint8Array(subscription.getKey('p256dh')))),
                auth: btoa(String.fromCharCode(...new Uint8Array(subscription.getKey('auth')))),
            },
        }),
    });

    return response.ok;
}

/**
 * Unsubscribe from push notifications
 */
export async function unsubscribeFromPush() {
    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();

    if (subscription) {
        await subscription.unsubscribe();
    }
}

/**
 * Show a local notification (not push)
 */
export async function showLocalNotification(title, body, options = {}) {
    if (Notification.permission !== 'granted') return;

    const registration = await navigator.serviceWorker.ready;
    await registration.showNotification(title, {
        body,
        icon: '/icons/icon-192x192.png',
        badge: '/icons/icon-72x72.png',
        vibrate: [100, 50, 100],
        tag: options.tag || 'shillings-local',
        data: options.data || {},
        ...options,
    });
}

/**
 * Get notification preferences
 */
export async function getNotificationPreferences() {
    const prefs = await db.notificationPrefs.toArray();
    const defaults = {
        [NotificationType.SYNC_COMPLETE]: true,
        [NotificationType.SYNC_CONFLICT]: true,
        [NotificationType.TRANSACTION_REMINDER]: true,
        [NotificationType.BALANCE_ALERT]: true,
        [NotificationType.SCHEDULED_TRANSACTION]: true,
    };

    for (const pref of prefs) {
        defaults[pref.key] = pref.value;
    }

    return defaults;
}

/**
 * Update a notification preference
 */
export async function setNotificationPreference(type, enabled) {
    await db.notificationPrefs.put({ key: type, value: enabled });
}

/**
 * Check if a notification type is enabled
 */
export async function isNotificationEnabled(type) {
    const pref = await db.notificationPrefs.get(type);
    return pref?.value !== false; // Default to true
}

/**
 * Utility: Convert VAPID base64 string to Uint8Array
 */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}
