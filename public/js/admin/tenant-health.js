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

    function showBulkResult(message, isError) {
        var $el = $('#bulk-action-result');
        $el.removeClass('pa-bulk-result--error pa-bulk-result--success');
        $el.addClass(isError ? 'pa-bulk-result--error' : 'pa-bulk-result--success');
        $el.text(message);
    }

    function selectedCompanyIds() {
        var ids = [];
        $('.tenant-health-select:checked').each(function () {
            ids.push(parseInt($(this).val(), 10));
        });
        return ids;
    }

    $('#tenant-health-select-all').on('change', function () {
        $('.tenant-health-select').prop('checked', $(this).is(':checked'));
    });

    $('#bulk-extend-submit').on('click', function () {
        var $btn = $(this);
        var scope = $('#bulk-extend-scope').val();
        var days = parseInt($('#bulk-extend-days').val(), 10) || 7;
        if (!window.confirm('Extend trials for the selected scope by ' + days + ' days?')) {
            return;
        }
        postAdminJson(
            baseUrl() + 'admin/bulk_extend_trials/',
            { scope: scope, grace_days: days },
            function (response) {
                showBulkResult('Extended ' + response.updated + ' of ' + response.total + ' trials.' + (response.failed ? ' (' + response.failed + ' failed)' : ''));
            },
            function (msg) { showBulkResult(msg, true); },
            $btn
        );
    });

    $('#bulk-email-submit').on('click', function () {
        var $btn = $(this);
        var subject = $.trim($('#bulk-email-subject').val());
        var body = $.trim($('#bulk-email-body').val());
        if (!subject || !body) {
            alert('Subject and message are required.');
            return;
        }
        if (!window.confirm('Send this email to all tenants in the selected scope?')) {
            return;
        }
        postAdminJson(
            baseUrl() + 'admin/bulk_email_tenants/',
            { scope: $('#bulk-email-scope').val(), subject: subject, body: body },
            function (response) {
                showBulkResult('Sent ' + response.sent + ' emails (' + response.skipped + ' skipped).');
            },
            function (msg) { showBulkResult(msg, true); },
            $btn
        );
    });

    $('#bulk-tier-submit').on('click', function () {
        var $btn = $(this);
        var scope = $('#bulk-tier-scope').val();
        var tierId = parseInt($('#bulk-tier-id').val(), 10);
        var data = { scope: scope, tier_id: tierId };
        if (scope === 'selected') {
            data.company_ids = selectedCompanyIds();
            if (!data.company_ids.length) {
                alert('Select at least one property in the table.');
                return;
            }
        }
        if (!window.confirm('Apply the selected pricing tier to the chosen scope?')) {
            return;
        }
        postAdminJson(
            baseUrl() + 'admin/bulk_update_tiers/',
            data,
            function (response) {
                showBulkResult('Updated ' + response.updated + ' tenants to tier "' + response.tier_name + '".');
            },
            function (msg) { showBulkResult(msg, true); },
            $btn
        );
    });
});
