/**
 * Shillings Service Worker
 * PWA with offline-first architecture
 * Cache-first for static assets, network-first for API
 */

const CACHE_VERSION = 'v1';
const STATIC_CACHE = `shillings-static-${CACHE_VERSION}`;
const API_CACHE = `shillings-api-${CACHE_VERSION}`;
const IMAGE_CACHE = `shillings-images-${CACHE_VERSION}`;

const OFFLINE_PAGE = '/offline.html';

// Static assets to precache on install
const PRECACHE_URLS = [
    '/',
    '/admin',
    '/offline.html',
    '/manifest.json',
    '/favicon.ico',
];

// API routes that can be cached for offline
const CACHEABLE_API_ROUTES = [
    '/api/accounts',
    '/api/accounts/chart',
    '/api/contacts',
    '/api/health',
];

/**
 * Install event: precache essential assets
 */
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
            .catch((err) => {
                console.warn('[SW] Precache failed, continuing:', err.message);
                return self.skipWaiting();
            })
    );
});

/**
 * Activate event: clean old caches
 */
self.addEventListener('activate', (event) => {
    const currentCaches = [STATIC_CACHE, API_CACHE, IMAGE_CACHE];
    event.waitUntil(
        caches.keys()
            .then((cacheNames) =>
                Promise.all(
                    cacheNames
                        .filter((name) => !currentCaches.includes(name))
                        .map((name) => caches.delete(name))
                )
            )
            .then(() => self.clients.claim())
    );
});

/**
 * Fetch event: route requests to appropriate strategy
 */
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests (let them go to network, queue if offline)
    if (request.method !== 'GET') {
        event.respondWith(handleMutationRequest(request));
        return;
    }

    // API requests: network-first
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(networkFirst(request, API_CACHE));
        return;
    }

    // Image requests: cache-first
    if (request.destination === 'image' || url.pathname.startsWith('/icons/')) {
        event.respondWith(cacheFirst(request, IMAGE_CACHE));
        return;
    }

    // Static assets (JS, CSS, fonts): cache-first
    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));
        return;
    }

    // Navigation & HTML: network-first with offline fallback
    if (request.mode === 'navigate') {
        event.respondWith(networkFirstWithOfflineFallback(request));
        return;
    }

    // Default: network-first
    event.respondWith(networkFirst(request, STATIC_CACHE));
});

/**
 * Cache-first strategy: return cached version, falling back to network
 */
async function cacheFirst(request, cacheName) {
    const cached = await caches.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        return new Response('', { status: 503, statusText: 'Offline' });
    }
}

/**
 * Network-first strategy: try network, fall back to cache
 */
async function networkFirst(request, cacheName) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        const cached = await caches.match(request);
        if (cached) return cached;
        return new Response(
            JSON.stringify({ error: 'offline', message: 'You are currently offline' }),
            { status: 503, headers: { 'Content-Type': 'application/json' } }
        );
    }
}

/**
 * Network-first with offline page fallback for navigation
 */
async function networkFirstWithOfflineFallback(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        const cached = await caches.match(request);
        if (cached) return cached;

        const offlinePage = await caches.match(OFFLINE_PAGE);
        if (offlinePage) return offlinePage;

        return new Response('<h1>Offline</h1><p>Please check your connection.</p>', {
            status: 503,
            headers: { 'Content-Type': 'text/html' },
        });
    }
}

/**
 * Handle mutation (POST/PUT/DELETE) requests
 * When offline, queue them for background sync
 */
async function handleMutationRequest(request) {
    try {
        return await fetch(request);
    } catch {
        // Clone request data for background sync queue
        const body = await request.text();
        const syncData = {
            url: request.url,
            method: request.method,
            headers: Object.fromEntries(request.headers.entries()),
            body: body,
            timestamp: Date.now(),
        };

        // Store in IndexedDB via message to client
        const clients = await self.clients.matchAll();
        clients.forEach((client) => {
            client.postMessage({
                type: 'QUEUE_SYNC',
                payload: syncData,
            });
        });

        return new Response(
            JSON.stringify({
                queued: true,
                message: 'Request queued for sync when online',
                timestamp: syncData.timestamp,
            }),
            {
                status: 202,
                headers: { 'Content-Type': 'application/json' },
            }
        );
    }
}

/**
 * Background sync event
 */
self.addEventListener('sync', (event) => {
    if (event.tag === 'shillings-sync') {
        event.waitUntil(processSyncQueue());
    }
});

/**
 * Process queued sync items
 */
async function processSyncQueue() {
    const clients = await self.clients.matchAll();
    clients.forEach((client) => {
        client.postMessage({ type: 'PROCESS_SYNC_QUEUE' });
    });
}

/**
 * Push notification event
 */
self.addEventListener('push', (event) => {
    if (!event.data) return;

    const data = event.data.json();
    const options = {
        body: data.body || 'New notification from Shillings',
        icon: '/icons/icon-192x192.png',
        badge: '/icons/icon-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/admin',
            ...data,
        },
        actions: data.actions || [],
        tag: data.tag || 'shillings-notification',
        renotify: true,
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'Shillings', options)
    );
});

/**
 * Notification click event
 */
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = event.notification.data?.url || '/admin';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Focus existing window or open new one
                for (const client of clientList) {
                    if (client.url.includes('/admin') && 'focus' in client) {
                        client.navigate(url);
                        return client.focus();
                    }
                }
                return self.clients.openWindow(url);
            })
    );
});

/**
 * Message handler for client communication
 */
self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    if (event.data?.type === 'GET_VERSION') {
        event.ports[0].postMessage({ version: CACHE_VERSION });
    }
});

/**
 * Check if a path is a static asset
 */
function isStaticAsset(pathname) {
    return /\.(js|css|woff2?|ttf|eot|svg|png|jpg|jpeg|gif|webp|ico)(\?.*)?$/.test(pathname)
        || pathname.startsWith('/build/');
}
