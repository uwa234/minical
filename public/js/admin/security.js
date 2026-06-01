(function ($) {
    'use strict';

    function baseUrl() {
        return typeof window.base_url !== 'undefined' ? window.base_url : '/';
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
        }).done(function (resp) {
            if (resp && resp.success) {
                if (onSuccess) {
                    onSuccess(resp);
                }
            } else {
                if (onError) {
                    onError((resp && resp.error) ? resp.error : 'Request failed.');
                }
            }
        }).fail(function (xhr) {
            var msg = 'Request failed.';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
            }
            if (onError) {
                onError(msg);
            }
        }).always(function () {
            if ($btn) {
                $btn.prop('disabled', false);
            }
        });
    }

    $('.pa-revoke-admin-btn').on('click', function () {
        var $btn = $(this);
        var adminId = $btn.data('platform-admin-id');
        var email = $btn.data('admin-email');
        if (!window.confirm('Revoke platform admin access for ' + email + '?')) {
            return;
        }
        postAdminJson(
            baseUrl() + 'admin/revoke_platform_admin/',
            { platform_admin_id: adminId },
            function () {
                window.location.reload();
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });

    $('#pa-start-2fa-btn').on('click', function () {
        var $btn = $(this);
        postAdminJson(
            baseUrl() + 'admin/start_2fa_setup/',
            {},
            function () {
                window.location.href = baseUrl() + 'admin/security#two-factor';
                window.location.reload();
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });

    $('#pa-confirm-2fa-btn').on('click', function () {
        var $btn = $(this);
        var code = $.trim($('#pa-confirm-2fa-code').val());
        if (!/^\d{6}$/.test(code)) {
            alert('Enter a valid 6-digit code.');
            return;
        }
        postAdminJson(
            baseUrl() + 'admin/confirm_2fa_setup/',
            { totp_code: code },
            function () {
                window.location.reload();
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });

    $('#pa-disable-2fa-btn').on('click', function () {
        var $btn = $(this);
        var code = $.trim($('#pa-disable-2fa-code').val());
        if (!/^\d{6}$/.test(code)) {
            alert('Enter a valid 6-digit code.');
            return;
        }
        if (!window.confirm('Disable two-factor authentication for your account?')) {
            return;
        }
        postAdminJson(
            baseUrl() + 'admin/disable_2fa/',
            { totp_code: code },
            function () {
                window.location.reload();
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });
}(jQuery));
