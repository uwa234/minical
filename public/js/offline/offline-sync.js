/**
 * miniCal offline layer: queues mutations, caches reads, syncs on reconnect,
 * and tracks conflicts when the server rejects a replayed change.
 */
(function (window, $) {
    'use strict';

    if (!$) {
        return;
    }

    var DB_NAME = 'minical-offline-v1';
    var DB_VERSION = 2;
    var STORE_QUEUE = 'mutationQueue';
    var STORE_CACHE = 'apiCache';
    var STORE_CONFLICTS = 'syncConflicts';

    var EXCLUDED_URL_PATTERNS = [
        /auth\/checkSession/i,
        /language_translation\/insert_non_translated_keys/i,
        /auth\/resend_verification/i,
        /tokenex/i,
        /stripe/i,
        /paypal/i,
        /upload/i,
        /cron\//i
    ];

    var CACHEABLE_URL_PATTERNS = [
        /booking\/get_bookings_in_JSON/i
    ];

    var MUTABLE_METHODS = {
        POST: true,
        PUT: true,
        PATCH: true,
        DELETE: true
    };

    var ACTION_LABELS = [
        { pattern: /booking\/create_booking_AJAX/i, label: 'Create reservation' },
        { pattern: /booking\/update_booking_AJAX/i, label: 'Update reservation / check-in / check-out' },
        { pattern: /invoice\/insert_payment_AJAX/i, label: 'Record payment (invoice)' },
        { pattern: /customer\/insert_payments_AJAX/i, label: 'Record payment (customer)' },
        { pattern: /room\/update_room_status/i, label: 'Update room status' },
        { pattern: /extra\/update_booking_extra_AJAX/i, label: 'Update booking extra' },
        { pattern: /customer\/update/i, label: 'Update customer' }
    ];

    function uuid() {
        if (window.crypto && window.crypto.randomUUID) {
            return window.crypto.randomUUID();
        }
        return 'offline-' + Date.now() + '-' + Math.random().toString(16).slice(2);
    }

    function normalizeAjaxOptions(url, options) {
        var settings = options || {};
        if (typeof url === 'object') {
            settings = url;
        } else if (url) {
            settings.url = url;
        }
        settings.type = (settings.type || settings.method || 'GET').toUpperCase();
        settings.method = settings.type;
        return settings;
    }

    function absoluteUrl(path) {
        if (!path) {
            return '';
        }
        if (/^https?:\/\//i.test(path)) {
            return path;
        }
        var base = getBaseURL();
        if (path.charAt(0) === '/') {
            try {
                return new URL(path, base).href;
            } catch (e) {
                return base + path.replace(/^\//, '');
            }
        }
        return base + path.replace(/^\//, '');
    }

    function isSameOrigin(url) {
        try {
            return new URL(url, getBaseURL()).origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    function matchesAny(patterns, value) {
        for (var i = 0; i < patterns.length; i++) {
            if (patterns[i].test(value)) {
                return true;
            }
        }
        return false;
    }

    function cacheKeyFor(settings) {
        var data = settings.data;
        if (typeof data === 'object' && data !== null && !(data instanceof FormData)) {
            try {
                data = JSON.stringify(data);
            } catch (e) {
                data = String(data);
            }
        }
        return settings.type + ':' + settings.url + ':' + (data || '');
    }

    function offlineText(key, fallback) {
        if (typeof l === 'function') {
            var translated = l(key, true);
            if (translated && translated !== key) {
                return translated;
            }
        }
        return fallback;
    }

    function describeAction(url, data) {
        var i;
        for (i = 0; i < ACTION_LABELS.length; i++) {
            if (ACTION_LABELS[i].pattern.test(url)) {
                return ACTION_LABELS[i].label;
            }
        }
        try {
            var path = new URL(url).pathname;
            return path.replace(/^.*\/public\//, '').replace(/\/$/, '') || url;
        } catch (e) {
            return url;
        }
    }

    function summarizeData(data) {
        if (!data) {
            return '';
        }
        if (typeof data === 'string') {
            if (data.indexOf('booking_id') !== -1) {
                var m = data.match(/booking_id[=:](\d+)/i);
                if (m) {
                    return 'Booking #' + m[1];
                }
            }
            return data.length > 80 ? data.substring(0, 80) + '…' : data;
        }
        if (data.booking_id) {
            return 'Booking #' + data.booking_id;
        }
        if (data.data && data.data.booking && data.data.booking.booking_id) {
            return 'Booking #' + data.data.booking.booking_id;
        }
        if (data.data && data.data.booking && data.data.booking.state !== undefined) {
            var states = { 0: 'Reserved', 1: 'Checked in', 2: 'Checked out', 3: 'Maintenance', 4: 'Cancelled' };
            return states[data.data.booking.state] || ('State ' + data.data.booking.state);
        }
        return '';
    }

    function parseResponseBody(xhr) {
        if (xhr && xhr.responseJSON) {
            return xhr.responseJSON;
        }
        if (xhr && xhr.responseText) {
            try {
                return JSON.parse(xhr.responseText);
            } catch (e) {
                return { message: xhr.responseText };
            }
        }
        return null;
    }

    function extractErrorMessage(body, xhr) {
        if (!body) {
            return (xhr && xhr.statusText) ? xhr.statusText : offlineText('offline_sync_unknown_error', 'Sync failed');
        }
        if (body.message) {
            return body.message;
        }
        if (body.errors && body.errors.length) {
            return body.errors.join('; ');
        }
        if (body.response === 'failure') {
            return body.message || offlineText('offline_sync_rejected', 'Server rejected this change');
        }
        return offlineText('offline_sync_unknown_error', 'Sync failed');
    }

    var MiniCalOffline = {
        db: null,
        pendingCount: 0,
        conflictCount: 0,
        isSyncing: false,
        bannerEl: null,

        init: function () {
            var self = this;
            if (!window.indexedDB) {
                console.warn('IndexedDB unavailable; offline queue disabled.');
                return $.Deferred().resolve().promise();
            }

            return this.openDatabase()
                .then(function () {
                    self.installAjaxInterceptor();
                    self.bindConnectivityEvents();
                    self.registerServiceWorker();
                    self.renderBanner();
                    return self.refreshCounts();
                })
                .then(function () {
                    self.updateBanner();
                    if (navigator.onLine && self.pendingCount > 0) {
                        return self.syncQueue();
                    }
                })
                .catch(function (err) {
                    console.warn('MiniCal offline module failed to initialize', err);
                });
        },

        openDatabase: function () {
            var self = this;
            return new Promise(function (resolve, reject) {
                var request = window.indexedDB.open(DB_NAME, DB_VERSION);
                request.onerror = function () {
                    reject(request.error);
                };
                request.onupgradeneeded = function (event) {
                    var db = event.target.result;
                    if (!db.objectStoreNames.contains(STORE_QUEUE)) {
                        var queue = db.createObjectStore(STORE_QUEUE, { keyPath: 'id' });
                        queue.createIndex('createdAt', 'createdAt', { unique: false });
                        queue.createIndex('status', 'status', { unique: false });
                    }
                    if (!db.objectStoreNames.contains(STORE_CACHE)) {
                        db.createObjectStore(STORE_CACHE, { keyPath: 'key' });
                    }
                    if (!db.objectStoreNames.contains(STORE_CONFLICTS)) {
                        var conflicts = db.createObjectStore(STORE_CONFLICTS, { keyPath: 'id' });
                        conflicts.createIndex('failedAt', 'failedAt', { unique: false });
                    }
                };
                request.onsuccess = function () {
                    self.db = request.result;
                    resolve();
                };
            });
        },

        dbTransaction: function (storeName, mode) {
            return this.db.transaction(storeName, mode).objectStore(storeName);
        },

        refreshCounts: function () {
            var self = this;
            return Promise.all([this.getAllQueued(), this.getAllConflicts()]).then(function (results) {
                self.pendingCount = results[0].length;
                self.conflictCount = results[1].length;
                return { pending: self.pendingCount, conflicts: self.conflictCount };
            });
        },

        getAllQueued: function () {
            var self = this;
            return new Promise(function (resolve, reject) {
                var store = self.dbTransaction(STORE_QUEUE, 'readonly');
                var request = store.getAll();
                request.onsuccess = function () {
                    var rows = (request.result || []).filter(function (r) {
                        return r.status !== 'conflict';
                    });
                    rows.sort(function (a, b) {
                        return a.createdAt - b.createdAt;
                    });
                    resolve(rows);
                };
                request.onerror = function () {
                    reject(request.error);
                };
            });
        },

        getAllConflicts: function () {
            var self = this;
            return new Promise(function (resolve, reject) {
                if (!self.db.objectStoreNames.contains(STORE_CONFLICTS)) {
                    resolve([]);
                    return;
                }
                var store = self.dbTransaction(STORE_CONFLICTS, 'readonly');
                var request = store.getAll();
                request.onsuccess = function () {
                    var rows = request.result || [];
                    rows.sort(function (a, b) {
                        return b.failedAt - a.failedAt;
                    });
                    resolve(rows);
                };
                request.onerror = function () {
                    reject(request.error);
                };
            });
        },

        putQueueItem: function (item, isUpdate) {
            var self = this;
            return new Promise(function (resolve, reject) {
                var store = self.dbTransaction(STORE_QUEUE, 'readwrite');
                var request = store.put(item);
                request.onsuccess = function () {
                    if (!isUpdate) {
                        self.pendingCount += 1;
                    }
                    resolve(item);
                };
                request.onerror = function () {
                    reject(request.error);
                };
            });
        },

        deleteQueueItem: function (id) {
            var self = this;
            return new Promise(function (resolve, reject) {
                var store = self.dbTransaction(STORE_QUEUE, 'readwrite');
                var request = store.delete(id);
                request.onsuccess = function () {
                    self.pendingCount = Math.max(0, self.pendingCount - 1);
                    resolve();
                };
                request.onerror = function () {
                    reject(request.error);
                };
            });
        },

        putConflict: function (item) {
            var self = this;
            return new Promise(function (resolve, reject) {
                var store = self.dbTransaction(STORE_CONFLICTS, 'readwrite');
                var request = store.put(item);
                request.onsuccess = function () {
                    self.conflictCount += 1;
                    resolve(item);
                };
                request.onerror = function () {
                    reject(request.error);
                };
            });
        },

        deleteConflict: function (id) {
            var self = this;
            return new Promise(function (resolve, reject) {
                var store = self.dbTransaction(STORE_CONFLICTS, 'readwrite');
                var request = store.delete(id);
                request.onsuccess = function () {
                    self.conflictCount = Math.max(0, self.conflictCount - 1);
                    resolve();
                };
                request.onerror = function () {
                    reject(request.error);
                };
            });
        },

        getCachedResponse: function (key) {
            var self = this;
            return new Promise(function (resolve) {
                var store = self.dbTransaction(STORE_CACHE, 'readonly');
                var request = store.get(key);
                request.onsuccess = function () {
                    var row = request.result;
                    resolve(row ? row.payload : null);
                };
                request.onerror = function () {
                    resolve(null);
                };
            });
        },

        setCachedResponse: function (key, payload) {
            var self = this;
            return new Promise(function (resolve, reject) {
                var store = self.dbTransaction(STORE_CACHE, 'readwrite');
                var request = store.put({ key: key, payload: payload, cachedAt: Date.now() });
                request.onsuccess = function () {
                    resolve();
                };
                request.onerror = function () {
                    reject(request.error);
                };
            });
        },

        shouldExclude: function (url) {
            return matchesAny(EXCLUDED_URL_PATTERNS, url);
        },

        shouldCache: function (settings) {
            return matchesAny(CACHEABLE_URL_PATTERNS, settings.url || '');
        },

        shouldQueue: function (settings) {
            if (!MUTABLE_METHODS[settings.type]) {
                return false;
            }
            var url = absoluteUrl(settings.url);
            if (!isSameOrigin(url) || this.shouldExclude(url)) {
                return false;
            }
            return true;
        },

        isServerRejection: function (data) {
            if (!data || typeof data !== 'object') {
                return false;
            }
            if (data.response === 'failure') {
                return true;
            }
            if (data.errors && data.errors.length) {
                return true;
            }
            if (data.success === false && !data.offline) {
                return true;
            }
            return false;
        },

        buildOfflinePayload: function (settings, item) {
            var msg = offlineText(
                'offline_change_queued',
                'Saved offline. Changes will sync when you are back online.'
            );
            var payload = {
                offline: true,
                queued: true,
                queueId: item.id,
                success: false,
                message: msg
            };
            if (settings.dataType === 'json') {
                return payload;
            }
            return JSON.stringify(payload);
        },

        enqueue: function (settings) {
            var self = this;
            var deferred = $.Deferred();
            var url = absoluteUrl(settings.url);
            var item = {
                id: uuid(),
                url: url,
                method: settings.type,
                data: settings.data,
                contentType: settings.contentType,
                processData: settings.processData,
                dataType: settings.dataType,
                headers: settings.headers || null,
                createdAt: Date.now(),
                retries: 0,
                status: 'pending',
                label: describeAction(url, settings.data),
                summary: summarizeData(settings.data)
            };

            this.putQueueItem(item)
                .then(function () {
                    self.updateBanner();
                    $(document).trigger('minical:offline-queued', [item]);
                    deferred.resolve(self.buildOfflinePayload(settings, item));
                })
                .catch(function (err) {
                    deferred.reject(err, 'offline-queue-failed');
                });

            return deferred.promise();
        },

        moveToConflict: function (queueItem, xhr) {
            var self = this;
            var body = parseResponseBody(xhr);
            var conflict = $.extend({}, queueItem, {
                failedAt: Date.now(),
                lastError: extractErrorMessage(body, xhr),
                serverResponse: body,
                httpStatus: xhr && xhr.status ? xhr.status : 0
            });

            return self.deleteQueueItem(queueItem.id)
                .then(function () {
                    return self.putConflict(conflict);
                })
                .then(function () {
                    $(document).trigger('minical:sync-conflict', [conflict]);
                    return conflict;
                });
        },

        installAjaxInterceptor: function () {
            var self = this;
            var originalAjax = $.ajax;

            $.ajax = function (url, options) {
                var settings = normalizeAjaxOptions(url, options);
                settings.url = absoluteUrl(settings.url);

                if (!navigator.onLine) {
                    if (self.shouldCache(settings)) {
                        var key = cacheKeyFor(settings);
                        return self.getCachedResponse(key).then(function (cached) {
                            if (cached !== null && cached !== undefined) {
                                if (typeof settings.success === 'function') {
                                    settings.success(cached);
                                }
                                return $.Deferred().resolve(cached).promise();
                            }
                            return $.Deferred()
                                .reject({}, 'error', offlineText('offline_no_cached_data', 'No cached data for this view while offline.'))
                                .promise();
                        });
                    }
                    if (self.shouldQueue(settings)) {
                        return self.enqueue(settings);
                    }
                }

                if (self.shouldCache(settings)) {
                    var userSuccess = settings.success;
                    settings.success = function (data) {
                        self.setCachedResponse(cacheKeyFor(settings), data);
                        if (typeof userSuccess === 'function') {
                            userSuccess.apply(this, arguments);
                        }
                    };
                }

                return originalAjax.call($, settings);
            };
        },

        bindConnectivityEvents: function () {
            var self = this;
            window.addEventListener('offline', function () {
                self.updateBanner();
            });
            window.addEventListener('online', function () {
                self.updateBanner();
                self.syncQueue();
            });
        },

        syncQueue: function () {
            var self = this;
            if (!navigator.onLine || this.isSyncing || !this.db) {
                return $.Deferred().resolve().promise();
            }

            this.isSyncing = true;
            this.updateBanner();

            return this.getAllQueued()
                .then(function (items) {
                    var chain = Promise.resolve();
                    var conflicts = [];

                    items.forEach(function (item) {
                        chain = chain.then(function () {
                            return self.replayItem(item).catch(function (conflict) {
                                if (conflict) {
                                    conflicts.push(conflict);
                                }
                            });
                        });
                    });

                    return chain.then(function () {
                        return conflicts;
                    });
                })
                .then(function (conflicts) {
                    self.isSyncing = false;
                    return self.refreshCounts().then(function () {
                        self.updateBanner();
                        if (conflicts.length > 0) {
                            $(document).trigger('minical:sync-partial', [conflicts]);
                        } else if (self.pendingCount === 0 && self.conflictCount === 0) {
                            $(document).trigger('minical:sync-complete');
                            if (typeof innGrid !== 'undefined' && typeof innGrid.reloadBookings === 'function') {
                                innGrid.reloadBookings();
                            }
                        } else if (self.pendingCount === 0) {
                            $(document).trigger('minical:sync-complete-with-conflicts');
                        }
                    });
                })
                .catch(function () {
                    self.isSyncing = false;
                    self.updateBanner();
                });
        },

        replayItem: function (item) {
            var self = this;
            return new Promise(function (resolve, reject) {
                $.ajax({
                    url: item.url,
                    type: item.method,
                    data: item.data,
                    contentType: item.contentType,
                    processData: item.processData,
                    dataType: item.dataType,
                    headers: item.headers || undefined
                })
                    .done(function (data, textStatus, jqXHR) {
                        if (self.isServerRejection(data)) {
                            self.moveToConflict(item, jqXHR || { status: 200, responseJSON: data })
                                .then(function (c) {
                                    reject(c);
                                });
                            return;
                        }
                        self.deleteQueueItem(item.id).then(resolve).catch(resolve);
                    })
                    .fail(function (jqXHR) {
                        var status = jqXHR.status || 0;
                        var isConflict = status === 409 || status === 422 || status === 400 || status === 403;

                        item.retries = (item.retries || 0) + 1;

                        if (isConflict || item.retries >= 5) {
                            self.moveToConflict(item, jqXHR).then(function (c) {
                                reject(c);
                            });
                            return;
                        }

                        self.putQueueItem(item, true).then(function () {
                            reject(null);
                        });
                    });
            });
        },

        retryConflict: function (id) {
            var self = this;
            return new Promise(function (resolve, reject) {
                var store = self.dbTransaction(STORE_CONFLICTS, 'readonly');
                var request = store.get(id);
                request.onsuccess = function () {
                    var conflict = request.result;
                    if (!conflict) {
                        reject(new Error('not found'));
                        return;
                    }
                    var queueItem = {
                        id: conflict.id,
                        url: conflict.url,
                        method: conflict.method,
                        data: conflict.data,
                        contentType: conflict.contentType,
                        processData: conflict.processData,
                        dataType: conflict.dataType,
                        headers: conflict.headers,
                        createdAt: Date.now(),
                        retries: 0,
                        status: 'pending',
                        label: conflict.label,
                        summary: conflict.summary
                    };
                    self.deleteConflict(id)
                        .then(function () {
                            return self.putQueueItem(queueItem);
                        })
                        .then(function () {
                            self.updateBanner();
                            if (navigator.onLine) {
                                return self.syncQueue();
                            }
                            resolve(queueItem);
                        })
                        .catch(reject);
                };
                request.onerror = function () {
                    reject(request.error);
                };
            });
        },

        dismissConflict: function (id) {
            var self = this;
            return this.deleteConflict(id).then(function () {
                self.updateBanner();
                $(document).trigger('minical:conflict-dismissed', [id]);
            });
        },

        discardQueueItem: function (id) {
            var self = this;
            return this.deleteQueueItem(id).then(function () {
                self.updateBanner();
            });
        },

        getSyncStatus: function () {
            var self = this;
            return Promise.all([this.getAllQueued(), this.getAllConflicts()]).then(function (results) {
                return {
                    online: navigator.onLine,
                    isSyncing: self.isSyncing,
                    pending: results[0],
                    conflicts: results[1]
                };
            });
        },

        registerServiceWorker: function () {
            if (!('serviceWorker' in navigator)) {
                return;
            }
            var swUrl = window.MINICAL_SW_URL || (getBaseURL() + 'sw.js');
            window.addEventListener('load', function () {
                navigator.serviceWorker.register(swUrl, { scope: getBaseURL() }).catch(function (err) {
                    console.warn('Service worker registration failed', err);
                });
            });
        },

        renderBanner: function () {
            if (this.bannerEl) {
                return;
            }
            var el = document.createElement('div');
            el.id = 'minical-offline-banner';
            el.className = 'minical-offline-banner hidden';
            el.setAttribute('role', 'status');
            el.innerHTML =
                '<span class="minical-offline-banner__icon" aria-hidden="true">&#9888;</span>' +
                '<span class="minical-offline-banner__text"></span>' +
                '<a href="#" class="minical-offline-banner__details btn btn-sm btn-light hidden">' +
                    offlineText('offline_view_sync_status', 'View sync status') +
                '</a>' +
                '<button type="button" class="minical-offline-banner__sync btn btn-sm btn-light hidden">' +
                    offlineText('offline_sync_now', 'Sync now') +
                '</button>';
            document.body.appendChild(el);
            this.bannerEl = el;

            var self = this;
            el.querySelector('.minical-offline-banner__sync').addEventListener('click', function () {
                self.syncQueue();
            });
            el.querySelector('.minical-offline-banner__details').addEventListener('click', function (e) {
                e.preventDefault();
                window.location.href = getBaseURL() + 'offline_sync';
            });
        },

        updateBanner: function () {
            if (!this.bannerEl) {
                return;
            }
            var textEl = this.bannerEl.querySelector('.minical-offline-banner__text');
            var syncBtn = this.bannerEl.querySelector('.minical-offline-banner__sync');
            var detailsBtn = this.bannerEl.querySelector('.minical-offline-banner__details');
            var online = navigator.onLine;
            var pending = this.pendingCount;
            var conflicts = this.conflictCount;

            if (!online) {
                this.bannerEl.classList.remove('hidden');
                this.bannerEl.classList.add('minical-offline-banner--offline');
                textEl.textContent = offlineText(
                    'offline_mode_active',
                    'You are offline. Changes are saved on this device and will sync when connection returns.'
                );
                syncBtn.classList.add('hidden');
                detailsBtn.classList.remove('hidden');
                return;
            }

            if (pending > 0 || conflicts > 0 || this.isSyncing) {
                this.bannerEl.classList.remove('hidden');
                this.bannerEl.classList.remove('minical-offline-banner--offline');
                if (this.isSyncing) {
                    textEl.textContent = offlineText('offline_sync_in_progress', 'Syncing offline changes…');
                } else if (conflicts > 0 && pending > 0) {
                    textEl.textContent =
                        offlineText('offline_pending_and_conflicts', 'Pending sync and conflicts need review.') +
                        ' (' + pending + ' / ' + conflicts + ')';
                } else if (conflicts > 0) {
                    textEl.textContent =
                        offlineText('offline_conflicts_need_review', 'Some changes conflicted with the server.') +
                        ' (' + conflicts + ')';
                } else {
                    textEl.textContent =
                        offlineText('offline_pending_changes', 'You have offline changes waiting to sync.') +
                        ' (' + pending + ')';
                }
                syncBtn.classList.toggle('hidden', pending === 0);
                detailsBtn.classList.remove('hidden');
                return;
            }

            this.bannerEl.classList.add('hidden');
        }
    };

    window.MiniCalOffline = MiniCalOffline;
    window.minicalIsOfflineQueued = function (response) {
        if (typeof response === 'string') {
            try {
                response = JSON.parse(response);
            } catch (e) {
                return false;
            }
        }
        return !!(response && response.offline && response.queued);
    };

    $(function () {
        if ($('#project_url').length) {
            MiniCalOffline.init();
        }

        $(document).on('minical:offline-queued', function () {
            var msg = offlineText(
                'offline_change_queued',
                'Saved offline. Changes will sync when you are back online.'
            );
            if ($('#reservation-message').length) {
                $('#reservation-message .message').html(msg);
                $('#reservation-message').modal('show');
            } else if (typeof alert === 'function') {
                alert(msg);
            }
        });

        $(document).on('minical:sync-complete', function () {
            var msg = offlineText('offline_sync_complete', 'Offline changes have been synced.');
            if ($('#reservation-message').length) {
                $('#reservation-message .message').html(msg);
                $('#reservation-message').modal('show');
            }
        });

        $(document).on('minical:sync-conflict', function (e, conflict) {
            var msg = offlineText('offline_sync_conflict', 'A change could not be synced: ') +
                (conflict.lastError || '');
            if ($('#reservation-message').length) {
                $('#reservation-message .message').html(msg);
                $('#reservation-message').modal('show');
            }
        });
    });
})(window, window.jQuery);
