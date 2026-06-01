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

    $('#pa-test-webhook').on('click', function () {
        var $btn = $(this);
        var $result = $('#pa-webhook-test-result');
        $btn.prop('disabled', true);
        $result.removeClass('pa-inline-result--error pa-inline-result--success').text('Sending…');

        $.ajax({
            type: 'POST',
            url: baseUrl() + 'admin/test_webhook/',
            dataType: 'json'
        }).done(function (response) {
            if (response && response.success) {
                $result.addClass('pa-inline-result--success').text('Test notification sent.');
                return;
            }
            $result.addClass('pa-inline-result--error').text((response && response.error) ? response.error : 'Test failed.');
        }).fail(function (xhr) {
            $result.addClass('pa-inline-result--error').text(ajaxErrorMessage(xhr, 'Test failed.'));
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
});
