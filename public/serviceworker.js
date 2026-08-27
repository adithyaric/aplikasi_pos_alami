var staticCacheName = 'alami-admin-pwa-static-v6';
// Bump this when the cached HTML contract changes so stale forms cannot be
// opened offline after a deployment.
var userCachePrefix = 'alami-admin-pwa-user-v3-';
var filesToCache = [
    '/img/logo.png'
];
var stateDbName = 'alami-admin-pwa-state';
var stateDbVersion = 1;
var stateDbStore = 'state';
var stateDbKey = 'active-user-cache';
var activeUserCacheName = null;
var activeUserCachePromise = null;
var queueDbName = 'alami-pwa';
var queueDbVersion = 3;
var queueStoreName = 'requests';
var backgroundSyncTag = 'alami-offline-sync';

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

self.addEventListener('sync', function (event) {
    if (event.tag !== backgroundSyncTag) {
        return;
    }

    event.waitUntil(syncQueuedRequests());
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
        return;
    }

    if (data.type === 'warm-user-pages' && Array.isArray(data.urls) && activeUserCacheName) {
        event.waitUntil(warmUserPages(data.urls, activeUserCacheName));
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

function openQueueDb() {
    if (!self.indexedDB) {
        return Promise.reject(new Error('IndexedDB is unavailable'));
    }

    return new Promise(function (resolve, reject) {
        var request = indexedDB.open(queueDbName, queueDbVersion);

        request.onupgradeneeded = function () {
            if (!request.result.objectStoreNames.contains(queueStoreName)) {
                request.result.createObjectStore(queueStoreName, { keyPath: 'id' });
            }
        };
        request.onsuccess = function () { resolve(request.result); };
        request.onerror = function () { reject(request.error || new Error('Unable to open offline queue')); };
    });
}

function queueTransaction(mode, callback) {
    return openQueueDb().then(function (database) {
        return new Promise(function (resolve, reject) {
            var transaction;

            try {
                transaction = database.transaction(queueStoreName, mode);
            } catch (error) {
                database.close();
                reject(error);
                return;
            }

            var store = transaction.objectStore(queueStoreName);
            var result;

            try {
                result = callback(store);
            } catch (error) {
                database.close();
                reject(error);
                return;
            }

            transaction.oncomplete = function () {
                database.close();
                resolve(result);
            };
            transaction.onerror = function () {
                database.close();
                reject(transaction.error || new Error('Unable to update offline queue'));
            };
            transaction.onabort = function () {
                database.close();
                reject(transaction.error || new Error('Offline queue transaction aborted'));
            };
        });
    });
}

function queuedItems() {
    return queueTransaction('readonly', function (store) {
        var request = store.getAll();

        return new Promise(function (resolve, reject) {
            request.onsuccess = function () { resolve(request.result || []); };
            request.onerror = function () {
                reject(request.error || new Error('Unable to read offline queue'));
            };
        });
    });
}

function putQueuedItem(item) {
    return queueTransaction('readwrite', function (store) {
        store.put(item);
    });
}

function deleteQueuedItem(id) {
    return queueTransaction('readwrite', function (store) {
        store.delete(id);
    });
}

function queueUserScope(cacheName) {
    return validCacheName(cacheName) ? cacheName.slice(userCachePrefix.length) : null;
}

function queuedCsrfToken() {
    return fetch(new URL('/offline/csrf-token?refresh=' + Date.now(), self.location.origin), {
        method: 'GET',
        credentials: 'include',
        cache: 'no-store',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).then(function (response) {
        return response.text().then(function (text) {
            var payload = null;

            try {
                payload = text ? JSON.parse(text) : null;
            } catch (error) {
                payload = null;
            }

            if (response.redirected && new URL(response.url).pathname === '/login') {
                var loginError = new Error('Sesi login tidak tersedia.');
                loginError.retryable = false;
                throw loginError;
            }

            if (!response.ok || !payload || !payload.token) {
                var error = new Error('Sesi login tidak tersedia.');
                error.retryable = false;
                throw error;
            }

            return payload.token;
        });
    }).catch(function (error) {
        if (error && error.retryable === false) {
            throw error;
        }

        var networkError = new Error('Koneksi belum tersedia.');
        networkError.retryable = true;
        throw networkError;
    });
}

function replaceQueuedCsrfToken(item, token) {
    if (item.bodyType === 'form-data' && Array.isArray(item.body)) {
        item.body = item.body.filter(function (entry) {
            return entry.key !== '_token';
        });
        item.body.push({ key: '_token', value: token, file: false });
        return;
    }

    if (!item.body || !item.contentType) {
        return;
    }

    if (item.contentType.indexOf('application/json') !== -1) {
        var json = JSON.parse(item.body);
        json._token = token;
        item.body = JSON.stringify(json);
        return;
    }

    var params = new URLSearchParams(item.body);
    params.set('_token', token);
    item.body = params.toString();
}

function replayQueuedItem(item, token) {
    replaceQueuedCsrfToken(item, token);

    var headers = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': token
    };

    if (item.contentType) {
        headers['Content-Type'] = item.contentType;
    }

    var requestBody = item.body;
    if (item.bodyType === 'form-data' && Array.isArray(item.body)) {
        requestBody = new FormData();
        item.body.forEach(function (entry) {
            if (!entry || !entry.key) {
                return;
            }

            if (entry.file) {
                requestBody.append(entry.key, entry.value, entry.filename || 'upload');
            } else {
                requestBody.append(entry.key, String(entry.value || ''));
            }
        });
    }

    return fetch(item.url, {
        method: item.method,
        headers: headers,
        credentials: 'include',
        redirect: 'follow',
        body: requestBody
    }).then(function (response) {
        return response.text().then(function (text) {
            var payload = null;

            try {
                payload = text ? JSON.parse(text) : null;
            } catch (error) {
                payload = null;
            }

            if (!response.ok) {
                var responseError = new Error(payload && payload.message
                    ? payload.message
                    : 'Server menolak data offline.');
                responseError.status = response.status;
                responseError.retryable = response.status >= 500 || response.status === 408 || response.status === 429;
                throw responseError;
            }

            if (response.redirected && new URL(response.url).pathname === '/login') {
                var loginError = new Error('Sesi login tidak tersedia.');
                loginError.retryable = false;
                throw loginError;
            }

            if (payload && payload.success === false) {
                var rejectedError = new Error(payload.message || 'Server menolak perubahan data.');
                rejectedError.retryable = false;
                throw rejectedError;
            }

            if (!payload && (response.headers.get('Content-Type') || '').indexOf('text/html') !== -1) {
                var htmlError = new Error('Respons server tidak valid.');
                htmlError.retryable = false;
                throw htmlError;
            }

            return payload;
        });
    }).catch(function (error) {
        if (error && (error.retryable === false || error.status)) {
            throw error;
        }

        var networkError = new Error('Koneksi terputus saat sinkronisasi.');
        networkError.retryable = true;
        throw networkError;
    });
}

function queuedErrorMessage(error) {
    return error && error.message ? error.message : 'Gagal menyinkronkan data offline.';
}

function notifyQueueClients() {
    return self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clients) {
        clients.forEach(function (client) {
            client.postMessage({ type: 'alami-offline-queue-changed' });
        });
    });
}

function syncQueuedRequests() {
    return Promise.resolve(activeUserCacheName || readPersistedUserCache())
        .then(function (cacheName) {
            var scope = queueUserScope(cacheName);

            if (!scope) {
                return;
            }

            return queuedItems().then(function (items) {
                return items.filter(function (item) {
                    return (item.status === 'pending' || item.status === 'syncing')
                        && item.userScope === scope;
                }).sort(function (a, b) {
                    return a.createdAt - b.createdAt;
                });
            }).then(function (items) {
                if (!items.length) {
                    return;
                }

                return queuedCsrfToken().then(function (token) {
                    return items.reduce(function (chain, item) {
                        return chain.then(function () {
                            item.status = 'syncing';
                            item.attempts = (item.attempts || 0) + 1;

                            return putQueuedItem(item)
                                .then(function () { return replayQueuedItem(item, token); })
                                .then(function () { return deleteQueuedItem(item.id); })
                                .then(notifyQueueClients)
                                .catch(function (error) {
                                    item.lastError = queuedErrorMessage(error);

                                    if (error && error.retryable) {
                                        item.status = 'pending';
                                        return putQueuedItem(item).then(function () { throw error; });
                                    }

                                    item.status = 'failed';
                                    return putQueuedItem(item).then(notifyQueueClients);
                                });
                        });
                    }, Promise.resolve());
                });
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
        return cache.match(request).then(function (response) {
            if (response) {
                return response;
            }

            return cache.match(request, { ignoreSearch: true }).then(function (responseWithoutSearch) {
                if (responseWithoutSearch) {
                    return responseWithoutSearch;
                }

                // Branch-scoped users are redirected from /penjualan to
                // /penjualan/cabang online. Treat those URLs as the same
                // offline page so a queued sale never lands on an uncached
                // redirect alias.
                var url = new URL(request.url);
                if (url.pathname !== '/penjualan') {
                    return null;
                }

                url.pathname = '/penjualan/cabang';
                var branchRequest = new Request(url.href, {
                    credentials: 'include'
                });

                return cache.match(branchRequest, { ignoreSearch: true });
            });
        });
    });
}

function offlineMissResponse(request) {
    var path = new URL(request.url).pathname;

    return new Response(
        'Deze pagina is nog niet beschikbaar in de offline cache: ' + path,
        {
            status: 503,
            statusText: 'Offline page not cached',
            headers: {
                'Content-Type': 'text/plain; charset=utf-8',
                'Cache-Control': 'no-store'
            }
        }
    );
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
                // Never render the package fallback. A role-scoped cached page
                // is preferred; if this route was never opened or warmed,
                // return a plain 503 instead of another user's page.
                return response || offlineMissResponse(request);
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
            && !isExcludedPath(url.pathname);
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
        var cacheRequest = new Request(url, {
            credentials: 'include'
        });

        return fetch(request).then(function (response) {
            return response.ok ? cacheResponse(cacheName, cacheRequest, response) : null;
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
        var cacheRequest = new Request(url, {
            credentials: 'same-origin'
        });

        return fetch(request).then(function (response) {
            return response.ok ? cacheResponse(staticCacheName, cacheRequest, response) : null;
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
