var staticCacheName = 'alami-admin-pwa-static-v5';
var userCachePrefix = 'alami-admin-pwa-user-';
var offlineUrl = '/offline';
var filesToCache = [
    offlineUrl,
    '/img/logo.png'
];
var activeUserCacheName = null;

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
            return Promise.all(cacheNames
                .filter(function (cacheName) {
                    return cacheName.indexOf('pwa-') === 0
                        || cacheName.indexOf('alami-admin-pwa-') === 0;
                })
                .filter(function (cacheName) {
                    return cacheName !== staticCacheName;
                })
                .map(function (cacheName) { return caches.delete(cacheName); }));
        }).then(function () {
            return self.clients.claim();
        })
    );
});

self.addEventListener('message', function (event) {
    var data = event.data || {};

    if (data.type === 'set-user-cache' && validUserKey(data.key)) {
        activeUserCacheName = userCachePrefix + data.key;
        return;
    }

    if (data.type === 'clear-user-cache' && validCacheName(data.cacheName)) {
        if (activeUserCacheName === data.cacheName) {
            activeUserCacheName = null;
        }

        event.waitUntil(caches.delete(data.cacheName));
    }
});

function validUserKey(key) {
    return typeof key === 'string' && /^[a-f0-9]{64}$/.test(key);
}

function validCacheName(cacheName) {
    return typeof cacheName === 'string'
        && cacheName.indexOf(userCachePrefix) === 0
        && validUserKey(cacheName.slice(userCachePrefix.length));
}

function cookieUserKey(request) {
    var cookie = request.headers.get('cookie') || '';
    var match = cookie.match(/(?:^|;\s*)alami_pwa_user=([^;]+)/);

    if (!match) {
        return null;
    }

    try {
        var key = decodeURIComponent(match[1]);
        return validUserKey(key) ? key : null;
    } catch (error) {
        return null;
    }
}

function userCacheName(request) {
    if (activeUserCacheName) {
        return activeUserCacheName;
    }

    var key = cookieUserKey(request);
    return key ? userCachePrefix + key : null;
}

function isExcludedPath(pathname) {
    return /(^|\/)(laporan|export|pdf|download|import)(\/|$)/i.test(pathname)
        || /^\/offline\/csrf-token(?:\/|$)/.test(pathname)
        || /^\/(api|sanctum)(\/|$)/.test(pathname)
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

function cacheResponse(cacheName, request, response) {
    if (!response || (!response.ok && response.type !== 'opaque')) {
        return;
    }

    return caches.open(cacheName).then(function (cache) {
        return cache.put(request, response.clone());
    });
}

function matchUserCache(cacheName, request) {
    if (!cacheName) {
        return Promise.resolve(null);
    }

    return caches.open(cacheName).then(function (cache) {
        return cache.match(request);
    });
}

function networkFirst(request, cacheName) {
    return fetch(request)
        .then(function (response) {
            if (cacheName && response.ok) {
                cacheResponse(cacheName, request, response);
            }

            return response;
        })
        .catch(function () {
            return matchUserCache(cacheName, request).then(function (response) {
                return response || caches.match(offlineUrl);
            });
        });
}

function cacheFirstAfterNetwork(request, cacheName) {
    return fetch(request)
        .then(function (response) {
            if (cacheName) {
                cacheResponse(cacheName, request, response);
            }

            return response;
        })
        .catch(function () {
            return matchUserCache(cacheName, request).then(function (response) {
                return response || new Response('', {
                    status: 503,
                    statusText: 'Offline'
                });
            });
        });
}

self.addEventListener('fetch', function (event) {
    var request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    var url = new URL(request.url);

    if (isStaticAsset(request)) {
        event.respondWith(cacheFirstAfterNetwork(request, staticCacheName));
        return;
    }

    if (!isSameOrigin(request) || isExcludedPath(url.pathname)) {
        return;
    }

    // Authenticated HTML and JSON are cached only in the current user's
    // namespace. Without a namespace we fail closed and never serve another
    // account's sidebar or data.
    var cacheName = userCacheName(request);

    if (request.mode === 'navigate') {
        event.respondWith(networkFirst(request, cacheName));
        return;
    }

    event.respondWith(cacheFirstAfterNetwork(request, cacheName));
});
