var staticCacheName = "alami-admin-pwa-v2";
var offlineUrl = '/offline';
var filesToCache = [
    offlineUrl,
    '/img/logo.png',
];

// Cache on install
self.addEventListener("install", event => {
    event.waitUntil(
        caches.open(staticCacheName)
            .then(cache => {
                return cache.addAll(filesToCache);
            })
            .then(() => self.skipWaiting())
    )
});

// Clear cache on activate
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(cacheName => (cacheName.startsWith("pwa-") || cacheName.startsWith("alami-admin-pwa-")))
                    .filter(cacheName => (cacheName !== staticCacheName))
                    .map(cacheName => caches.delete(cacheName))
            );
        })
            .then(() => self.clients.claim())
    );
});

// Serve from Cache
self.addEventListener("fetch", event => {
    if (event.request.method !== 'GET' || new URL(event.request.url).origin !== self.location.origin) {
        return;
    }

    if (event.request.mode !== 'navigate') {
        return;
    }

    event.respondWith(fetch(event.request).catch(() => caches.match(offlineUrl)));
});
