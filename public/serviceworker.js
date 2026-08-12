var staticCacheName = "alami-admin-pwa-v4";
var runtimeCacheName = "alami-admin-pwa-runtime-v2";
var offlineUrl = '/offline';
var filesToCache = [
    offlineUrl,
    '/img/logo.png',
];

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(staticCacheName)
            .then(function (cache) { return cache.addAll(filesToCache); })
            .then(function () { return self.skipWaiting(); })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (cacheNames) {
            return Promise.all(
                cacheNames
                    .filter(function (cacheName) {
                        return cacheName.indexOf('pwa-') === 0
                            || cacheName.indexOf('alami-admin-pwa-') === 0;
                    })
                    .filter(function (cacheName) {
                        return cacheName !== staticCacheName && cacheName !== runtimeCacheName;
                    })
                    .map(function (cacheName) { return caches.delete(cacheName); })
            );
        }).then(function () { return self.clients.claim(); })
    );
});

function isExcludedPath(pathname) {
    return /(^|\/)(laporan|export|pdf|download|import)(\/|$)/i.test(pathname)
        || /^\/(logout|login|register|password|email|verification)(\/|$)/i.test(pathname)
        || /^\/pembelian\/[^/]+\/(publish|destroy)$/.test(pathname)
        || /^\/request-orders\/[^/]+\/verify$/.test(pathname);
}

function isSameOrigin(request) {
    return new URL(request.url).origin === self.location.origin;
}

function isStaticAsset(request) {
    return ['script', 'style', 'font', 'image'].indexOf(request.destination) !== -1;
}

function networkFirst(request, fallbackUrl) {
    return fetch(request)
        .then(function (response) {
            if (response.ok) {
                var copy = response.clone();
                caches.open(runtimeCacheName).then(function (cache) {
                    cache.put(request, copy);
                });
            }

            return response;
        })
        .catch(function () {
            return caches.match(request).then(function (response) {
                return response || caches.match(fallbackUrl);
            });
        });
}

function cacheFirstAfterNetwork(request) {
    return fetch(request)
        .then(function (response) {
            if (response.ok || response.type === 'opaque') {
                var copy = response.clone();
                caches.open(runtimeCacheName).then(function (cache) {
                    cache.put(request, copy);
                });
            }

            return response;
        })
        .catch(function () {
            return caches.match(request).then(function (response) {
                return response || new Response('', {
                    status: 503,
                    statusText: 'Offline'
                });
            });
        });
}

// Cache visited admin screens and their GET data. Export/report/download paths
// and legacy state-changing GET links deliberately bypass the service worker.
self.addEventListener('fetch', function (event) {
    var request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    var url = new URL(request.url);

    if (isStaticAsset(request)) {
        event.respondWith(cacheFirstAfterNetwork(request));
        return;
    }

    if (!isSameOrigin(request) || isExcludedPath(url.pathname)) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirst(request, offlineUrl));
        return;
    }

    event.respondWith(cacheFirstAfterNetwork(request));
});
