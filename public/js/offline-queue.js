(function () {
    'use strict';

    var DB_NAME = 'alami-pwa';
    var DB_VERSION = 2;
    var STORE_NAME = 'requests';
    var REQUEST_TIMEOUT = 15000;
    var databasePromise = null;
    var synchronizing = false;

    function currentUserScope() {
        var element = document.querySelector('meta[name="offline-user-scope"]');

        return element ? element.getAttribute('content') : null;
    }

    function openDatabase() {
        if (databasePromise) {
            return databasePromise;
        }

        if (!window.indexedDB) {
            return Promise.reject(new Error('Browser tidak mendukung penyimpanan offline.'));
        }

        databasePromise = new Promise(function (resolve, reject) {
            var request = window.indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = function () {
                var database = request.result;

                if (!database.objectStoreNames.contains(STORE_NAME)) {
                    database.createObjectStore(STORE_NAME, { keyPath: 'id' });
                }
            };

            request.onsuccess = function () {
                resolve(request.result);
            };

            request.onerror = function () {
                reject(request.error || new Error('Gagal membuka penyimpanan offline.'));
            };
        });

        return databasePromise;
    }

    function transaction(mode, callback) {
        return openDatabase().then(function (database) {
            return new Promise(function (resolve, reject) {
                var tx = database.transaction(STORE_NAME, mode);
                var store = tx.objectStore(STORE_NAME);
                var result;

                try {
                    result = callback(store);
                } catch (error) {
                    reject(error);
                    return;
                }

                tx.oncomplete = function () {
                    resolve(result);
                };
                tx.onerror = function () {
                    reject(tx.error || new Error('Gagal menyimpan antrean offline.'));
                };
                tx.onabort = function () {
                    reject(tx.error || new Error('Penyimpanan offline dibatalkan.'));
                };
            });
        });
    }

    function allRequests() {
        return transaction('readonly', function (store) {
            var request = store.getAll();
            return new Promise(function (resolve, reject) {
                request.onsuccess = function () {
                    resolve(request.result || []);
                };
                request.onerror = function () {
                    reject(request.error || new Error('Gagal membaca antrean offline.'));
                };
            });
        });
    }

    function currentUserRequests(items) {
        var scope = currentUserScope();

        // Requests created by this version are always scoped.  Do not replay
        // an unscoped request under a different account after logout/login.
        return (items || []).filter(function (item) {
            return scope && item.userScope === scope;
        });
    }

    function putRequest(item) {
        return transaction('readwrite', function (store) {
            store.put(item);
        });
    }

    function deleteRequest(id) {
        return transaction('readwrite', function (store) {
            store.delete(id);
        });
    }

    function makeId() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }

        return 'offline-' + Date.now() + '-' + Math.random().toString(16).slice(2);
    }

    function notify(icon, title) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                icon: icon,
                title: title,
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true
            });
            return;
        }

        window.alert(title);
    }

    function updateIndicator() {
        var indicator = document.getElementById('offline-sync-indicator');
        var label = document.getElementById('offline-sync-label');
        var button = document.getElementById('offline-sync-button');

        return allRequests().then(function (items) {
            items = currentUserRequests(items);
            var pending = items.filter(function (item) { return item.status === 'pending'; }).length;
            var failed = items.filter(function (item) { return item.status === 'failed'; }).length;
            var offline = !navigator.onLine;

            if (label) {
                if (offline) {
                    label.textContent = pending || failed
                        ? 'Offline · ' + (pending + failed) + ' data menunggu sinkronisasi'
                        : 'Offline';
                } else if (pending || failed) {
                    label.textContent = pending
                        ? pending + ' data menunggu sinkronisasi'
                        : failed + ' data gagal disinkronkan';
                } else {
                    label.textContent = 'Online';
                }
            }

            if (button) {
                button.style.display = pending || failed ? '' : 'none';
                button.disabled = offline;
            }

            if (indicator) {
                indicator.style.display = offline || pending || failed ? '' : 'none';
                indicator.className = failed
                    ? 'alert alert-danger'
                    : (offline || pending ? 'alert alert-warning' : 'alert alert-info');
            }

            return { pending: pending, failed: failed };
        }).catch(function () {
            if (label) {
                label.textContent = !navigator.onLine ? 'Offline' : 'Sinkronisasi belum tersedia';
            }

            if (indicator) {
                indicator.style.display = !navigator.onLine ? '' : 'none';
            }

            return { pending: 0, failed: 0 };
        });
    }

    function serializeForm(form, id) {
        var formData = new FormData(form);
        var params = new URLSearchParams();

        formData.forEach(function (value, key) {
            if (typeof value === 'string') {
                params.append(key, value);
                return;
            }

            if (value && typeof value.name === 'string' && value.name !== '') {
                throw new Error('Form offline tidak mendukung upload file. Kirim form ini saat online.');
            }
        });

        params.set('offline_client_id', id);

        return params.toString();
    }

    function pathOf(url) {
        try {
            return new URL(url, window.location.href).pathname;
        } catch (error) {
            return '';
        }
    }

    function isExcludedPath(url) {
        var path = pathOf(url);

        return !path
            || /(^|\/)(laporan|export|pdf|download|import)(\/|$)/i.test(path)
            || /^\/offline\/csrf-token$/.test(path)
            || /^\/(logout|login|register|password|email|verification)(\/|$)/i.test(path);
    }

    function isUnsafeMethod(method) {
        return ['POST', 'PUT', 'PATCH', 'DELETE'].indexOf(String(method || 'POST').toUpperCase()) !== -1;
    }

    function isQueueableForm(form) {
        return isUnsafeMethod(form.method || 'POST')
            && !form.hasAttribute('data-offline-skip')
            && !isExcludedPath(form.action);
    }

    function hasSelectedFile(form) {
        return Array.prototype.some.call(form.querySelectorAll('input[type="file"]'), function (input) {
            return input.files && input.files.length > 0;
        });
    }

    function replaceCsrfToken(item, token) {
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

    function refreshCsrfToken(item) {
        if (!isUnsafeMethod(item.method)) {
            return Promise.resolve(item);
        }

        return fetch('/offline/csrf-token?refresh=' + Date.now(), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            cache: 'no-store'
        }).then(function (response) {
            return response.text().then(function (text) {
                var payload = null;

                try {
                    payload = text ? JSON.parse(text) : null;
                } catch (error) {
                    payload = null;
                }

                if (!response.ok || !payload || !payload.token) {
                    var tokenError = new Error('Sesi login tidak tersedia. Login kembali untuk menyinkronkan data.');
                    tokenError.status = response.status || 401;
                    tokenError.payload = payload;
                    throw tokenError;
                }

                item.csrfToken = payload.token;
                replaceCsrfToken(item, payload.token);

                return item;
            });
        });
    }

    function requestJson(item) {
        var controller = window.AbortController ? new AbortController() : null;
        var timeout = controller
            ? window.setTimeout(function () { controller.abort(); }, REQUEST_TIMEOUT)
            : null;
        var headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };

        if (item.contentType) {
            headers['Content-Type'] = item.contentType;
        }
        return refreshCsrfToken(item).then(function () {
            if (item.csrfToken) {
                headers['X-CSRF-TOKEN'] = item.csrfToken;
            }

            return fetch(item.url, {
                method: item.method,
                headers: headers,
                credentials: 'same-origin',
                redirect: 'follow',
                body: item.body,
                signal: controller ? controller.signal : undefined
            });
        }).then(function (response) {
            return response.text().then(function (text) {
                var payload = null;

                try {
                    payload = text ? JSON.parse(text) : null;
                } catch (error) {
                    payload = null;
                }

                if (!response.ok) {
                    var serverError = new Error(payload && payload.message
                        ? payload.message
                        : 'Server menolak data offline.');
                    serverError.status = response.status;
                    serverError.payload = payload;
                    throw serverError;
                }

                if (payload && typeof payload === 'object') {
                    if (payload.success === false) {
                        var rejectedPayload = new Error(payload.message || 'Server menolak perubahan data.');
                        rejectedPayload.status = response.status;
                        rejectedPayload.payload = payload;
                        throw rejectedPayload;
                    }

                    if (item.allowNonJson || payload.success === true) {
                        payload.success = payload.success === undefined ? true : payload.success;
                        return payload;
                    }
                }

                if (item.allowNonJson) {
                    var responseType = response.headers.get('Content-Type') || '';
                    if (responseType.indexOf('text/html') !== -1) {
                        var htmlResponse = new Error('Sesi login mungkin sudah berakhir. Muat ulang halaman.');
                        htmlResponse.status = response.status === 200 ? 401 : response.status;
                        htmlResponse.payload = payload;
                        throw htmlResponse;
                    }

                    return {
                        success: true,
                        redirect: response.redirected ? response.url : null
                    };
                }

                var invalidResponse = new Error('Respons server tidak valid.');
                invalidResponse.status = response.status;
                invalidResponse.payload = payload;
                throw invalidResponse;
            });
        }).finally(function () {
            if (timeout) {
                window.clearTimeout(timeout);
            }
        });
    }

    function firstServerError(error) {
        var payload = error && error.payload;

        if (payload && payload.errors) {
            var fields = Object.keys(payload.errors);
            if (fields.length && payload.errors[fields[0]] && payload.errors[fields[0]][0]) {
                return payload.errors[fields[0]][0];
            }
        }

        return error && error.message ? error.message : 'Gagal menyimpan data.';
    }

    function setFormBusy(form, busy) {
        Array.prototype.forEach.call(form.querySelectorAll('[type="submit"]'), function (button) {
            button.disabled = busy;
        });
    }

    function normaliseFormValues(form) {
        Array.prototype.forEach.call(form.querySelectorAll('.rupiah-input'), function (input) {
            input.value = String(input.value || '').replace(/[^\d]/g, '');
        });
    }

    function csrfToken() {
        var element = document.querySelector('meta[name="csrf-token"]');

        return element ? element.getAttribute('content') : null;
    }

    function buildFormRequest(form) {
        var id = makeId();

        return {
            id: id,
            url: form.action,
            method: (form.method || 'POST').toUpperCase(),
            body: serializeForm(form, id),
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            csrfToken: csrfToken(),
            allowNonJson: !form.hasAttribute('data-offline-queue'),
            title: form.getAttribute('data-offline-title') || 'Perubahan data',
            redirect: form.getAttribute('data-offline-redirect') || null,
            userScope: currentUserScope(),
            createdAt: Date.now(),
            attempts: 0,
            status: 'pending',
            lastError: null
        };
    }

    function serialiseAjaxData(data, contentType, id) {
        if (data instanceof FormData) {
            var formData = new URLSearchParams();
            data.forEach(function (value, key) {
                if (typeof value !== 'string') {
                    throw new Error('Permintaan offline dengan upload file harus dikirim saat online.');
                }
                formData.append(key, value);
            });
            formData.set('offline_client_id', id);
            return formData.toString();
        }

        if (contentType && contentType.indexOf('application/json') !== -1) {
            var json = typeof data === 'string' && data !== '' ? JSON.parse(data) : (data || {});
            json.offline_client_id = id;
            return JSON.stringify(json);
        }

        var encoded = typeof data === 'string'
            ? data
            : (window.jQuery && typeof window.jQuery.param === 'function' ? window.jQuery.param(data || {}) : '');
        var params = new URLSearchParams(encoded);
        params.set('offline_client_id', id);

        return params.toString();
    }

    function buildAjaxRequest(options) {
        var id = makeId();
        var contentType = typeof options.contentType === 'string' ? options.contentType : '';

        return {
            id: id,
            url: new URL(options.url, window.location.href).href,
            method: String(options.method || options.type || 'GET').toUpperCase(),
            body: serialiseAjaxData(options.data, contentType, id),
            contentType: contentType || 'application/x-www-form-urlencoded; charset=UTF-8',
            csrfToken: options.headers && (options.headers['X-CSRF-TOKEN'] || options.headers['x-csrf-token'])
                || csrfToken(),
            allowNonJson: true,
            title: options.offlineTitle || 'Perubahan data',
            redirect: options.offlineRedirect || null,
            userScope: currentUserScope(),
            createdAt: Date.now(),
            attempts: 0,
            status: 'pending',
            lastError: null
        };
    }

    function queueItem(item, message) {
        return putRequest(item).then(function () {
            notify('warning', message || 'Koneksi terputus. Data disimpan di perangkat dan akan dikirim saat online.');
            updateIndicator();
            return item;
        });
    }

    function showServerError(error) {
        var message = firstServerError(error);

        if (error && error.status === 419) {
            message = 'Sesi login sudah berubah. Muat ulang halaman dan login kembali sebelum mengirim data.';
        } else if (error && error.status === 403) {
            message = 'Anda tidak memiliki akses untuk menyimpan data ini.';
        }

        notify('error', message);
    }

    function handleFormSubmit(form) {
        if (form.dataset.offlineSubmitting === 'true') {
            return;
        }

        form.dataset.offlineSubmitting = 'true';
        setFormBusy(form, true);
        normaliseFormValues(form);

        var item;

        try {
            item = buildFormRequest(form);
        } catch (error) {
            delete form.dataset.offlineSubmitting;
            setFormBusy(form, false);
            showServerError(error);
            return;
        }

        var submission = navigator.onLine
            ? requestJson(item)
            : Promise.reject(new Error('offline'));

        submission.then(function (payload) {
            notify('success', payload.created === false
                ? 'Data ini sudah pernah tersimpan.'
                : 'Data berhasil disimpan.');

            if (payload.redirect) {
                window.location.assign(payload.redirect);
            }
        }).catch(function (error) {
            if (!error.status) {
                queueItem(item).then(function () {
                    if (item.redirect) {
                        window.location.assign(item.redirect);
                    }
                }).catch(showServerError);
                return;
            }

            showServerError(error);
        }).finally(function () {
            delete form.dataset.offlineSubmitting;
            setFormBusy(form, false);
        });
    }

    function syncQueue() {
        if (synchronizing || !navigator.onLine) {
            return Promise.resolve();
        }

        synchronizing = true;

        return allRequests().then(function (items) {
            items = currentUserRequests(items);
            return items.filter(function (item) {
                return item.status === 'pending';
            }).sort(function (a, b) {
                return a.createdAt - b.createdAt;
            });
        }).then(function (items) {
            return items.reduce(function (chain, item) {
                return chain.then(function (shouldContinue) {
                    if (!shouldContinue || !navigator.onLine) {
                        return false;
                    }

                    return requestJson(item).then(function () {
                        return deleteRequest(item.id).then(function () {
                            notify('success', item.title + ' berhasil disinkronkan.');
                            return true;
                        });
                    }).catch(function (error) {
                        item.attempts = (item.attempts || 0) + 1;
                        item.lastError = firstServerError(error);

                        if (!error.status) {
                            return putRequest(item).then(function () { return false; });
                        }

                        item.status = 'failed';
                        return putRequest(item).then(function () {
                            notify('error', item.title + ': ' + item.lastError);
                            return true;
                        });
                    });
                });
            }, Promise.resolve(true));
        }).finally(function () {
            synchronizing = false;
            updateIndicator();
        });
    }

    function retryFailed() {
        return allRequests().then(function (items) {
            items = currentUserRequests(items);
            return Promise.all(items.filter(function (item) {
                return item.status === 'failed';
            }).map(function (item) {
                item.status = 'pending';
                item.lastError = null;
                return putRequest(item);
            }));
        }).then(syncQueue);
    }

    function enqueueForm(form, title) {
        normaliseFormValues(form);

        if (hasSelectedFile(form)) {
            return Promise.reject(new Error('Form dengan file harus dikirim saat online.'));
        }

        var item = buildFormRequest(form);
        if (title) {
            item.title = title;
        }

        return queueItem(item);
    }

    function installAjaxBridge() {
        if (!window.jQuery || !window.jQuery.ajax || window.jQuery.ajax.__alamiOfflineBridge) {
            return;
        }

        var $ = window.jQuery;
        var originalAjax = $.ajax;

        function offlineAjax(options) {
            var requestOptions = typeof options === 'string' ? { url: options } : (options || {});
            var method = String(requestOptions.method || requestOptions.type || 'GET').toUpperCase();

            if (!isUnsafeMethod(method) || isExcludedPath(requestOptions.url)) {
                return originalAjax.apply(this, arguments);
            }

            var item;
            try {
                item = buildAjaxRequest(requestOptions);
            } catch (error) {
                showServerError(error);
                return originalAjax.apply(this, arguments);
            }

            var deferred = $.Deferred();
            var promise = deferred.promise();

            function success(payload, state) {
                if (typeof requestOptions.success === 'function') {
                    requestOptions.success.call(requestOptions.context || requestOptions, payload, state, promise);
                }
                deferred.resolve(payload, state, promise);
                if (typeof requestOptions.complete === 'function') {
                    requestOptions.complete.call(requestOptions.context || requestOptions, promise, state);
                }
            }

            function failure(error) {
                var xhr = {
                    status: error && error.status ? error.status : 0,
                    responseJSON: error && error.payload ? error.payload : null,
                    responseText: error && error.payload ? JSON.stringify(error.payload) : ''
                };
                var state = error && error.status ? 'error' : 'offline';

                if (typeof requestOptions.error === 'function') {
                    requestOptions.error.call(requestOptions.context || requestOptions, xhr, state, error);
                }
                deferred.reject(xhr, state, error);
                if (typeof requestOptions.complete === 'function') {
                    requestOptions.complete.call(requestOptions.context || requestOptions, xhr, state);
                }
            }

            var request = navigator.onLine
                ? requestJson(item).catch(function (error) {
                    if (error && error.status) {
                        throw error;
                    }

                    return queueItem(item).then(function () {
                        return { success: true, queued: true };
                    });
                })
                : queueItem(item).then(function () {
                    return { success: true, queued: true };
                });

            request.then(function (payload) {
                success(payload, payload.queued ? 'queued' : 'success');
            }).catch(failure);

            return promise;
        }

        offlineAjax.__alamiOfflineBridge = true;
        $.ajax = offlineAjax;
    }

    function clearRuntimeCaches() {
        var scope = currentUserScope();
        var cacheName = scope ? 'alami-admin-pwa-user-' + scope : null;

        if (navigator.serviceWorker && navigator.serviceWorker.controller && cacheName) {
            navigator.serviceWorker.controller.postMessage({
                type: 'clear-user-cache',
                cacheName: cacheName
            });
        }

        if (window.caches) {
            window.caches.keys().then(function (cacheNames) {
                cacheNames.filter(function (name) {
                    return name.indexOf('alami-admin-pwa-user-') === 0;
                }).forEach(function (name) {
                    window.caches.delete(name);
                });
            });
        }

        document.cookie = 'alami_pwa_user=; Max-Age=0; path=/; SameSite=Lax';
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (form.hasAttribute('data-clear-offline-cache')) {
            clearRuntimeCaches();
            return;
        }

        var explicit = form.hasAttribute('data-offline-queue');
        var generic = !explicit && isQueueableForm(form);

        if (!explicit && !generic) {
            return;
        }

        // Let page-level validators and submit handlers prepare the form first.
        // Custom AJAX handlers already have their own offline bridge.
        if (!explicit && event.defaultPrevented) {
            return;
        }

        if (hasSelectedFile(form)) {
            if (!navigator.onLine) {
                event.preventDefault();
                event.stopImmediatePropagation();
                notify('warning', 'Form dengan file harus dikirim saat online.');
            }

            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        handleFormSubmit(form);
    });

    document.addEventListener('DOMContentLoaded', function () {
        var button = document.getElementById('offline-sync-button');

        if (button) {
            button.addEventListener('click', function () {
                button.disabled = true;
                retryFailed().finally(function () {
                    button.disabled = false;
                });
            });
        }

        installAjaxBridge();
        updateIndicator();
        syncQueue();
    });

    window.addEventListener('online', function () {
        updateIndicator();
        syncQueue();
    });

    window.addEventListener('offline', updateIndicator);

    window.AlamiOfflineQueue = {
        sync: syncQueue,
        refresh: updateIndicator,
        enqueueForm: enqueueForm,
        clearRuntimeCaches: clearRuntimeCaches,
        enqueue: function (options) {
            return queueItem(buildAjaxRequest(options));
        }
    };
}());
