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
    });
}(jQuery));
