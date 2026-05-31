$(function () {
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

    $('.debtor-record-payment').on('click', function () {
        var $btn = $(this);
        var amount = parseFloat($btn.data('amount'), 10) || '';
        var tierId = parseInt($btn.data('tier-id'), 10) || 0;

        $('#debtor-payment-company-id').val($btn.data('company-id'));
        $('#debtor-payment-property-name').text($btn.data('property-name') || '');
        $('#debtor-payment-amount').val(amount > 0 ? amount : '');
        if (tierId > 0) {
            $('#debtor-payment-tier').val(String(tierId));
        }
        $('#debtor-payment-notes').val('');
        $('#debtor-record-payment-modal').modal('show');
    });

    $('#debtor-payment-submit').on('click', function () {
        var $btn = $(this);
        var companyId = parseInt($('#debtor-payment-company-id').val(), 10);
        var amount = parseFloat($('#debtor-payment-amount').val(), 10);

        if (!companyId || !amount || amount <= 0) {
            alert('Enter a valid payment amount.');
            return;
        }

        postAdminJson(
            baseUrl() + 'admin/record_subscription_payment/',
            {
                company_id: companyId,
                amount: amount,
                renewal_period: $('#debtor-payment-period').val(),
                payment_date: $('#debtor-payment-date').val(),
                tier_id: $('#debtor-payment-tier').val(),
                notes: $.trim($('#debtor-payment-notes').val())
            },
            function (response) {
                $('#debtor-record-payment-modal').modal('hide');
                alert('Payment recorded. Subscription active until ' + response.expiration_date + '.');
                window.location.reload();
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });

    $('.debtor-extend-grace').on('click', function () {
        var $btn = $(this);
        var name = $btn.data('property-name') || 'this property';
        var graceDays = parseInt($btn.data('grace-days'), 10) || 7;

        if (!window.confirm('Extend grace by ' + graceDays + ' day' + (graceDays === 1 ? '' : 's') + ' for ' + name + '?')) {
            return;
        }

        postAdminJson(
            baseUrl() + 'admin/extend_subscription_grace/',
            {
                company_id: $btn.data('company-id'),
                grace_days: graceDays
            },
            function (response) {
                alert('Grace extended. New expiry: ' + response.new_expiry_date + '.');
                window.location.reload();
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });

    $('.debtor-send-reminder').on('click', function () {
        var $btn = $(this);
        var name = $btn.data('property-name') || 'this property';

        if (!window.confirm('Send a payment reminder email for ' + name + '?')) {
            return;
        }

        postAdminJson(
            baseUrl() + 'admin/send_payment_reminder/',
            { company_id: $btn.data('company-id') },
            function (response) {
                alert('Reminder sent to ' + response.recipient + '.');
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });
});
