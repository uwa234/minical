(function ($) {
    'use strict';

    $(function () {
        $('.trial-registration-status').on('change', function () {
            var $select = $(this);
            var requestId = $select.data('request-id');
            var status = $select.val();
            var previous = $select.data('previous-status') || $select.find('option:selected').val();

            $select.prop('disabled', true);

            $.ajax({
                type: 'POST',
                url: getBaseURL() + 'admin/update_trial_registration_status',
                dataType: 'json',
                data: {
                    request_id: requestId,
                    status: status
                },
                success: function (response) {
                    if (response && response.success) {
                        $select.data('previous-status', status);
                    } else {
                        alert((response && response.error) ? response.error : 'Could not update status.');
                        $select.val(previous);
                    }
                },
                error: function () {
                    alert('Could not update status. Please try again.');
                    $select.val(previous);
                },
                complete: function () {
                    $select.prop('disabled', false);
                }
            });
        });

        $('.trial-registration-status').each(function () {
            $(this).data('previous-status', $(this).val());
        });

        $('.trial-send-outreach-btn').on('click', function () {
            var $btn = $(this);
            var requestId = $btn.data('request-id');

            if (!window.confirm('Send trial outreach email to this lead?')) {
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                type: 'POST',
                url: getBaseURL() + 'admin/send_trial_lead_email',
                dataType: 'json',
                data: { request_id: requestId },
                success: function (response) {
                    if (response && response.success) {
                        alert('Outreach email sent to ' + response.recipient + '.');
                        window.location.reload();
                        return;
                    }
                    alert((response && response.error) ? response.error : 'Could not send email.');
                },
                error: function (xhr) {
                    var msg = 'Could not send email.';
                    try {
                        var body = JSON.parse(xhr.responseText);
                        if (body && body.error) {
                            msg = body.error;
                        }
                    } catch (e) {}
                    alert(msg);
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });

        $('.trial-create-property-btn').on('click', function () {
            var $btn = $(this);
            if (typeof window.saasOpenAddPropertyModal !== 'function') {
                alert('Property creation is unavailable. Please refresh the page.');
                return;
            }

            window.saasOpenAddPropertyModal({
                trial_registration_request_id: $btn.data('request-id'),
                name: $btn.data('property-name'),
                number_of_rooms: $btn.data('number-of-rooms'),
                owner_email: $btn.data('owner-email'),
                owner_first_name: $btn.data('owner-first-name'),
                owner_last_name: $btn.data('owner-last-name'),
                subscription_state: 'trialing',
                trial_days: window.saasDefaultTrialDays || 14
            });
        });

        if (window.saasOpenTrialLeadId) {
            var $leadRow = $('tr[data-request-id="' + window.saasOpenTrialLeadId + '"]');
            var $leadBtn = $leadRow.find('.trial-create-property-btn');
            if ($leadBtn.length) {
                $leadBtn.trigger('click');
            }
        }
    });
}(jQuery));
