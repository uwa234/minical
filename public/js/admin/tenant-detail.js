$(function () {
    var $page = $('#tenant-detail-page');
    if (!$page.length) {
        return;
    }

    var companyId = parseInt($page.data('company-id'), 10);

    function baseUrl() {
        return typeof getBaseURL === 'function' ? getBaseURL() : ($('#project_url').val() || '/');
    }

    function parseAjaxJson(xhr) {
        if (!xhr || !xhr.responseText) {
            return null;
        }
        try {
            return JSON.parse(xhr.responseText);
        } catch (e) {
            return null;
        }
    }

    function ajaxErrorMessage(xhr, fallback) {
        var body = parseAjaxJson(xhr);
        if (body && body.error) {
            return body.error;
        }
        if (xhr && xhr.status === 403) {
            return 'Platform admin access required.';
        }
        return fallback;
    }

    function postAdminJson(url, data, onSuccess, onError, $btn) {
        if ($btn) {
            $btn.prop('disabled', true);
        }
        $.ajax({
            type: 'POST',
            url: url,
            dataType: 'json',
            data: data
        }).done(function (response) {
            if (response && (response.success === true || response.success === 'true')) {
                onSuccess(response);
                return;
            }
            onError((response && response.error) ? response.error : 'Request failed.');
        }).fail(function (xhr) {
            onError(ajaxErrorMessage(xhr, 'Request failed.'));
        }).always(function () {
            if ($btn) {
                $btn.prop('disabled', false);
            }
        });
    }

    $('#tenant-save-admin-comment').on('click', function () {
        var $btn = $(this);
        postAdminJson(
            baseUrl() + 'admin/save_admin_comment/',
            {
                company_id: companyId,
                admin_comment: $.trim($('#tenant-admin-comment').val())
            },
            function () {
                window.location.reload();
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });

    $('#tenant-add-activity-note').on('click', function () {
        var $btn = $(this);
        var note = $.trim($('#tenant-activity-note').val());
        if (!note) {
            alert('Enter a note first.');
            return;
        }

        postAdminJson(
            baseUrl() + 'admin/add_activity_note/',
            { company_id: companyId, note: note },
            function () {
                window.location.reload();
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });

    function pushSubscriptionUpdate(payload) {
        payload.company_id = companyId;
        $.ajax({
            type: 'POST',
            url: baseUrl() + 'admin/update_subscription/',
            dataType: 'json',
            data: payload
        }).done(function (response) {
            if (response && response.success) {
                window.location.reload();
                return;
            }
            alert((response && response.error) ? response.error : 'Subscription update failed.');
        }).fail(function (xhr) {
            alert(ajaxErrorMessage(xhr, 'Subscription update failed.'));
        });
    }

    $('#tenant-subscription-state').on('change', function () {
        pushSubscriptionUpdate({ subscription_state: $(this).val() });
    });

    $('#tenant-pricing-tier').on('change', function () {
        pushSubscriptionUpdate({ tier_id: $(this).val() });
    });

    $('#tenant-plan-expiry-save').on('click', function () {
        var $btn = $(this);
        var planExpiryDate = $.trim($('#tenant-plan-expiry-date').val());
        if (!planExpiryDate) {
            alert('Select a plan expiry date first.');
            return;
        }

        postAdminJson(
            baseUrl() + 'admin/update_plan_expiry/',
            { company_id: companyId, plan_expiry_date: planExpiryDate },
            function () {
                window.location.reload();
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });

    $('#tenant-trial-save').on('click', function () {
        var $btn = $(this);
        var days = parseInt($('#tenant-trial-days').val(), 10);
        if (!days || days < 1) {
            alert('Enter trial days (1–365).');
            return;
        }

        postAdminJson(
            baseUrl() + 'admin/update_trial/',
            { company_id: companyId, trial_days: days },
            function () {
                window.location.reload();
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });

    $('#tenant-record-payment').on('click', function () {
        var $btn = $(this);
        var amount = parseFloat($('#tenant-payment-amount').val(), 10);
        var tierId = parseInt($('#tenant-pricing-tier').val(), 10) || 0;

        if (!amount || amount <= 0) {
            alert('Enter a valid payment amount.');
            return;
        }

        postAdminJson(
            baseUrl() + 'admin/record_subscription_payment/',
            {
                company_id: companyId,
                amount: amount,
                renewal_period: $('#tenant-payment-period').val(),
                payment_date: $('#tenant-payment-date').val(),
                tier_id: tierId,
                notes: $.trim($('#tenant-payment-notes').val()),
                send_receipt: $('#tenant-payment-send-receipt').is(':checked') ? '1' : '0'
            },
            function (response) {
                var msg = 'Payment recorded. Subscription active until ' + response.expiration_date + '.';
                if (response.receipt_sent) {
                    msg += ' Receipt emailed to owner.';
                }
                if (response.receipt_url && window.confirm(msg + '\n\nOpen printable receipt now?')) {
                    window.open(response.receipt_url, '_blank');
                    window.location.reload();
                    return;
                }
                alert(msg);
                window.location.reload();
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });

    $('#tenant-extend-grace').on('click', function () {
        var $btn = $(this);
        var graceDays = parseInt($btn.data('grace-days'), 10) || 7;

        if (!window.confirm('Extend grace by ' + graceDays + ' day' + (graceDays === 1 ? '' : 's') + '?')) {
            return;
        }

        postAdminJson(
            baseUrl() + 'admin/extend_subscription_grace/',
            { company_id: companyId, grace_days: graceDays },
            function (response) {
                alert('Grace extended. New expiry: ' + response.new_expiry_date
                    + (response.grace_email_sent ? '. Owner notified by email.' : '') + '.');
                window.location.reload();
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });

    $('#tenant-send-reminder').on('click', function () {
        var $btn = $(this);

        if (!window.confirm('Send a payment reminder email to the property owner?')) {
            return;
        }

        postAdminJson(
            baseUrl() + 'admin/send_payment_reminder/',
            { company_id: companyId },
            function (response) {
                alert('Reminder sent to ' + response.recipient + '.');
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });

    $('#tenant-send-win-back').on('click', function () {
        var $btn = $(this);

        if (!window.confirm('Send a win-back email to the property owner?')) {
            return;
        }

        postAdminJson(
            baseUrl() + 'admin/send_win_back_email/',
            { company_id: companyId },
            function (response) {
                alert('Win-back email sent to ' + response.recipient + '.');
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });

    $('#tenant-mark-contacted').on('click', function () {
        var $btn = $(this);
        var note = window.prompt('Mark this property as contacted. Optional note:', '');

        if (note === null) {
            return;
        }

        postAdminJson(
            baseUrl() + 'admin/mark_debtor_contacted/',
            { company_id: companyId, note: $.trim(note) },
            function () {
                window.location.reload();
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });

    $('.tenant-email-receipt').on('click', function () {
        var $btn = $(this);
        var reference = $btn.data('reference');

        if (!reference) {
            return;
        }

        if (!window.confirm('Email receipt to the property owner?')) {
            return;
        }

        postAdminJson(
            baseUrl() + 'admin/send_subscription_receipt/',
            { company_id: companyId, reference: reference },
            function () {
                alert('Receipt emailed to property owner.');
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });

    $('.saas-restore-property-btn').on('click', function () {
        var $btn = $(this);
        var restoreCompanyId = $btn.data('company-id') || companyId;
        var companyName = $btn.data('company-name') || ('#' + restoreCompanyId);

        if (!window.confirm('Restore "' + companyName + '"?')) {
            return;
        }

        postAdminJson(
            baseUrl() + 'admin/restore_property/',
            { company_id: restoreCompanyId },
            function () {
                window.location.reload();
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });
});
