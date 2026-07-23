/**
 * Service Worker - SyncOps Monitoring System
 * File: public/sw.js
 */

const CACHE_NAME = 'telemetry-hub-v1';
const STATIC_ASSETS = [
    '/',
    '/favicon.ico',
    '/offline.html'
];

// 1. Install Event - Cache Core Assets
self.addEventListener('install', (event) => {
    console.log('[Service Worker] Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[Service Worker] Pre-caching static assets');
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('[Service Worker] Partial pre-cache failure:', err);
            });
        })
    );
    self.skipWaiting();
});

// 2. Activate Event - Clean Up Old Caches
self.addEventListener('activate', (event) => {
    console.log('[Service Worker] Activating...');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        console.log('[Service Worker] Deleting old cache:', cache);
                        return caches.delete(cache);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// 3. Fetch Event - Network-First with Cache Fallback for Pages
self.addEventListener('fetch', (event) => {
    // Only handle GET requests
    if (event.request.method !== 'GET') return;

    // Skip unsupported schemes (e.g., chrome-extension://, moz-extension://)
    if (!event.request.url.startsWith('http://') && !event.request.url.startsWith('https://')) {
        return;
    }

    // Skip API, Livewire, Reverb, or WebSocket connections
    const url = new URL(event.request.url);
    if (url.pathname.startsWith('/api') || 
        url.pathname.startsWith('/broadcasting') || 
        url.pathname.includes('livewire')) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((networkResponse) => {
                // If valid response, update cache for static assets
                if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseToCache);
                    });
                }
                return networkResponse;
            })
            .catch(() => {
                // Fallback to cache if network fails (offline support)
                return caches.match(event.request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    if (event.request.headers.get('accept')?.includes('text/html')) {
                        return caches.match('/') || caches.match('/offline.html');
                    }
                });
            })
    );
});

// 4. Web Push Notification Event
self.addEventListener('push', (event) => {
    console.log('[Service Worker] Push Received');

    let notificationData = {
        title: 'Telemetry Alert',
        body: 'Ada pembaruan status pada sistem monitoring.',
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        url: '/',
        tag: 'telemetry-alert'
    };

    if (event.data) {
        try {
            const parsed = event.data.json();
            notificationData = { ...notificationData, ...parsed };
        } catch (e) {
            notificationData.body = event.data.text();
        }
    }

    const options = {
        body: notificationData.body,
        icon: notificationData.icon || '/favicon.ico',
        badge: notificationData.badge || '/favicon.ico',
        vibrate: [100, 50, 100],
        data: {
            url: notificationData.url || '/'
        },
        tag: notificationData.tag || 'telemetry-notification',
        renotify: true,
        actions: [
            { action: 'open', title: 'Buka Dashboard' },
            { action: 'close', title: 'Tutup' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(notificationData.title, options)
    );
});

// 5. Notification Click Event
self.addEventListener('notificationclick', (event) => {
    console.log('[Service Worker] Notification click received.', event.notification.tag);
    event.notification.close();

    if (event.action === 'close') return;

    const targetUrl = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            // Check if there is already a window open with target URL
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                if (client.url.includes(targetUrl) && 'focus' in client) {
                    return client.focus();
                }
            }
            // If no window open, open new window
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

// 6. Message Event (Client <-> SW Communication)
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
