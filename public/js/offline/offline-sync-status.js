(function ($) {
    'use strict';

    function t(key, fallback) {
        if (typeof l === 'function') {
            var v = l(key, true);
            if (v && v !== key) {
                return v;
            }
        }
        return fallback;
    }

    function formatTime(ts) {
        if (!ts) {
            return '—';
        }
        try {
            return new Date(ts).toLocaleString();
        } catch (e) {
            return String(ts);
        }
    }

    function escapeHtml(str) {
        return $('<div/>').text(str || '').html();
    }

    var OfflineSyncPage = {
        init: function () {
            var self = this;
            if (!window.MiniCalOffline) {
                $('#offline-sync-connection').text(t('offline_sync_unavailable', 'Offline sync is not available in this browser.'));
                return;
            }

            $('#offline-sync-refresh').on('click', function () {
                self.render();
            });
            $('#offline-sync-run').on('click', function () {
                MiniCalOffline.syncQueue().then(function () {
                    self.render();
                });
            });

            $(document).on('minical:offline-queued minical:sync-complete minical:sync-conflict minical:sync-partial minical:conflict-dismissed', function () {
                self.render();
            });

            window.addEventListener('online', function () {
                self.render();
            });
            window.addEventListener('offline', function () {
                self.render();
            });

            this.render();
        },

        render: function () {
            var self = this;
            return MiniCalOffline.getSyncStatus().then(function (status) {
                self.renderConnection(status);
                self.renderPending(status.pending);
                self.renderConflicts(status.conflicts);
            });
        },

        renderConnection: function (status) {
            var $badge = $('#offline-sync-connection');
            $badge.removeClass('offline-sync-badge--online offline-sync-badge--offline offline-sync-badge--unknown');
            if (status.isSyncing) {
                $badge.addClass('offline-sync-badge--unknown').text(t('offline_sync_in_progress', 'Syncing…'));
            } else if (status.online) {
                $badge.addClass('offline-sync-badge--online').text(t('offline_sync_online', 'Online'));
            } else {
                $badge.addClass('offline-sync-badge--offline').text(t('offline_sync_offline', 'Offline'));
            }
        },

        renderPending: function (items) {
            var $tbody = $('#offline-sync-pending-table tbody');
            var $empty = $('#offline-sync-pending-empty');
            $tbody.empty();

            if (!items.length) {
                $empty.removeClass('hidden');
                return;
            }
            $empty.addClass('hidden');

            items.forEach(function (item) {
                var $tr = $('<tr/>');
                $tr.append($('<td/>').text(item.label || item.url));
                $tr.append($('<td/>').text(item.summary || '—'));
                $tr.append($('<td/>').text(formatTime(item.createdAt)));
                var $actions = $('<td class="offline-sync-actions"/>');
                $actions.append(
                    $('<button type="button" class="btn btn-xs btn-danger"/>')
                        .text(t('offline_sync_discard', 'Discard'))
                        .on('click', function () {
                            if (confirm(t('offline_sync_discard_confirm', 'Discard this queued change?'))) {
                                MiniCalOffline.discardQueueItem(item.id).then(function () {
                                    OfflineSyncPage.render();
                                });
                            }
                        })
                );
                $tr.append($actions);
                $tbody.append($tr);
            });
        },

        renderConflicts: function (items) {
            var $tbody = $('#offline-sync-conflicts-table tbody');
            var $empty = $('#offline-sync-conflicts-empty');
            $tbody.empty();

            if (!items.length) {
                $empty.removeClass('hidden');
                return;
            }
            $empty.addClass('hidden');

            items.forEach(function (item) {
                var $tr = $('<tr/>');
                $tr.append($('<td/>').text(item.label || item.url));
                $tr.append($('<td class="offline-sync-error"/>').html(escapeHtml(item.lastError)));
                $tr.append($('<td/>').text(formatTime(item.failedAt)));
                var $actions = $('<td class="offline-sync-actions"/>');
                $actions.append(
                    $('<button type="button" class="btn btn-xs btn-primary"/>')
                        .text(t('offline_sync_retry', 'Retry'))
                        .on('click', function () {
                            MiniCalOffline.retryConflict(item.id).then(function () {
                                OfflineSyncPage.render();
                            });
                        })
                );
                $actions.append(' ');
                $actions.append(
                    $('<button type="button" class="btn btn-xs btn-default"/>')
                        .text(t('offline_sync_dismiss', 'Dismiss'))
                        .on('click', function () {
                            if (confirm(t('offline_sync_dismiss_confirm', 'Remove this conflict record?'))) {
                                MiniCalOffline.dismissConflict(item.id).then(function () {
                                    OfflineSyncPage.render();
                                });
                            }
                        })
                );
                $tr.append($actions);
                $tbody.append($tr);
            });
        }
    };

    $(function () {
        if ($('.offline-sync-page').length) {
            OfflineSyncPage.init();
        }
    });
})(jQuery);
