(function ($) {
    'use strict';

    function parseJson(res) {
        return typeof res === 'string' ? JSON.parse(res) : res;
    }

    function showSettingsMessage(html) {
        $('#whatsapp-settings-message').html(html);
    }

    function initWhatsappSettings() {
        var base = $('#whatsapp-settings-root').data('base-url');
        if (!base) {
            return;
        }

        $('#whatsapp-settings-form').on('submit', function (e) {
            e.preventDefault();
            var $msg = $('#whatsapp-settings-message');
            $.post(base + 'whatsapp/save_settings', $(this).serialize())
                .done(function (res) {
                    res = parseJson(res);
                    showSettingsMessage(
                        '<div class="alert alert-' + (res.success ? 'success' : 'danger') + '">' +
                            (res.message || '') +
                            '</div>'
                    );
                })
                .fail(function () {
                    showSettingsMessage(
                        '<div class="alert alert-danger">Could not save settings. Please try again.</div>'
                    );
                });
        });

        $('#whatsapp-test-connection').on('click', function () {
            $.post(base + 'whatsapp/test_connection', {})
                .done(function (res) {
                    res = parseJson(res);
                    showSettingsMessage(
                        '<div class="alert alert-' + (res.success ? 'success' : 'danger') + '">' +
                            (res.message || '') +
                            '</div>'
                    );
                })
                .fail(function () {
                    showSettingsMessage(
                        '<div class="alert alert-danger">Connection test failed. Please try again.</div>'
                    );
                });
        });

        $('#whatsapp-simulate-form').on('submit', function (e) {
            e.preventDefault();
            var $btn = $('#whatsapp-simulate-send');
            var $wrap = $('#whatsapp-simulate-reply-wrap');
            var $reply = $('#whatsapp-simulate-reply');
            var $err = $('#whatsapp-simulate-error');

            $err.hide().empty();
            $wrap.hide();
            $btn.prop('disabled', true).text($btn.data('sending-label') || 'Sending…');

            $.ajax({
                url: base + 'whatsapp/simulate',
                type: 'POST',
                data: $(this).serialize(),
                timeout: 60000,
            })
                .done(function (res) {
                    try {
                        res = parseJson(res);
                    } catch (parseErr) {
                        $err.html('Unexpected server response. Please refresh and try again.').show();
                        return;
                    }
                    if (res.success && res.reply) {
                        $reply.text(res.reply);
                        $wrap.show();
                    } else if (res.message) {
                        $err.html(res.message).show();
                    } else {
                        $err.html('No reply from the bot. Try hello or book.').show();
                    }
                })
                .fail(function (xhr, textStatus) {
                    var msg = textStatus === 'timeout'
                        ? 'Request timed out while checking availability. Please try again.'
                        : 'Could not reach the simulator.';
                    if (xhr.responseText) {
                        try {
                            var err = parseJson(xhr.responseText);
                            if (err.message) {
                                msg = err.message;
                            }
                        } catch (ignore) {
                            /* use default */
                        }
                    }
                    $err.html(msg).show();
                })
                .always(function () {
                    $btn.prop('disabled', false).text($btn.data('default-label') || 'Send');
                });
        });
    }

    $(initWhatsappSettings);
}(jQuery));
