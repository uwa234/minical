(function ($) {
    'use strict';

    function getBaseURLSafe() {
        if (typeof getBaseURL === 'function') {
            return getBaseURL();
        }
        return '/';
    }

    function setButtonState($button, isLoading) {
        if (!$button || !$button.length) {
            return;
        }
        $button.prop('disabled', isLoading);
        if (isLoading) {
            $button.data('original-text', $button.text());
            $button.text('Redirecting...');
            return;
        }
        var original = $button.data('original-text');
        if (original) {
            $button.text(original);
        }
    }

    $(function () {
        $(document).on('click', '.js-paystack-subscribe', function () {
            var $button = $(this);
            var tierId = parseInt($button.data('tier-id'), 10);
            var renewalPeriod = $button.data('renewal-period') || 'month';

            if (!tierId) {
                window.alert('Invalid plan selected.');
                return;
            }

            setButtonState($button, true);

            $.ajax({
                url: getBaseURLSafe() + 'account_settings/paystack_checkout',
                method: 'POST',
                dataType: 'json',
                data: {
                    tier_id: tierId,
                    renewal_period: renewalPeriod
                }
            }).done(function (response) {
                if (!response || !response.success || !response.authorization_url) {
                    window.alert(response && response.error ? response.error : 'Could not start payment.');
                    return;
                }
                window.location.href = response.authorization_url;
            }).fail(function (xhr) {
                var message = 'Could not start payment.';
                if (xhr && xhr.responseJSON && xhr.responseJSON.error) {
                    message = xhr.responseJSON.error;
                }
                window.alert(message);
            }).always(function () {
                setButtonState($button, false);
            });
        });
    });
}(jQuery));
