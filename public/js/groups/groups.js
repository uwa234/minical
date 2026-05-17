(function ($) {
    'use strict';

    var baseUrl = typeof getBaseURL === 'function' ? getBaseURL() : '/';

    function showMessage(msg) {
        if (typeof alert !== 'undefined') {
            alert(msg);
        }
    }

  $(function () {
        if (window.location.hash === '#tab-blocks' || /[?&]tab=blocks/.test(window.location.search)) {
            $('a[href="#tab-blocks"]').tab('show');
        }

        $('#add-block-line').on('click', function () {
            var $line = $('#block-lines .block-line').first().clone();
            $line.find('input').val(1);
            $('#block-lines').append($line);
        });

        $('#create-block-form').on('submit', function (e) {
            e.preventDefault();
            var lines = [];
            $('#block-lines .block-line').each(function () {
                var rt = $(this).find('select').val();
                var qty = $(this).find('input[type="number"]').val();
                if (rt && qty) {
                    lines.push({ room_type_id: rt, quantity: qty });
                }
            });

            $.post(baseUrl + 'groups/create_block_AJAX', {
                name: $(this).find('[name="name"]').val(),
                check_in_date: $(this).find('[name="check_in_date"]').val(),
                check_out_date: $(this).find('[name="check_out_date"]').val(),
                cutoff_date: $(this).find('[name="cutoff_date"]').val(),
                release_date: $(this).find('[name="release_date"]').val(),
                notes: $(this).find('[name="notes"]').val(),
                lines_json: JSON.stringify(lines)
            }, function (res) {
                if (res.success) {
                    window.location.reload();
                } else {
                    showMessage('Could not create block.');
                }
            }, 'json');
        });

        $('#blocks-table').on('click', '.btn-view-block', function () {
            var blockId = $(this).closest('tr').data('block-id');
            $.getJSON(baseUrl + 'groups/block_detail_AJAX/' + blockId, function (res) {
                if (!res.success) {
                    return;
                }
                var b = res.block;
                var html = '<h4>' + b.name + '</h4><p>' + b.check_in_date + ' – ' + b.check_out_date + '</p>';
                html += '<table class="table table-condensed"><thead><tr><th>Room type</th><th>Qty</th><th>Picked up</th><th>Remaining</th><th></th></tr></thead><tbody>';
                $.each(b.lines || [], function (i, line) {
                    html += '<tr><td>' + (line.room_type_name || '') + '</td><td>' + line.quantity + '</td><td>' + line.picked_up + '</td><td>' + line.remaining + '</td><td>';
                    if (line.remaining > 0 && b.status === 'active' && !b.past_cutoff) {
                        html += '<button class="btn btn-xs btn-success btn-pickup-line" data-line-id="' + line.id + '">Pick up</button>';
                    }
                    html += '</td></tr>';
                });
                html += '</tbody></table>';
                $('#block-detail-panel').html(html).removeClass('hidden');
            });
        });

        $('#block-detail-panel').on('click', '.btn-pickup-line', function () {
            var lineId = $(this).data('line-id');
            $.post(baseUrl + 'groups/pickup_block_AJAX', { line_id: lineId }, function (res) {
                if (res.success) {
                    showMessage('Reservation #' + res.booking_id + ' (' + res.room_name + ')');
                    window.location.reload();
                } else {
                    showMessage(res.message || 'Pickup failed');
                }
            }, 'json');
        });

        $('#blocks-table').on('click', '.btn-release-block', function () {
            if (!confirm('Release this block?')) {
                return;
            }
            var blockId = $(this).closest('tr').data('block-id');
            $.post(baseUrl + 'groups/release_block_AJAX', { block_id: blockId }, function (res) {
                if (res.success) {
                    window.location.reload();
                }
            }, 'json');
        });

        var $detail = $('.groups-detail');
        if ($detail.length) {
            var groupId = $detail.data('group-id');

            $('#save-billing-settings').on('click', function () {
                $.post(baseUrl + 'groups/update_billing_settings_AJAX', {
                    group_id: groupId,
                    billing_mode: $('#group-billing-mode').val(),
                    route_existing: $('#route-existing-charges').is(':checked') ? '1' : '0'
                }, function (res) {
                    if (res.success) {
                        showMessage('Billing settings saved.');
                    }
                }, 'json');
            });

            $('#add-unassigned-slots').on('click', function () {
                $.post(baseUrl + 'groups/add_unassigned_slots_AJAX', {
                    group_id: groupId,
                    room_type_id: $('#unassigned-room-type').val(),
                    count: $('#unassigned-count').val()
                }, function (res) {
                    if (res.success) {
                        window.location.reload();
                    }
                }, 'json');
            });

            $('#rooming-table').on('click', '.btn-save-rooming', function () {
                var $row = $(this).closest('tr');
                $.post(baseUrl + 'groups/save_rooming_entry_AJAX', {
                    group_id: groupId,
                    entry_id: $row.data('entry-id') || '',
                    booking_id: $row.data('booking-id') || '',
                    guest_name: $row.find('.rooming-guest').val()
                }, function (res) {
                    if (res.success) {
                        window.location.reload();
                    }
                }, 'json');
            });

            $('#rooming-table').on('click', '.btn-delete-slot', function () {
                var $row = $(this).closest('tr');
                $.post(baseUrl + 'groups/delete_rooming_entry_AJAX', {
                    group_id: groupId,
                    entry_id: $row.data('entry-id')
                }, function (res) {
                    if (res.success) {
                        $row.remove();
                    }
                }, 'json');
            });

            $('#rooming-table').on('click', '.open-booking', function (e) {
                e.preventDefault();
                var bookingId = $(this).data('booking-id');
                if (typeof bookingModalInvoker !== 'undefined' && bookingId) {
                    bookingModalInvoker.open({ booking_id: bookingId });
                }
            });
        }

        $('#group-billing-mode').on('change', function () {
            var groupId = $('#group_id').val();
            if (!groupId) {
                return;
            }
            $.post(baseUrl + 'groups/update_billing_settings_AJAX', {
                group_id: groupId,
                billing_mode: $(this).val(),
                route_existing: '0'
            });
        });
    });
}(jQuery));
