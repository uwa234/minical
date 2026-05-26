$(function () {
    var $editModal = $('#saas-edit-property-modal');
    var $assignModal = $('#saas-assign-owner-modal');

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
            return 'Platform admin access required. Sign in with a platform admin account at /admin/login.';
        }
        if (xhr && (xhr.status === 302 || xhr.status === 401)) {
            return 'Your session expired. Please sign in again.';
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

    $('#saas-add-property-submit').on('click', function () {
        var $btn = $(this);
        var name = $.trim($('[name="saas_property_name"]').val());
        if (!name) {
            alert('Property name is required.');
            return;
        }

        postAdminJson(
            baseUrl() + 'auth/create_company/',
            {
                name: name,
                number_of_rooms: $('[name="saas_number_of_rooms"]').val() || 1,
                region: $('[name="saas_region"]').val(),
                subscription_type: $('[name="saas_subscription_type"]').val(),
                subscription_state: $('[name="saas_subscription_state"]').val(),
                trial_days: $('[name="saas_trial_days"]').val(),
                created_by: 'admin',
                owner_email: $.trim($('[name="saas_owner_email"]').val()),
                owner_first_name: $('[name="saas_owner_first_name"]').val(),
                owner_last_name: $('[name="saas_owner_last_name"]').val()
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

    $('.saas-edit-property-btn').on('click', function () {
        var $btn = $(this);
        var companyId = $btn.data('company-id');
        $editModal.data('company-id', companyId);
        $editModal.find('[name="saas_edit_company_id"]').val(companyId);
        $('#saas-edit-property-name').text($btn.data('company-name'));
        $editModal.find('[name="saas_edit_property_name"]').val($btn.data('company-name'));
        $editModal.find('[name="saas_edit_property_email"]').val($btn.data('company-email') || '');
        $editModal.find('[name="saas_edit_number_of_rooms"]').val($btn.data('number-of-rooms') || 1);
        $editModal.modal('show');
    });

    $('#saas-edit-property-submit').on('click', function () {
        var $btn = $(this);
        var companyId = $editModal.data('company-id') || $editModal.find('[name="saas_edit_company_id"]').val();
        var name = $.trim($editModal.find('[name="saas_edit_property_name"]').val());
        if (!companyId) {
            alert('Property ID is missing. Close the dialog and open Edit again.');
            return;
        }
        if (!name) {
            alert('Property name is required.');
            return;
        }

        postAdminJson(
            baseUrl() + 'admin/update_property/',
            {
                company_id: companyId,
                name: name,
                email: $.trim($editModal.find('[name="saas_edit_property_email"]').val()),
                number_of_rooms: $editModal.find('[name="saas_edit_number_of_rooms"]').val()
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

    $('.saas-delete-property-btn').on('click', function () {
        var $btn = $(this);
        $('[name="saas_delete_company_id"]').val($btn.data('company-id'));
        $('#saas-delete-property-name').text($btn.data('company-name'));
        $('#saas_delete_confirm_name').val('');
        $('#saas-delete-property-modal').modal('show');
    });

    $('#saas-delete-property-submit').on('click', function () {
        var $btn = $(this);
        var companyId = $('[name="saas_delete_company_id"]').val();
        var confirmName = $.trim($('#saas_delete_confirm_name').val());
        var expectedName = $('#saas-delete-property-name').text();

        if (!confirmName || confirmName.toLowerCase() !== expectedName.toLowerCase()) {
            alert('Type the exact property name to confirm permanent deletion.');
            return;
        }

        if (!window.confirm('Permanently delete "' + expectedName + '" and all related data?')) {
            return;
        }

        postAdminJson(
            baseUrl() + 'admin/delete_property/',
            {
                company_id: companyId,
                confirm_name: confirmName
            },
            function () {
                $('#saas-delete-property-modal').modal('hide');
                $('tr[data-company-id="' + companyId + '"]').remove();
                if ($('#saas-property-table tbody tr').length === 0) {
                    window.location.reload();
                }
            },
            function (msg) {
                alert(msg);
            },
            $btn
        );
    });

    $('.saas-assign-owner-btn').on('click', function () {
        var $btn = $(this);
        var companyId = $btn.data('company-id');
        $assignModal.data('company-id', companyId);
        $assignModal.find('[name="saas_assign_company_id"]').val(companyId);
        $('#saas-assign-owner-property-name').text($btn.data('company-name'));
        $assignModal.find('[name="saas_assign_owner_email"]').val($btn.data('owner-email') || '');
        $assignModal.find('[name="saas_assign_owner_first_name"]').val($btn.data('owner-first-name') || '');
        $assignModal.find('[name="saas_assign_owner_last_name"]').val($btn.data('owner-last-name') || '');
        $assignModal.modal('show');
    });

    $('#saas-assign-owner-submit').on('click', function () {
        var $btn = $(this);
        var companyId = $assignModal.data('company-id') || $assignModal.find('[name="saas_assign_company_id"]').val();
        var email = $.trim($assignModal.find('[name="saas_assign_owner_email"]').val());
        if (!companyId) {
            alert('Property ID is missing. Close the dialog and open Assign owner again.');
            return;
        }
        if (!email) {
            alert('Owner email is required.');
            return;
        }

        postAdminJson(
            baseUrl() + 'admin/assign_owner/',
            {
                company_id: companyId,
                owner_email: email,
                owner_first_name: $assignModal.find('[name="saas_assign_owner_first_name"]').val(),
                owner_last_name: $assignModal.find('[name="saas_assign_owner_last_name"]').val()
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

    function pushSubscriptionUpdate(companyId, payload) {
        payload.company_id = companyId;
        $.ajax({
            type: 'POST',
            url: baseUrl() + 'admin/update_subscription/',
            dataType: 'json',
            data: payload
        }).fail(function (xhr) {
            alert(ajaxErrorMessage(xhr, 'Failed to update subscription for property #' + companyId));
        });
    }

    $('.saas-subscription-state').on('change', function () {
        pushSubscriptionUpdate($(this).data('company-id'), {
            subscription_state: $(this).val()
        });
    });

    $('.saas-subscription-level').on('change', function () {
        pushSubscriptionUpdate($(this).data('company-id'), {
            subscription_level: $(this).val()
        });
    });

    $('.saas-trial-save-btn').on('click', function () {
        var companyId = $(this).data('company-id');
        var $row = $(this).closest('tr');
        var days = parseInt($row.find('.saas-trial-days').val(), 10);
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
            $(this)
        );
    });
});
