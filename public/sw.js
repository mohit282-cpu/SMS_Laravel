/**
 * Progressive Web App Service Worker
 * Application: Laravel School Management System (Nepal Edition)
 * Version: 1.1.0
 *
 * Security & Cache Architecture:
 * 1. Strict Cache Allowlist: Only verified immutable static assets (CSS, JS, fonts, icons) are cached.
 * 2. Zero Private HTML Caching: Authenticated HTML responses, student records, exam marks,
 *    attendance data, fee payments, and CSRF tokens are NEVER written to CacheStorage.
 * 3. Non-GET Bypass: All state-changing requests (POST, PUT, PATCH, DELETE) bypass SW completely.
 * 4. Response Validation: 3xx redirects, 4xx/5xx errors, opaque responses, and responses
 *    bearing 'Cache-Control: no-store' are strictly rejected from CacheStorage.
 * 5. Query String Isolation: URLs with dynamic or unknown query parameters are not cached.
 * 6. Scoped Activation: Cache eviction during activation is strictly scoped to 'lav-sms-*',
 *    preventing accidental deletion of third-party or sibling origin caches.
 * 7. Safe Update Lifecycle: The worker does NOT skipWaiting on install; it waits until the client
 *    confirms that no active form submissions or transactions are in-flight.
 */

const CACHE_VERSION = 'lav-sms-v1.1.1';
const CACHE_PREFIX = 'lav-sms-';
const PRECACHE_NAME = `${CACHE_PREFIX}precache-${CACHE_VERSION}`;
const RUNTIME_STATIC_CACHE = `${CACHE_PREFIX}static-${CACHE_VERSION}`;
const EXPECTED_CACHES = [PRECACHE_NAME, RUNTIME_STATIC_CACHE];

// Pre-cached core static assets and offline fallback shell
const PRECACHE_URLS = [
    './offline',
    './assets/css/bootstrap.min.css',
    './assets/css/bootstrap_limitless.min.css',
    './assets/css/layout.min.css',
    './assets/css/components.min.css',
    './assets/css/colors.min.css',
    './assets/css/qs.css',
    './assets/css/nepali_datepicker.css',
    './assets/css/pwa.css',
    './global_assets/css/icons/icomoon/styles.css',
    './global_assets/js/main/jquery.min.js',
    './global_assets/js/main/bootstrap.bundle.min.js',
    './assets/js/pwa.js',
    './global_assets/images/logo.png',
    './global_assets/images/favicon.png',
    './assets/images/icons/icon-72x72.png',
    './assets/images/icons/icon-192x192.png',
    './assets/images/icons/icon-512x512.png',
    './assets/images/icons/icon-maskable-192x192.png',
    './assets/images/icons/icon-maskable-512x512.png'
];

// Explicit Deny-List for Sensitive Application Endpoints
const SENSITIVE_PATH_PATTERNS = [
    /\/login/i,
    /\/logout/i,
    /\/register/i,
    /\/password/i,
    /\/dashboard/i,
    /\/my_account/i,
    /\/students/i,
    /\/users/i,
    /\/marks/i,
    /\/exams/i,
    /\/grades/i,
    /\/payments/i,
    /\/receipts/i,
    /\/invoices/i,
    /\/fees/i,
    /\/attendance/i,
    /\/timetables/i,
    /\/pins/i,
    /\/books/i,
    /\/pwa\/push/i,
    /\/ajax/i,
    /\/api\//i
];

/**
 * 1. Install Event: Pre-cache essential assets & offline shell
 * Safe recovery: Uses Promise.allSettled so a single missing asset does not abort install.
 * Safe update: Does NOT call skipWaiting() prematurely.
 */
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(PRECACHE_NAME)
            .then((cache) => {
                return Promise.allSettled(
                    PRECACHE_URLS.map((url) => {
                        return cache.add(new Request(url, { cache: 'reload' })).catch((err) => {
                            console.warn(`[PWA SW] Pre-cache skipped for: ${url}`, err);
                        });
                    })
                );
            })
    );
});

/**
 * 2. Activate Event: Clean up outdated caches owned strictly by this application
 */
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    // Only delete caches starting with 'lav-sms-' that are not in EXPECTED_CACHES
                    if (cacheName.startsWith(CACHE_PREFIX) && !EXPECTED_CACHES.includes(cacheName)) {
                        console.log(`[PWA SW] Deleting obsolete application cache: ${cacheName}`);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

/**
 * Helper: Check if response can safely be stored in CacheStorage
 */
function isSafeToCache(response) {
    if (!response || response.status !== 200) {
        return false;
    }
    // Reject opaque and redirected responses
    if (response.type === 'opaque' || response.redirected) {
        return false;
    }
    // Reject private/no-store responses
    const cacheControl = response.headers.get('cache-control');
    if (cacheControl && (cacheControl.includes('no-store') || cacheControl.includes('private'))) {
        return false;
    }
    return true;
}

/**
 * Helper: Check if URL represents an approved static resource
 */
function isApprovedStaticAsset(url) {
    const isStaticExt = /\.(css|js|woff2?|ttf|eot|svg|png|jpg|jpeg|gif|ico|webp)$/i.test(url.pathname);
    if (!isStaticExt) {
        return false;
    }

    // Must be in approved static directories on same origin OR approved font CDN
    const isSameOrigin = url.origin === self.location.origin;
    const isApprovedPath = isSameOrigin && (
        url.pathname.includes('/assets/') ||
        url.pathname.includes('/global_assets/') ||
        url.pathname.endsWith('/manifest.json') ||
        url.pathname.endsWith('/favicon.ico') ||
        url.pathname.endsWith('/apple-touch-icon.png')
    );

    const isFontCdn = url.origin === 'https://fonts.googleapis.com' ||
        url.origin === 'https://fonts.gstatic.com';

    if (!isApprovedPath && !isFontCdn) {
        return false;
    }

    // Query parameter policy: Reject dynamic queries to prevent caching user-scoped content
    // Only allow clean URLs or explicit versioning like ?v=1.0.0
    if (url.search) {
        const isCleanVersionParam = /^\?v=[a-zA-Z0-9._-]+$/.test(url.search);
        if (!isCleanVersionParam && !isFontCdn) {
            return false;
        }
    }

    return true;
}

/**
 * 3. Fetch Event: Conservative, Secure Routing & Caching Strategies
 */
self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Rule 1: Never intercept non-GET requests (POST, PUT, PATCH, DELETE)
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Rule 2: Ignore browser extensions, chrome-extension, and non-http protocols
    if (url.protocol !== 'http:' && url.protocol !== 'https:') {
        return;
    }

    // Rule 3: Deny-list check for sensitive/authenticated routes
    const isSensitive = SENSITIVE_PATH_PATTERNS.some((pattern) => pattern.test(url.pathname));
    const isNavigation = request.mode === 'navigate' ||
        (request.headers.get('accept') && request.headers.get('accept').includes('text/html'));

    if (isSensitive) {
        if (isNavigation) {
            // Strict Network-Only with branded offline fallback for HTML navigation
            event.respondWith(
                fetch(request).catch(async () => {
                    const cache = await caches.open(PRECACHE_NAME);
                    const offlineFallback = await cache.match('./offline') || await cache.match('/offline');
                    if (offlineFallback) {
                        return offlineFallback;
                    }
                    return new Response(
                        '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Offline - School Portal</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h2>Offline Mode</h2><p>You are currently offline. School records and live actions require an active connection.</p><button onclick="window.location.reload()">Retry Connection</button></body></html>',
                        { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
                    );
                })
            );
        } else {
            // Network-Only for private API/AJAX requests — NEVER return cached private data
            event.respondWith(
                fetch(request).catch(() => {
                    return new Response(
                        JSON.stringify({ error: 'Network unavailable. Live server connection required.' }),
                        { status: 503, statusText: 'Service Unavailable Offline', headers: { 'Content-Type': 'application/json' } }
                    );
                })
            );
        }
        return;
    }

    // Rule 4: Generic HTML Navigation Requests (Network-Only with Offline Fallback)
    // CRITICAL: We NEVER cache HTML pages in CacheStorage
    if (isNavigation) {
        event.respondWith(
            fetch(request).catch(async () => {
                const cache = await caches.open(PRECACHE_NAME);
                const offlineFallback = await cache.match('./offline') || await cache.match('/offline');
                if (offlineFallback) {
                    return offlineFallback;
                }
                return new Response(
                    '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Offline - School Portal</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h2>Offline Mode</h2><p>You are currently offline. Please check your internet connection.</p><button onclick="window.location.reload()">Retry Connection</button></body></html>',
                    { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
                );
            })
        );
        return;
    }

    // Rule 5: Approved Static Assets Only (CSS, JS, Fonts, Images)
    if (isApprovedStaticAsset(url)) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    // Return cached asset immediately, revalidate in background when online
                    if (navigator.onLine) {
                        fetch(request).then((networkResponse) => {
                            if (isSafeToCache(networkResponse)) {
                                caches.open(RUNTIME_STATIC_CACHE).then((cache) => {
                                    cache.put(request, networkResponse.clone());
                                });
                            }
                        }).catch(() => { /* Background revalidation failed gracefully */ });
                    }
                    return cachedResponse;
                }

                // Not in cache: fetch from network and store if eligible
                return fetch(request).then((networkResponse) => {
                    if (isSafeToCache(networkResponse)) {
                        const responseToCache = networkResponse.clone();
                        caches.open(RUNTIME_STATIC_CACHE).then((cache) => {
                            cache.put(request, responseToCache);
                        });
                    }
                    return networkResponse;
                }).catch(() => {
                    return new Response('', { status: 408, statusText: 'Asset Unavailable Offline' });
                });
            })
        );
        return;
    }

    // Rule 6: Default fallback — Pass through to network without caching
    event.respondWith(fetch(request));
});

/**
 * 4. Message Event: Safe update activation command from client
 */
self.addEventListener('message', (event) => {
    if (event.data && event.data.action === 'SKIP_WAITING') {
        console.log('[PWA SW] Safe update approved: activating new service worker...');
        self.skipWaiting();
    }
});

/**
 * 5. Push Notification Event (Privacy-Safe & Sanitized)
 */
self.addEventListener('push', (event) => {
    let data = {
        title: 'School Notice',
        body: 'You have a new update in the School Portal.',
        icon: './assets/images/icons/icon-192x192.png',
        badge: './assets/images/icons/icon-72x72.png',
        url: './dashboard'
    };

    if (event.data) {
        try {
            const parsed = event.data.json();
            if (parsed && typeof parsed === 'object') {
                if (parsed.title) data.title = String(parsed.title).replace(/<[^>]*>?/gm, '');
                if (parsed.body) data.body = String(parsed.body).replace(/<[^>]*>?/gm, '');
                if (parsed.url) data.url = parsed.url;
            }
        } catch (e) {
            const text = event.data.text();
            if (text) {
                data.body = String(text).replace(/<[^>]*>?/gm, '');
            }
        }
    }

    const options = {
        body: data.body,
        icon: data.icon,
        badge: data.badge,
        data: { url: data.url },
        vibrate: [100, 50, 100],
        tag: 'sms-notice',
        renotify: true,
        actions: [
            { action: 'open_url', title: 'Open Portal' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

/**
 * 6. Notification Click Event (Strict Same-Origin Target Check)
 */
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    let targetUrl = './dashboard';
    if (event.notification.data && event.notification.data.url) {
        try {
            const raw = String(event.notification.data.url).trim();
            // Reject control characters, newlines, backslashes, protocol-relative prefixes, and dangerous schemes
            if (raw &&
                !/[\x00-\x1F\x7F\r\n\\]/.test(raw) &&
                !raw.startsWith('//') &&
                !raw.startsWith('/\\') &&
                !raw.startsWith('\\\\') &&
                !/^(javascript|data|vbscript|file|about|blob):/i.test(raw)
            ) {
                const parsed = new URL(raw, self.location.origin);
                if (parsed.origin === self.location.origin && (parsed.protocol === 'http:' || parsed.protocol === 'https:')) {
                    targetUrl = parsed.pathname + parsed.search;
                }
            }
        } catch (e) {
            targetUrl = './dashboard';
        }
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                try {
                    const clientUrl = new URL(client.url);
                    const targetParsed = new URL(targetUrl, self.location.origin);
                    if (clientUrl.pathname === targetParsed.pathname && 'focus' in client) {
                        return client.focus();
                    }
                } catch (e) {
                    // Ignore URL parsing errors for unmatched clients
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
