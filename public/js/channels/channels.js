$(function () {
    var $websiteToggle = $('#website-online-reservations-toggle');
    var $statusBadge = $('#website-channel-status-badge');

    function updateWebsiteStatusBadge(enabled) {
        if (!$statusBadge.length) {
            return;
        }

        var labelOn = $websiteToggle.data('label-on') || 'Connected';
        var labelOff = $websiteToggle.data('label-off') || 'Not connected';

        $statusBadge
            .removeClass('channel-badge--ok channel-badge--muted')
            .addClass('channel-badge--' + (enabled ? 'ok' : 'muted'))
            .text(enabled ? labelOn : labelOff);
    }

    $websiteToggle.on('change', function () {
        var enabled = $(this).is(':checked') ? 1 : 0;
        var previousChecked = !enabled;

        $websiteToggle.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: getBaseURL() + 'channels/update_website_online_reservations_AJAX',
            data: { enabled: enabled },
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (resp) {
                if (resp && resp.success) {
                    $websiteToggle.prop('checked', !!resp.enabled);
                    updateWebsiteStatusBadge(!!resp.enabled);
                } else {
                    $websiteToggle.prop('checked', previousChecked);
                    updateWebsiteStatusBadge(previousChecked);
                    alert((resp && resp.message) ? resp.message : 'Could not update setting.');
                }
            },
            error: function (xhr) {
                $websiteToggle.prop('checked', previousChecked);
                updateWebsiteStatusBadge(previousChecked);
                var message = 'Could not update setting.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseText && xhr.responseText.indexOf('{') === 0) {
                    try {
                        var parsed = JSON.parse(xhr.responseText);
                        if (parsed.message) {
                            message = parsed.message;
                        }
                    } catch (e) {
                        // keep default message
                    }
                }
                alert(message);
            },
            complete: function () {
                $websiteToggle.prop('disabled', false);
            }
        });
    });

    $('#copy-booking-engine-url').on('click', function () {
        var input = document.getElementById('booking-engine-url');
        input.select();
        input.setSelectionRange(0, 99999);
        try {
            document.execCommand('copy');
            alert('URL copied');
        } catch (e) {
            alert(input.value);
        }
    });

    $('.channel-ical-form').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        var channelKey = $form.data('channel-key') || $form.find('input[name="channel_key"]').val();

        $.ajax({
            type: 'POST',
            url: getBaseURL() + 'channels/save_ical_AJAX',
            data: $form.serialize(),
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (resp) {
                alert(resp.message || (resp.success ? 'Saved' : 'Error'));
                if (resp.success) {
                    window.location.reload();
                }
            },
            error: function () {
                alert('Could not save channel settings.');
            }
        });
    });

    $('.sync-ical-channel').on('click', function () {
        var $btn = $(this);
        var channelKey = $btn.data('channel-key');
        var syncLabel = $btn.data('sync-label') || 'Sync import now';
        $btn.prop('disabled', true).text('Syncing...');

        $.ajax({
            type: 'POST',
            url: getBaseURL() + 'channels/sync_ical_AJAX',
            data: { channel_key: channelKey },
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (resp) {
                alert(resp.message || 'Sync finished');
                if (resp.success) {
                    window.location.reload();
                }
            },
            error: function () {
                alert('Sync failed.');
            },
            complete: function () {
                $btn.prop('disabled', false).text(syncLabel);
            }
        });
    });

    $('.regenerate-export-token').on('click', function () {
        var roomTypeId = $(this).data('room-type-id');
        var channelKey = $(this).data('channel-key');
        var $card = $(this).closest('.channel-card');
        var $row = $card.find('tr[data-room-type-id="' + roomTypeId + '"]');

        $.ajax({
            type: 'POST',
            url: getBaseURL() + 'channels/regenerate_export_token_AJAX',
            data: {
                room_type_id: roomTypeId,
                channel_key: channelKey
            },
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (resp) {
                if (resp.success && resp.export_url) {
                    $row.find('.export-url-field').val(resp.export_url);
                    $row.find('input[name="mappings[' + roomTypeId + '][export_enabled]"]').prop('checked', true);
                    alert('Export link updated. Paste it in the OTA calendar import settings.');
                } else {
                    alert('Could not regenerate export link.');
                }
            },
            error: function () {
                alert('Could not regenerate export link.');
            }
        });
    });

    // Store original sync button labels for restore after sync
    $('.sync-ical-channel').each(function () {
        $(this).data('sync-label', $(this).text());
    });
});
