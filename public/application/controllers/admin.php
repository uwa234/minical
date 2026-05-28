<?php

/**
 * Platform SaaS admin: tenant management, revenue, and platform settings.
 */
class Admin extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->model(array(
            'Admin_model',
            'Company_model',
            'Company_subscription_model',
            'User_model',
            'Platform_settings_model',
            'Trial_registration_request_model',
        ));
        $this->load->library('form_validation');
    }

    function index()
    {
        if ($this->_is_platform_admin()) {
            redirect('admin/dashboard');
        }
        redirect('admin/login');
    }

    function login()
    {
        if ($this->_is_platform_admin()) {
            redirect('admin/dashboard');
        }

        $data['errors'] = array();
        $data['login'] = $this->input->post('login') ?: '';

        if ($this->input->post('submit')) {
            $login = trim($this->input->post('login'));
            $password = $this->input->post('password');

            $this->form_validation->set_rules('login', 'Email', 'trim|required');
            $this->form_validation->set_rules('password', 'Password', 'trim|required');

            if ($this->form_validation->run()) {
                $login_by_email = $this->config->item('login_by_email', 'tank_auth');
                if ($this->tank_auth->login($login, $password, false, false, $login_by_email)) {
                    $user_id = $this->session->userdata('user_id');
                    $email = $this->session->userdata('email');
                    if (is_platform_admin($user_id, $email)) {
                        redirect('admin/dashboard');
                    }
                    $this->tank_auth->logout();
                    $data['errors']['access'] = 'This account does not have platform admin access.';
                } else {
                    $data['errors']['login'] = 'Invalid email or password.';
                }
            }
        }

        $this->load->view('admin/login', $data);
    }

    function dashboard()
    {
        $this->_require_platform_admin();

        $from_date = date('Y-m-d', strtotime('-30 days'));
        $to_date = date('Y-m-d');
        $monthly = $this->Admin_model->get_monthly_report($from_date, $to_date);

        $data['state_counts'] = $this->Admin_model->get_subscription_state_counts();
        $data['mrr'] = $this->Admin_model->get_mrr_estimate();
        $data['trials_expiring'] = $this->Admin_model->get_trials_expiring_within_days(7);
        $data['monthly_summary'] = is_array($monthly) ? $monthly : array();
        $data['default_trial_days'] = $this->Platform_settings_model->get_default_trial_days();
        $data['main_content'] = 'admin/dashboard';
        $data['js_files'] = array();
        $data['css_files'] = array();

        $this->load->view('includes/admin_template', $data);
    }

    function revenue()
    {
        $this->_require_platform_admin();

        $from_date = $this->input->get('from_date');
        $to_date = $this->input->get('to_date');
        if (!$from_date) {
            $from_date = date('Y-m-d', strtotime('-12 months'));
        }
        if (!$to_date) {
            $to_date = date('Y-m-d');
        }

        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;
        $data['monthly_rows'] = $this->Admin_model->get_monthly_report();
        $data['range_summary'] = $this->Admin_model->get_monthly_report($from_date, $to_date);
        $data['main_content'] = 'admin/revenue';
        $data['js_files'] = array();
        $data['css_files'] = array();

        $this->load->view('includes/admin_template', $data);
    }

    function settings()
    {
        $this->_require_platform_admin();

        if ($this->input->post('save_settings')) {
            $trial_days = (int) $this->input->post('default_trial_days');
            if ($trial_days < 1) {
                $trial_days = 14;
            }
            if (
                $this->Platform_settings_model->set('default_trial_days', $trial_days)
                && $this->Platform_settings_model->set('marketing_hero_title', $this->input->post('marketing_hero_title'))
                && $this->Platform_settings_model->set('marketing_hero_subtitle', $this->input->post('marketing_hero_subtitle'))
            ) {
                $this->session->set_flashdata('admin_message', 'Platform settings saved.');
            } else {
                $this->session->set_flashdata('admin_error', 'Platform settings could not be saved. Run database migrations and try again.');
            }
            redirect('admin/settings');
        }

        if ($this->input->post('save_tier')) {
            $tier_id = $this->input->post('tier_id');
            $tier_data = array(
                'name' => $this->input->post('tier_name'),
                'min_rooms' => $this->input->post('tier_min_rooms'),
                'max_rooms' => $this->input->post('tier_max_rooms'),
                'monthly_price' => $this->input->post('tier_monthly_price'),
                'currency' => $this->input->post('tier_currency'),
                'subscription_level' => $this->input->post('tier_subscription_level'),
                'features_text' => $this->input->post('tier_features'),
                'sort_order' => $this->input->post('tier_sort_order'),
                'is_active' => $this->input->post('tier_is_active') ? 1 : 0,
            );
            $saved_id = $this->Platform_settings_model->save_pricing_tier($tier_data, $tier_id ? (int) $tier_id : null);
            if ($saved_id) {
                $this->session->set_flashdata('admin_message', 'Pricing tier saved.');
            } else {
                $this->session->set_flashdata('admin_error', 'Pricing tier could not be saved. Run database migrations and try again.');
            }
            redirect('admin/settings');
        }

        if ($this->input->post('delete_tier')) {
            if ($this->Platform_settings_model->delete_pricing_tier((int) $this->input->post('tier_id'))) {
                $this->session->set_flashdata('admin_message', 'Pricing tier deleted.');
            } else {
                $this->session->set_flashdata('admin_error', 'Pricing tier could not be deleted.');
            }
            redirect('admin/settings');
        }

        $data['default_trial_days'] = $this->Platform_settings_model->get_default_trial_days();
        $data['marketing_hero_title'] = $this->Platform_settings_model->get('marketing_hero_title', '');
        $data['marketing_hero_subtitle'] = $this->Platform_settings_model->get('marketing_hero_subtitle', '');
        $data['pricing_tiers'] = $this->Platform_settings_model->get_pricing_tiers(false);
        $data['edit_tier'] = null;
        $edit_id = (int) $this->input->get('edit_tier');
        if ($edit_id) {
            $data['edit_tier'] = $this->Platform_settings_model->get_pricing_tier($edit_id);
        }
        $data['main_content'] = 'admin/settings';
        $data['js_files'] = array(base_url() . auto_version('js/admin/platform-settings.js'));
        $data['css_files'] = array();

        $this->load->view('includes/admin_template', $data);
    }

    function trial_registrations()
    {
        $this->_require_platform_admin();

        $search_query = trim((string) $this->input->get('search_query'));
        $data['search_query'] = $search_query;
        $data['registrations'] = $this->Trial_registration_request_model->get_all(array(
            'search' => $search_query,
        ));
        $data['main_content'] = 'admin/trial_registrations';
        $data['js_files'] = array(base_url() . auto_version('js/admin/trial-registrations.js'));
        $data['css_files'] = array();

        $this->load->view('includes/admin_template', $data);
    }

    function update_trial_registration_status()
    {
        $this->_require_platform_admin(true);

        $id = (int) $this->input->post('request_id');
        $status = $this->input->post('status');

        if (!$id || !$status) {
            $this->_json_response(array('success' => false, 'error' => 'Invalid request.'), 400);
            return;
        }

        if ($this->Trial_registration_request_model->update_status($id, $status)) {
            $this->_json_response(array('success' => true));
            return;
        }

        $this->_json_response(array('success' => false, 'error' => 'Status could not be updated.'), 400);
    }

    function property_list()
    {
        $this->_require_platform_admin();

        $state = $this->input->get('state');
        if ($state === false || $state === null || $state === '') {
            $state = 'all';
        }

        $properties = $this->Admin_model->get_tenant_list(array(
            'subscription_state' => $state,
            'search' => $this->input->get('search_query'),
            'include_deleted' => $this->input->get('include_deleted') === '1',
        ));

        // SaaS pricing tiers (Starter/Growth/Professional/Enterprise).
        $tiers = $this->Platform_settings_model->get_pricing_tiers(false);
        foreach ($properties as &$property) {
            $room_count = isset($property['number_of_rooms']) ? (int) $property['number_of_rooms'] : 0;
            if ($room_count < 1 && isset($property['number_of_rooms_actual'])) {
                $room_count = (int) $property['number_of_rooms_actual'];
            }
            if ($room_count < 1) {
                $room_count = 1;
            }

            $meta = array();
            if (!empty($property['meta_data'])) {
                $decoded = json_decode($property['meta_data'], true);
                if (is_array($decoded)) {
                    $meta = $decoded;
                }
            }
            $selected_tier_id = isset($meta['tier_id']) ? (int) $meta['tier_id'] : 0;

            $tier_name = '';
            $tier_id_for_rooms = 0;
            foreach ($tiers as $tier) {
                $min = (int) $tier['min_rooms'];
                $max = (isset($tier['max_rooms']) && $tier['max_rooms'] !== null && $tier['max_rooms'] !== '')
                    ? (int) $tier['max_rooms']
                    : null;
                if ($room_count >= $min && ($max === null || $room_count <= $max)) {
                    $tier_name = isset($tier['name']) ? (string) $tier['name'] : '';
                    $tier_id_for_rooms = isset($tier['id']) ? (int) $tier['id'] : 0;
                    break;
                }
            }
            $property['pricing_tier_name'] = $tier_name !== '' ? $tier_name : '—';
            // Prefer stored tier_id (chosen by admin / billing) else fall back to room-derived.
            $property['pricing_tier_id'] = $selected_tier_id > 0 ? $selected_tier_id : $tier_id_for_rooms;

            $trial_expiry = isset($property['trial_expiry_date']) ? trim((string) $property['trial_expiry_date']) : '';
            $paid_expiry = isset($property['expiration_date']) ? trim((string) $property['expiration_date']) : '';
            $state = isset($property['subscription_state']) ? (string) $property['subscription_state'] : '';
            $plan_expiry = '';
            if ($state === 'trialing') {
                $plan_expiry = $trial_expiry;
            } elseif ($paid_expiry !== '' && $paid_expiry !== '0000-00-00') {
                $plan_expiry = $paid_expiry;
            }
            $property['plan_expiry_date'] = $plan_expiry;
            $property['plan_expiry_days_left'] = null;
            if ($plan_expiry !== '' && $plan_expiry !== '0000-00-00') {
                $today = new DateTime(date('Y-m-d'));
                $expiry = DateTime::createFromFormat('Y-m-d', $plan_expiry);
                if ($expiry instanceof DateTime) {
                    $diff_days = (int) $today->diff($expiry)->format('%r%a');
                    $property['plan_expiry_days_left'] = $diff_days;
                }
            }
        }
        unset($property);

        $data['properties'] = $properties;
        $data['pricing_tiers'] = $tiers;
        $data['filter_state'] = $state;
        $data['search_query'] = $this->input->get('search_query');
        $data['default_trial_days'] = $this->Platform_settings_model->get_default_trial_days();
        $data['main_content'] = 'admin/property_list';
        $data['js_files'] = array(base_url() . auto_version('js/admin/saas-admin.js'));
        $data['css_files'] = array();

        $this->load->view('includes/admin_template', $data);
    }

    function assign_owner()
    {
        $this->_require_platform_admin(true);

        $company_id = (int) $this->input->post('company_id');
        $owner_email = trim(strtolower($this->input->post('owner_email')));
        $owner_first_name = trim($this->input->post('owner_first_name'));
        $owner_last_name = trim($this->input->post('owner_last_name'));

        if (!$company_id || $owner_email === '') {
            $this->_json_response(array('success' => false, 'error' => 'company_id and owner_email are required.'), 400);
            return;
        }

        $company = $this->Company_model->get_company($company_id);
        if (!$company || !isset($company['company_id'])) {
            $this->_json_response(array('success' => false, 'error' => 'Property not found.'), 404);
            return;
        }

        $owner_user_id = $this->_resolve_or_create_owner_user($owner_email, $owner_first_name, $owner_last_name);
        if (!$owner_user_id) {
            $this->_json_response(array('success' => false, 'error' => 'Could not create or find user.'), 500);
            return;
        }

        $this->User_model->transfer_property_owner($company_id, $owner_user_id, true);

        $profile_update = array('current_company_id' => $company_id);
        if ($owner_first_name !== '') {
            $profile_update['first_name'] = $owner_first_name;
        }
        if ($owner_last_name !== '') {
            $profile_update['last_name'] = $owner_last_name;
        }
        $this->User_model->update_user_profile($owner_user_id, $profile_update);

        $this->_json_response(array(
            'success' => true,
            'company_id' => $company_id,
            'user_id' => $owner_user_id,
            'owner_email' => $owner_email,
        ));
    }

    function update_subscription()
    {
        $this->_require_platform_admin(true);

        $company_id = (int) $this->input->post('company_id');
        $subscription_state = $this->input->post('subscription_state');
        $subscription_level = $this->input->post('subscription_level');
        $tier_id = (int) $this->input->post('tier_id');

        if (!$company_id) {
            $this->_json_response(array('success' => false, 'error' => 'company_id is required.'), 400);
            return;
        }

        $allowed_states = array('trialing', 'active', 'unpaid', 'canceled', 'trial_ended');
        $update = array();
        if ($subscription_state !== null && $subscription_state !== '' && in_array($subscription_state, $allowed_states, true)) {
            $update['subscription_state'] = $subscription_state;
        }
        if ($tier_id > 0) {
            $tier = $this->Platform_settings_model->get_pricing_tier($tier_id);
            if (!$tier) {
                $this->_json_response(array('success' => false, 'error' => 'Invalid pricing tier.'), 422);
                return;
            }

            $update['subscription_level'] = resolve_subscription_level_for_tier($tier);
            $update['limit_feature'] = 1;

            $current = $this->Company_subscription_model->get_company_subscription($company_id);
            $meta = array();
            if ($current && !empty($current['meta_data'])) {
                $decoded = json_decode($current['meta_data'], true);
                if (is_array($decoded)) {
                    $meta = $decoded;
                }
            }
            $meta['tier_id'] = (int) $tier['id'];
            $meta['tier_name'] = (string) $tier['name'];
            $meta['tier_updated_at'] = gmdate('c');
            $update['meta_data'] = json_encode($meta);
        } elseif ($subscription_level !== null && $subscription_level !== '') {
            $update['subscription_level'] = (int) $subscription_level;
        }

        if (empty($update)) {
            $this->_json_response(array('success' => false, 'error' => 'Nothing to update.'), 400);
            return;
        }

        $this->Company_subscription_model->update_company_subscription($company_id, $update);
        $this->_json_response(array('success' => true));
    }

    function update_property()
    {
        $this->_require_platform_admin(true);

        $company_id = (int) $this->input->post('company_id');
        if (!$company_id) {
            $this->_json_response(array('success' => false, 'error' => 'company_id is required.'), 400);
            return;
        }

        $company = $this->Company_model->get_company($company_id);
        if (!$company || !isset($company['company_id'])) {
            $this->_json_response(array('success' => false, 'error' => 'Property not found.'), 404);
            return;
        }

        $update = array();
        $name = trim((string) $this->input->post('name'));
        if ($name !== '') {
            $update['name'] = $name;
        }
        if ($this->input->post('email') !== false && $this->input->post('email') !== null) {
            $update['email'] = trim((string) $this->input->post('email'));
        }
        if ($this->input->post('number_of_rooms') !== null && $this->input->post('number_of_rooms') !== '') {
            $rooms = (int) $this->input->post('number_of_rooms');
            if ($rooms < 1) {
                $this->_json_response(array('success' => false, 'error' => 'number_of_rooms must be at least 1.'), 400);
                return;
            }
            $update['number_of_rooms'] = $rooms;
        }

        if (empty($update)) {
            $this->_json_response(array('success' => false, 'error' => 'Nothing to update.'), 400);
            return;
        }

        $this->Company_model->update_company($company_id, $update);
        if ($this->db->_error_message()) {
            $this->_json_response(array(
                'success' => false,
                'error' => 'Property could not be updated. Check server logs.',
            ), 500);
            return;
        }
        $this->_json_response(array('success' => true, 'company_id' => $company_id));
    }

    function delete_property()
    {
        $this->_require_platform_admin(true);

        $company_id = (int) $this->input->post('company_id');
        $confirm_name = trim((string) $this->input->post('confirm_name'));

        if (!$company_id) {
            $this->_json_response(array('success' => false, 'error' => 'company_id is required.'), 400);
            return;
        }

        $company = $this->Company_model->get_company($company_id);
        if (!$company || !isset($company['company_id'])) {
            $this->_json_response(array('success' => false, 'error' => 'Property not found.'), 404);
            return;
        }

        if ($confirm_name === '' || strcasecmp($confirm_name, $company['name']) !== 0) {
            $this->_json_response(array(
                'success' => false,
                'error' => 'Type the exact property name to confirm permanent deletion.',
            ), 400);
            return;
        }

        if (!$this->Company_model->delete_company($company_id)) {
            $this->_json_response(array(
                'success' => false,
                'error' => 'Property could not be deleted. Check server logs for database errors.',
            ), 500);
            return;
        }

        if ((int) $this->session->userdata('current_company_id') === $company_id) {
            $this->session->unset_userdata('current_company_id');
        }

        $this->_json_response(array('success' => true, 'company_id' => $company_id));
    }

    function update_trial()
    {
        $this->_require_platform_admin(true);

        $company_id = (int) $this->input->post('company_id');
        $trial_days = (int) $this->input->post('trial_days');
        $trial_expiry_date = trim($this->input->post('trial_expiry_date'));

        if (!$company_id) {
            $this->_json_response(array('success' => false, 'error' => 'company_id is required.'), 400);
            return;
        }

        if ($trial_expiry_date !== '') {
            $expiry = $trial_expiry_date;
        } elseif ($trial_days > 0) {
            $expiry = date('Y-m-d', strtotime('+' . $trial_days . ' days'));
        } else {
            $this->_json_response(array('success' => false, 'error' => 'trial_days or trial_expiry_date required.'), 400);
            return;
        }

        $panel = $this->Admin_model->get_single_company_admin_panel_info($company_id);
        if ($panel) {
            $this->Admin_model->update_company_admin_panel_info($company_id, array(
                'trial_expiry_date' => $expiry,
            ));
        } else {
            $this->Admin_model->insert_company_admin_panel_info(array(
                'company_id' => $company_id,
                'creation_date' => date('Y-m-d G:i'),
                'trial_expiry_date' => $expiry,
            ));
        }

        $this->Company_subscription_model->update_company_subscription($company_id, array(
            'subscription_state' => 'trialing',
        ));

        $this->_json_response(array(
            'success' => true,
            'trial_expiry_date' => $expiry,
        ));
    }

    function update_plan_expiry()
    {
        $this->_require_platform_admin(true);

        $company_id = (int) $this->input->post('company_id');
        $plan_expiry_date = trim((string) $this->input->post('plan_expiry_date'));

        if (!$company_id) {
            $this->_json_response(array('success' => false, 'error' => 'company_id is required.'), 400);
            return;
        }
        if ($plan_expiry_date === '') {
            $this->_json_response(array('success' => false, 'error' => 'plan_expiry_date is required.'), 400);
            return;
        }

        $expiry = DateTime::createFromFormat('Y-m-d', $plan_expiry_date);
        if (!$expiry || $expiry->format('Y-m-d') !== $plan_expiry_date) {
            $this->_json_response(array('success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.'), 422);
            return;
        }

        $current = $this->Company_subscription_model->get_company_subscription($company_id);
        $state = $current && isset($current['subscription_state']) ? (string) $current['subscription_state'] : '';

        if ($state === 'trialing') {
            $panel = $this->Admin_model->get_single_company_admin_panel_info($company_id);
            if ($panel) {
                $this->Admin_model->update_company_admin_panel_info($company_id, array(
                    'trial_expiry_date' => $plan_expiry_date,
                ));
            } else {
                $this->Admin_model->insert_company_admin_panel_info(array(
                    'company_id' => $company_id,
                    'creation_date' => date('Y-m-d G:i'),
                    'trial_expiry_date' => $plan_expiry_date,
                ));
            }
        } else {
            $this->Company_subscription_model->update_company_subscription($company_id, array(
                'expiration_date' => $plan_expiry_date,
            ));
        }

        $this->_json_response(array(
            'success' => true,
            'plan_expiry_date' => $plan_expiry_date,
        ));
    }

    function _require_platform_admin($json = false)
    {
        if ($this->_is_platform_admin()) {
            return;
        }
        if ($json) {
            $this->_json_response(array('success' => false, 'error' => 'Platform admin access required.'), 403);
            exit;
        }
        if ($this->tank_auth->is_logged_in()) {
            show_error('Platform admin access required.', 403);
        }
        redirect('admin/login');
    }

    function _is_platform_admin()
    {
        if (!$this->tank_auth->is_logged_in()) {
            return false;
        }
        $user_id = $this->user_id ? $this->user_id : $this->session->userdata('user_id');
        $email = isset($this->user_email) && $this->user_email
            ? $this->user_email
            : $this->session->userdata('email');
        return is_platform_admin($user_id, $email);
    }

    function _resolve_or_create_owner_user($email, $first_name, $last_name)
    {
        $this->load->model('tank_auth/users');
        $user = $this->users->get_user_by_email($email);
        if ($user) {
            $user_id = (int) $user->id;
            $profile = array();
            if ($first_name !== '') {
                $profile['first_name'] = $first_name;
            }
            if ($last_name !== '') {
                $profile['last_name'] = $last_name;
            }
            if (!empty($profile)) {
                $this->User_model->update_user_profile($user_id, $profile);
            }
            return $user_id;
        }

        $data = array(
            'email' => $email,
            'first_name' => $first_name ?: 'Owner',
            'last_name' => $last_name ?: '',
            'password' => md5(uniqid(rand(), true) . microtime()),
        );
        $created = $this->users->create_user($data, false);
        if ($created && isset($created['user_id'])) {
            return (int) $created['user_id'];
        }
        return null;
    }

    function _json_response($payload, $http_code = 200)
    {
        $this->output
            ->set_status_header($http_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}
