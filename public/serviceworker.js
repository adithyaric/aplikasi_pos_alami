var staticCacheName = 'alami-admin-pwa-static-v5';
var userCachePrefix = 'alami-admin-pwa-user-';
var offlineUrl = '/offline';
var filesToCache = [
    offlineUrl,
    '/img/logo.png'
];
var stateDbName = 'alami-admin-pwa-state';
var stateDbVersion = 1;
var stateDbStore = 'state';
var stateDbKey = 'active-user-cache';
var activeUserCacheName = null;
var activeUserCachePromise = null;

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
                    // Remove the package's old shared caches. User caches are
                    // intentionally retained across worker updates so that a
                    // freshly activated worker can still serve the logged-in
                    // user's pages while offline.
                    return cacheName.indexOf('pwa-') === 0
                        || (cacheName.indexOf('alami-admin-pwa-') === 0
                            && cacheName.indexOf(userCachePrefix) !== 0);
                })
                .filter(function (cacheName) {
                    return cacheName !== staticCacheName;
                })
                .map(function (cacheName) { return caches.delete(cacheName); }));
        }).then(function () {
            return readPersistedUserCache().then(function (cacheName) {
                activeUserCacheName = cacheName;
                activeUserCachePromise = Promise.resolve(cacheName);
            }).catch(function () {
                activeUserCacheName = null;
                activeUserCachePromise = Promise.resolve(null);
            });
        }).then(function () {
            return self.clients.claim();
        })
    );
});

self.addEventListener('message', function (event) {
    var data = event.data || {};

    if (data.type === 'set-user-cache' && validUserKey(data.key)) {
        var cacheName = userCachePrefix + data.key;
        activeUserCacheName = cacheName;
        activeUserCachePromise = Promise.resolve(cacheName);

        var persistPromise = persistUserCache(cacheName).catch(function () {
            // Cache selection still works for this worker lifetime if browser
            // storage is unavailable.
        });

        event.waitUntil(persistPromise);

        if (Array.isArray(data.warmUrls)) {
            event.waitUntil(persistPromise.then(function () {
                return warmUserPages(data.warmUrls, cacheName);
            }));
        }

        return;
    }

    if (data.type === 'clear-user-cache' && validCacheName(data.cacheName)) {
        if (activeUserCacheName === data.cacheName) {
            activeUserCacheName = null;
            activeUserCachePromise = Promise.resolve(null);
        }

        event.waitUntil(Promise.all([
            caches.delete(data.cacheName),
            clearPersistedUserCache(data.cacheName)
        ]));
        return;
    }

    if (data.type === 'warm-static' && Array.isArray(data.urls)) {
        event.waitUntil(warmStaticAssets(data.urls));
    }
});

function openStateDb() {
    if (!self.indexedDB) {
        return Promise.reject(new Error('IndexedDB is unavailable'));
    }

    return new Promise(function (resolve, reject) {
        var request = indexedDB.open(stateDbName, stateDbVersion);

        request.onupgradeneeded = function () {
            if (!request.result.objectStoreNames.contains(stateDbStore)) {
                request.result.createObjectStore(stateDbStore, { keyPath: 'key' });
            }
        };
        request.onsuccess = function () { resolve(request.result); };
        request.onerror = function () { reject(request.error || new Error('Unable to open state database')); };
    });
}

function readPersistedUserCache() {
    return openStateDb().then(function (database) {
        return new Promise(function (resolve, reject) {
            var transaction = database.transaction(stateDbStore, 'readonly');
            var request = transaction.objectStore(stateDbStore).get(stateDbKey);

            request.onsuccess = function () {
                var record = request.result;
                resolve(record && validCacheName(record.cacheName) ? record.cacheName : null);
            };
            request.onerror = function () { reject(request.error || new Error('Unable to read state')); };
            transaction.oncomplete = function () { database.close(); };
            transaction.onerror = function () { database.close(); };
        });
    });
}

function persistUserCache(cacheName) {
    return openStateDb().then(function (database) {
        return new Promise(function (resolve, reject) {
            var transaction = database.transaction(stateDbStore, 'readwrite');
            transaction.objectStore(stateDbStore).put({ key: stateDbKey, cacheName: cacheName });
            transaction.oncomplete = function () {
                database.close();
                resolve();
            };
            transaction.onerror = function () {
                database.close();
                reject(transaction.error || new Error('Unable to save state'));
            };
        });
    });
}

function clearPersistedUserCache(expectedCacheName) {
    return openStateDb().then(function (database) {
        return new Promise(function (resolve, reject) {
            var transaction = database.transaction(stateDbStore, 'readwrite');
            var store = transaction.objectStore(stateDbStore);
            var request = store.get(stateDbKey);

            request.onsuccess = function () {
                if (request.result && request.result.cacheName === expectedCacheName) {
                    store.delete(stateDbKey);
                }
            };
            request.onerror = function () { reject(request.error || new Error('Unable to read state')); };
            transaction.oncomplete = function () {
                database.close();
                resolve();
            };
            transaction.onerror = function () {
                database.close();
                reject(transaction.error || new Error('Unable to clear state'));
            };
        });
    });
}

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
        return Promise.resolve(activeUserCacheName);
    }

    var key = cookieUserKey(request);
    if (key) {
        activeUserCacheName = userCachePrefix + key;
        activeUserCachePromise = Promise.resolve(activeUserCacheName);
        return activeUserCachePromise;
    }

    if (!activeUserCachePromise) {
        activeUserCachePromise = readPersistedUserCache()
            .then(function (cacheName) {
                activeUserCacheName = cacheName;
                return cacheName;
            })
            .catch(function () {
                activeUserCacheName = null;
                return null;
            });
    }

    return activeUserCachePromise;
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

function validWarmUrl(value) {
    try {
        var url = new URL(value, self.location.origin);
        return url.origin === self.location.origin
            && !isExcludedPath(url.pathname)
            && url.pathname !== offlineUrl;
    } catch (error) {
        return false;
    }
}

function warmUserPages(urls, cacheName) {
    var uniqueUrls = [];

    urls.forEach(function (value) {
        if (!validWarmUrl(value)) {
            return;
        }

        var url = new URL(value, self.location.origin).href;
        if (uniqueUrls.indexOf(url) === -1) {
            uniqueUrls.push(url);
        }
    });

    return Promise.all(uniqueUrls.map(function (url) {
        var request = new Request(url, {
            credentials: 'include',
            cache: 'no-store'
        });

        return fetch(request).then(function (response) {
            return response.ok ? cacheResponse(cacheName, request, response) : null;
        }).catch(function () {
            return null;
        });
    }));
}

function warmStaticAssets(urls) {
    var uniqueUrls = [];

    urls.forEach(function (value) {
        try {
            var url = new URL(value, self.location.origin);
            if (url.origin !== self.location.origin || uniqueUrls.indexOf(url.href) !== -1) {
                return;
            }

            // The page sends only link/script/image resources. Keep this
            // guard in the worker too so a page cannot ask us to cache HTML.
            if (!/\.[a-z0-9]{1,8}(?:[?#].*)?$/i.test(url.pathname)) {
                return;
            }

            uniqueUrls.push(url.href);
        } catch (error) {
            // Ignore malformed or cross-origin values.
        }
    });

    return Promise.all(uniqueUrls.map(function (url) {
        var request = new Request(url, {
            credentials: 'same-origin',
            cache: 'no-store'
        });

        return fetch(request).then(function (response) {
            return response.ok ? cacheResponse(staticCacheName, request, response) : null;
        }).catch(function () {
            return null;
        });
    }));
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
    event.respondWith(userCacheName(request).then(function (cacheName) {
        if (request.mode === 'navigate') {
            return networkFirst(request, cacheName);
        }

        return cacheFirstAfterNetwork(request, cacheName);
    }));
});
