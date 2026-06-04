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
            'Company_charge_model',
            'Company_payment_model',
            'Company_subscription_model',
            'User_model',
            'Platform_settings_model',
            'Platform_communication_model',
            'Trial_registration_request_model',
            'Platform_admin_model',
            'Platform_audit_log_model',
        ));
        $this->load->library('Totp');
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
        if ($this->_is_platform_admin() && !$this->_platform_admin_needs_2fa_verification()) {
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
                        $this->Platform_admin_model->link_user_id_by_email($email, $user_id);
                        if ($this->Platform_admin_model->is_2fa_enabled($user_id, $email)) {
                            $this->session->set_userdata('platform_admin_2fa_pending', (int) $user_id);
                            $this->session->unset_userdata('platform_admin_2fa_verified');
                            redirect('admin/verify_2fa');
                        }
                        $this->session->set_userdata('platform_admin_2fa_verified', 1);
                        platform_audit_log('login', array(
                            'actor_user_id' => $user_id,
                            'actor_email' => $email,
                            'details' => 'Platform admin login',
                        ));
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

    function verify_2fa()
    {
        $pending_user_id = (int) $this->session->userdata('platform_admin_2fa_pending');
        if ($pending_user_id < 1 || !$this->tank_auth->is_logged_in()) {
            redirect('admin/login');
        }

        $email = $this->session->userdata('email');
        if (!is_platform_admin($pending_user_id, $email)) {
            $this->tank_auth->logout();
            redirect('admin/login');
        }

        $data['errors'] = array();
        if ($this->input->post('submit')) {
            $code = trim((string) $this->input->post('totp_code'));
            if ($this->_verify_platform_admin_totp($pending_user_id, $email, $code)) {
                $this->session->set_userdata('platform_admin_2fa_verified', 1);
                $this->session->unset_userdata('platform_admin_2fa_pending');
                platform_audit_log('login.2fa', array(
                    'actor_user_id' => $pending_user_id,
                    'actor_email' => $email,
                    'details' => '2FA verification successful',
                ));
                redirect('admin/dashboard');
            }
            $data['errors']['code'] = 'Invalid authentication code. Try again.';
        }

        $this->load->view('admin/verify_2fa', $data);
    }

    function dashboard()
    {
        $this->_require_platform_admin();

        $mrr = $this->Admin_model->get_mrr_estimate();
        $month_metrics = $this->Admin_model->get_dashboard_month_saas_metrics();
        $debtor_outstanding = $this->Admin_model->get_subscription_debtor_outstanding_total();

        $data['state_counts'] = $this->Admin_model->get_subscription_state_counts();
        $data['subscription_revenue_lifetime'] = $this->Admin_model->get_subscription_revenue_lifetime_summary();
        $data['mrr_estimate'] = $mrr;
        $data['arr_estimate'] = round($mrr * 12, 2);
        $data['month_saas_metrics'] = is_array($month_metrics) ? $month_metrics : array();
        $data['debtor_outstanding'] = is_array($debtor_outstanding) ? $debtor_outstanding : array('total' => 0, 'count' => 0);
        $data['saas_currency_symbol'] = saas_currency_symbol();
        $data['trials_expiring'] = $this->Admin_model->get_trials_expiring_within_days(7);
        $data['default_trial_days'] = $this->Platform_settings_model->get_default_trial_days();
        $data['new_trial_leads_count'] = $this->Trial_registration_request_model->count_new();
        $data['recent_trial_leads'] = $this->Trial_registration_request_model->get_all(array(
            'statuses' => array('new', 'contacted'),
            'unlinked_only' => true,
            'limit' => 5,
        ));
        $data['communication_summary'] = array(
            'active_announcements' => $this->Platform_communication_model->count_active_announcements(),
            'email_templates' => count($this->Platform_communication_model->get_email_templates()),
            'webhooks_configured' => $this->_webhooks_configured(),
        );
        $data['website_logo_url'] = platform_website_logo_url();
        $data['website_favicon_url'] = platform_website_favicon_url();
        $data['main_content'] = 'admin/dashboard';
        $data['js_files'] = array();
        $data['css_files'] = array();

        $this->load->view('includes/admin_template', $data);
    }

    function analytics()
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

        $funnel = $this->Admin_model->get_funnel_report();
        $regional = $this->Admin_model->get_regional_report($from_date, $to_date);
        $subscriptions_by_room = $this->Admin_model->get_number_of_subscriptions_by_room_size_and_by_month();
        $charges_by_room = $this->Admin_model->get_charges_by_room_size_and_by_month();

        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;
        $data['saas_currency_symbol'] = saas_currency_symbol();
        $data['funnel_summary'] = $this->_build_funnel_summary($funnel);
        $data['regional_rows'] = is_array($regional) ? $regional : array();
        $data['room_size_months'] = $this->_build_room_size_table_rows($subscriptions_by_room, 12);
        $data['charges_by_room_months'] = $this->_build_room_size_table_rows($charges_by_room, 12, true);
        $data['chart_payload'] = $this->_build_admin_growth_chart_payload($funnel, $subscriptions_by_room, $charges_by_room);
        $data['main_content'] = 'admin/analytics';
        $data['js_files'] = array(
            base_url() . 'js/Chart.min.js',
            base_url() . auto_version('js/admin/analytics.js'),
        );
        $data['css_files'] = array();

        $this->load->view('includes/admin_template', $data);
    }

    function tenant_health()
    {
        $this->_require_platform_admin();

        $filter = $this->input->get('filter');
        $allowed_filters = array('all', 'at_risk', 'no_bookings', 'trial_expiring', 'night_audit', 'channel_sync');
        if (!$filter || !in_array($filter, $allowed_filters, true)) {
            $filter = 'at_risk';
        }

        $active_tenant_count = $this->Admin_model->get_active_tenant_count();
        $extension_stats = $this->Admin_model->get_extension_adoption_stats();
        foreach ($extension_stats as &$ext) {
            $count = isset($ext['tenant_count']) ? (int) $ext['tenant_count'] : 0;
            $ext['adoption_pct'] = $active_tenant_count > 0
                ? round(($count / $active_tenant_count) * 100, 1)
                : 0;
            $ext['display_name'] = $this->_extension_display_name($ext['extension_name']);
        }
        unset($ext);

        $data['filter'] = $filter;
        $data['search_query'] = $this->input->get('search_query');
        $data['health_summary'] = $this->Admin_model->get_tenant_health_summary();
        $data['health_rows'] = $this->Admin_model->get_tenant_health_rows(array(
            'filter' => $filter,
            'search' => $data['search_query'],
        ));
        $data['extension_stats'] = $extension_stats;
        $data['active_tenant_count'] = $active_tenant_count;
        $data['night_audit_health'] = $this->Admin_model->get_night_audit_platform_health();
        $data['cron_health'] = $this->Admin_model->get_cron_health_summary();
        $data['channel_sync_issues'] = $this->Admin_model->get_channel_sync_issues();
        $data['pricing_tiers'] = $this->Platform_settings_model->get_pricing_tiers(false);
        $data['default_grace_days'] = saas_paid_plan_grace_days();
        $data['main_content'] = 'admin/tenant_health';
        $data['js_files'] = array(base_url() . auto_version('js/admin/tenant-health.js'));
        $data['css_files'] = array();

        $this->load->view('includes/admin_template', $data);
    }

    function impersonate_tenant($company_id = null)
    {
        $this->_require_platform_admin();

        $company_id = (int) $company_id;
        if ($company_id < 1) {
            $this->session->set_flashdata('admin_message', 'Invalid property.');
            redirect('admin/tenant_health');
            return;
        }

        $company = $this->Company_model->get_company($company_id);
        if (!$company || !isset($company['company_id'])) {
            $this->session->set_flashdata('admin_message', 'Property not found.');
            redirect('admin/tenant_health');
            return;
        }

        $keys = platform_admin_impersonation_session_keys();
        $this->session->set_userdata(array(
            $keys['active'] => 1,
            $keys['company_id'] => $company_id,
            $keys['company_name'] => isset($company['name']) ? (string) $company['name'] : ('#' . $company_id),
            $keys['return_url'] => 'admin/tenant_health',
        ));

        $this->_log_admin_action($company_id, 'Started tenant impersonation', 'action', 'impersonation.start');
        redirect('menu/select_hotel/' . $company_id);
    }

    function stop_impersonation()
    {
        $this->_require_platform_admin();

        $keys = platform_admin_impersonation_session_keys();
        $return_url = $this->session->userdata($keys['return_url']);
        if (!$return_url) {
            $return_url = 'admin/tenant_health';
        }

        $this->session->unset_userdata($keys['active']);
        $this->session->unset_userdata($keys['company_id']);
        $this->session->unset_userdata($keys['company_name']);
        $this->session->unset_userdata($keys['return_url']);
        $this->session->unset_userdata('current_company_id');
        $this->session->unset_userdata('permissions');
        $this->session->unset_userdata('user_role');

        redirect($return_url);
    }

    function bulk_extend_trials()
    {
        $this->_require_platform_admin(true);

        $scope = $this->input->post('scope');
        $grace_days = $this->input->post('grace_days');
        $allowed_scopes = array('expiring_7', 'all_trialing', 'at_risk');
        if (!in_array($scope, $allowed_scopes, true)) {
            $this->_json_response(array('success' => false, 'error' => 'Invalid scope.'), 400);
            return;
        }

        $days = ($grace_days !== null && $grace_days !== '') ? (int) $grace_days : null;
        $company_ids = $this->Admin_model->get_tenant_health_bulk_company_ids($scope);
        if (empty($company_ids)) {
            $this->_json_response(array('success' => true, 'updated' => 0, 'failed' => 0));
            return;
        }

        $updated = 0;
        $failed = 0;
        foreach ($company_ids as $company_id) {
            $result = extend_subscription_grace_period($company_id, $days);
            if (!empty($result['success'])) {
                $updated++;
                $this->_log_admin_action(
                    $company_id,
                    'Bulk trial extension: +' . (int) $result['grace_days'] . ' days → ' . $result['new_expiry_date']
                );
            } else {
                $failed++;
            }
        }

        $this->_json_response(array(
            'success' => true,
            'updated' => $updated,
            'failed' => $failed,
            'total' => count($company_ids),
        ));
    }

    function bulk_email_tenants()
    {
        $this->_require_platform_admin(true);

        $scope = $this->input->post('scope');
        $subject = trim((string) $this->input->post('subject'));
        $body = trim((string) $this->input->post('body'));
        $allowed_scopes = array('active', 'all_trialing', 'at_risk', 'expiring_7');
        if (!in_array($scope, $allowed_scopes, true)) {
            $this->_json_response(array('success' => false, 'error' => 'Invalid scope.'), 400);
            return;
        }
        if ($subject === '' || $body === '') {
            $this->_json_response(array('success' => false, 'error' => 'Subject and message are required.'), 400);
            return;
        }
        if (!minical_env('SMTP_USER', '')) {
            $this->_json_response(array(
                'success' => false,
                'error' => 'Email is not configured (SMTP_USER missing in .env).',
            ), 503);
            return;
        }

        $company_ids = $this->Admin_model->get_tenant_health_bulk_company_ids($scope);
        if (empty($company_ids)) {
            $this->_json_response(array('success' => true, 'sent' => 0, 'skipped' => 0));
            return;
        }

        $this->load->library('email');
        $this->load->helper('email');
        $sent = 0;
        $skipped = 0;
        foreach ($company_ids as $company_id) {
            $company = $this->Company_model->get_company($company_id);
            if (!$company) {
                $skipped++;
                continue;
            }

            $recipient = !empty($company['owner_email']) ? trim((string) $company['owner_email']) : '';
            if ($recipient === '' && !empty($company['email'])) {
                $recipient = trim((string) $company['email']);
            }
            if ($recipient === '') {
                $skipped++;
                continue;
            }

            $html_body = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
            $this->email->clear(true);
            $this->email->from(minical_smtp_from_email(), 'Veurion Support');
            $this->email->to($recipient);
            $this->email->subject($subject);
            $this->email->message($html_body);
            $this->email->set_mailtype('html');

            if ($this->email->send()) {
                $sent++;
                $this->_log_admin_action($company_id, 'Bulk email sent: ' . $subject, 'note');
            } else {
                $skipped++;
            }
        }

        $this->_json_response(array(
            'success' => true,
            'sent' => $sent,
            'skipped' => $skipped,
            'total' => count($company_ids),
        ));
    }

    function bulk_update_tiers()
    {
        $this->_require_platform_admin(true);

        $tier_id = (int) $this->input->post('tier_id');
        $scope = $this->input->post('scope');
        $company_ids_raw = $this->input->post('company_ids');
        $allowed_scopes = array('active', 'all_trialing', 'at_risk', 'selected');

        if ($tier_id < 1) {
            $this->_json_response(array('success' => false, 'error' => 'tier_id is required.'), 400);
            return;
        }

        $tier = $this->Platform_settings_model->get_pricing_tier($tier_id);
        if (!$tier) {
            $this->_json_response(array('success' => false, 'error' => 'Invalid pricing tier.'), 422);
            return;
        }

        if ($scope === 'selected') {
            if (!is_array($company_ids_raw)) {
                $company_ids_raw = explode(',', (string) $company_ids_raw);
            }
            $company_ids = array();
            foreach ($company_ids_raw as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $company_ids[] = $id;
                }
            }
        } elseif (in_array($scope, array('active', 'all_trialing', 'at_risk'), true)) {
            $company_ids = $this->Admin_model->get_tenant_health_bulk_company_ids($scope);
        } else {
            $this->_json_response(array('success' => false, 'error' => 'Invalid scope.'), 400);
            return;
        }

        if (empty($company_ids)) {
            $this->_json_response(array('success' => true, 'updated' => 0));
            return;
        }

        $updated = 0;
        foreach ($company_ids as $company_id) {
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

            $this->Company_subscription_model->update_company_subscription($company_id, array(
                'subscription_level' => resolve_subscription_level_for_tier($tier),
                'limit_feature' => 1,
                'meta_data' => json_encode($meta),
            ));
            $updated++;
            $this->_log_admin_action($company_id, 'Bulk tier change → ' . $tier['name']);
        }

        $this->_json_response(array(
            'success' => true,
            'updated' => $updated,
            'tier_name' => $tier['name'],
        ));
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
        $data['subscription_payments'] = $this->Admin_model->get_saas_subscription_payments($from_date, $to_date);
        $data['subscription_revenue_summary'] = $this->Admin_model->get_subscription_revenue_summary_for_range($from_date, $to_date);
        $data['subscription_revenue_lifetime'] = $this->Admin_model->get_subscription_revenue_lifetime_summary();
        $data['saas_currency_symbol'] = saas_currency_symbol();
        $data['main_content'] = 'admin/revenue';
        $data['js_files'] = array();
        $data['css_files'] = array();

        $this->load->view('includes/admin_template', $data);
    }

    function debtors()
    {
        $this->_require_platform_admin();

        $filter = $this->input->get('filter');
        if (!$filter || !in_array($filter, array('overdue', 'due_soon', 'all'), true)) {
            $filter = 'overdue';
        }

        $debtors = $this->Admin_model->get_subscription_debtors(array(
            'filter' => $filter,
            'search' => $this->input->get('search_query'),
            'include_deleted' => $this->input->get('include_deleted') === '1',
        ));

        $tiers = $this->Platform_settings_model->get_pricing_tiers(false);
        foreach ($debtors as &$row) {
            $room_count = isset($row['number_of_rooms']) ? (int) $row['number_of_rooms'] : 1;
            if ($room_count < 1) {
                $room_count = 1;
            }

            $tier_name = '—';
            $meta = array();
            if (!empty($row['meta_data'])) {
                $decoded = json_decode($row['meta_data'], true);
                if (is_array($decoded)) {
                    $meta = $decoded;
                }
            }
            $row['last_contacted_at'] = subscription_debtor_last_contacted_at($row['meta_data']);
            $selected_tier_id = isset($meta['tier_id']) ? (int) $meta['tier_id'] : 0;
            foreach ($tiers as $tier) {
                if ($selected_tier_id > 0) {
                    if ((int) $tier['id'] === $selected_tier_id) {
                        $tier_name = isset($tier['name']) ? (string) $tier['name'] : '—';
                        break;
                    }
                } else {
                    $min = (int) $tier['min_rooms'];
                    $max = (isset($tier['max_rooms']) && $tier['max_rooms'] !== null && $tier['max_rooms'] !== '')
                        ? (int) $tier['max_rooms']
                        : null;
                    if ($room_count >= $min && ($max === null || $room_count <= $max)) {
                        $tier_name = isset($tier['name']) ? (string) $tier['name'] : '—';
                        break;
                    }
                }
            }
            $row['pricing_tier_name'] = $tier_name;
            $row['amount_due'] = resolve_subscription_debtor_amount_due($row, $tiers);
        }
        unset($row);

        $data['filter'] = $filter;
        $data['debtors'] = $debtors;
        $data['debtor_counts'] = $this->Admin_model->get_subscription_debtor_counts();
        $data['search_query'] = $this->input->get('search_query');
        $data['saas_currency_symbol'] = saas_currency_symbol();
        $data['pricing_tiers'] = $tiers;
        $data['default_grace_days'] = saas_paid_plan_grace_days();
        $data['main_content'] = 'admin/debtors';
        $data['js_files'] = array(base_url() . auto_version('js/admin/debtors.js'));
        $data['css_files'] = array();

        $this->load->view('includes/admin_template', $data);
    }

    function record_subscription_payment()
    {
        $this->_require_platform_admin(true);

        $company_id = (int) $this->input->post('company_id');
        $amount = round((float) $this->input->post('amount'), 2);
        $renewal_period = $this->input->post('renewal_period');
        $tier_id = (int) $this->input->post('tier_id');
        $payment_date = trim((string) $this->input->post('payment_date'));
        $notes = trim((string) $this->input->post('notes'));
        $send_receipt = $this->input->post('send_receipt') === '1' || $this->input->post('send_receipt') === 'true';

        if (!$company_id) {
            $this->_json_response(array('success' => false, 'error' => 'company_id is required.'), 400);
            return;
        }

        $options = array(
            'company_id' => $company_id,
            'amount' => $amount,
            'renewal_period' => $renewal_period ?: '1 month',
            'payment_date' => $payment_date !== '' ? $payment_date : gmdate('Y-m-d'),
            'notes' => $notes,
            'recorded_by' => $this->session->userdata('email'),
        );
        if ($tier_id > 0) {
            $options['tier_id'] = $tier_id;
        }

        $result = record_saas_subscription_payment($options);
        if (empty($result['success'])) {
            $this->_json_response(array(
                'success' => false,
                'error' => !empty($result['error']) ? $result['error'] : 'Payment could not be recorded.',
            ), 422);
            return;
        }

        $log_msg = 'Recorded manual subscription payment of '
            . saas_currency_symbol() . format_saas_price_amount($result['amount'])
            . ' (ref ' . $result['reference'] . ', expires ' . $result['expiration_date'] . ')';
        if ($notes !== '') {
            $log_msg .= '. Note: ' . $notes;
        }
        $this->_log_admin_action($company_id, $log_msg);

        $receipt_sent = false;
        if ($send_receipt) {
            $receipt_sent = send_saas_subscription_receipt_email($company_id, $result['reference']);
        }

        $this->_json_response(array(
            'success' => true,
            'reference' => $result['reference'],
            'expiration_date' => $result['expiration_date'],
            'amount' => $result['amount'],
            'receipt_url' => saas_subscription_receipt_url($company_id, $result['reference']),
            'receipt_sent' => $receipt_sent,
        ));
    }

    function mark_debtor_contacted()
    {
        $this->_require_platform_admin(true);

        $company_id = (int) $this->input->post('company_id');
        $note = trim((string) $this->input->post('note'));

        if (!$company_id) {
            $this->_json_response(array('success' => false, 'error' => 'company_id is required.'), 400);
            return;
        }

        $result = mark_subscription_debtor_contacted(
            $company_id,
            $this->session->userdata('email'),
            $note
        );
        if (empty($result['success'])) {
            $this->_json_response(array(
                'success' => false,
                'error' => !empty($result['error']) ? $result['error'] : 'Could not mark as contacted.',
            ), 422);
            return;
        }

        $log_msg = 'Marked debtor as contacted';
        if ($note !== '') {
            $log_msg .= ': ' . $note;
        }
        $this->_log_admin_action($company_id, $log_msg);

        $this->_json_response(array(
            'success' => true,
            'contacted_at' => $result['contacted_at'],
        ));
    }

    function send_subscription_receipt()
    {
        $this->_require_platform_admin(true);

        $company_id = (int) $this->input->post('company_id');
        $reference = trim((string) $this->input->post('reference'));

        if (!$company_id || $reference === '') {
            $this->_json_response(array('success' => false, 'error' => 'company_id and reference are required.'), 400);
            return;
        }

        if (send_saas_subscription_receipt_email($company_id, $reference)) {
            $this->_log_admin_action($company_id, 'Sent subscription receipt email (ref ' . $reference . ')');
            $this->_json_response(array('success' => true));
            return;
        }

        $this->_json_response(array('success' => false, 'error' => 'Receipt email could not be sent.'), 502);
    }

    function subscription_receipt($company_id = null, $reference = null)
    {
        $this->_require_platform_admin();

        $company_id = (int) $company_id;
        $reference = rawurldecode(trim((string) $reference));
        if ($company_id < 1 || $reference === '') {
            show_404();
            return;
        }

        $receipt = build_saas_subscription_receipt_data($company_id, $reference);
        if (!$receipt) {
            show_404();
            return;
        }

        $this->output
            ->set_content_type('text/html; charset=utf-8')
            ->set_output(build_saas_subscription_receipt_html($receipt));
    }

    function export_revenue_csv()
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

        $payments = $this->Admin_model->get_saas_subscription_payments($from_date, $to_date);
        $rows = array();
        foreach ($payments as $payment) {
            $rows[] = array(
                isset($payment['paid_date']) ? $payment['paid_date'] : '',
                isset($payment['company_id']) ? (int) $payment['company_id'] : '',
                isset($payment['property_name']) ? $payment['property_name'] : '',
                isset($payment['property_status']) ? $payment['property_status'] : '',
                isset($payment['payment_type']) ? $payment['payment_type'] : '',
                isset($payment['amount']) ? number_format((float) $payment['amount'], 2, '.', '') : '0.00',
                isset($payment['reference']) ? $payment['reference'] : '',
            );
        }

        $this->_stream_csv(
            'veurion-revenue-' . $from_date . '-to-' . $to_date . '.csv',
            array('Paid date', 'Company ID', 'Property', 'Property status', 'Payment type', 'Amount', 'Reference'),
            $rows
        );
    }

    function export_debtors_csv()
    {
        $this->_require_platform_admin();

        $filter = $this->input->get('filter');
        if (!$filter || !in_array($filter, array('overdue', 'due_soon', 'all'), true)) {
            $filter = 'overdue';
        }

        $debtors = $this->Admin_model->get_subscription_debtors(array(
            'filter' => $filter,
            'search' => $this->input->get('search_query'),
            'include_deleted' => $this->input->get('include_deleted') === '1',
        ));
        $tiers = $this->Platform_settings_model->get_pricing_tiers(false);

        $rows = array();
        foreach ($debtors as $debtor) {
            $amount_due = resolve_subscription_debtor_amount_due($debtor, $tiers);
            $owner = isset($debtor['owner_email']) ? (string) $debtor['owner_email'] : '';
            if (!empty($debtor['owner_first_name']) || !empty($debtor['owner_last_name'])) {
                $owner = trim($debtor['owner_first_name'] . ' ' . $debtor['owner_last_name']) . ' <' . $owner . '>';
            }
            $last_contacted = subscription_debtor_last_contacted_at(isset($debtor['meta_data']) ? $debtor['meta_data'] : null);
            $rows[] = array(
                isset($debtor['company_id']) ? (int) $debtor['company_id'] : '',
                isset($debtor['name']) ? $debtor['name'] : '',
                $owner,
                isset($debtor['subscription_state']) ? $debtor['subscription_state'] : '',
                isset($debtor['debtor_reason_label']) ? $debtor['debtor_reason_label'] : '',
                isset($debtor['due_date']) ? $debtor['due_date'] : '',
                isset($debtor['days_overdue']) ? (int) $debtor['days_overdue'] : 0,
                number_format($amount_due, 2, '.', ''),
                isset($debtor['balance']) ? number_format((float) $debtor['balance'], 2, '.', '') : '0.00',
                $last_contacted ? $last_contacted : '',
            );
        }

        $this->_stream_csv(
            'veurion-debtors-' . $filter . '-' . date('Y-m-d') . '.csv',
            array('Company ID', 'Property', 'Owner', 'Subscription state', 'Reason', 'Due date', 'Days overdue', 'Amount due', 'Balance', 'Last contacted'),
            $rows
        );
    }

    function export_properties_csv()
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
        $tiers = $this->Platform_settings_model->get_pricing_tiers(false);

        $rows = array();
        foreach ($properties as $property) {
            $this->_enrich_tenant_subscription_fields($property, $tiers);
            $owner = isset($property['owner_email']) ? (string) $property['owner_email'] : '';
            if (!empty($property['owner_first_name']) || !empty($property['owner_last_name'])) {
                $owner = trim($property['owner_first_name'] . ' ' . $property['owner_last_name']) . ' <' . $owner . '>';
            }
            $plan_expires = !empty($property['expiration_date']) && $property['expiration_date'] !== '0000-00-00'
                ? $property['expiration_date']
                : (isset($property['trial_expiry_date']) ? $property['trial_expiry_date'] : '');
            $rows[] = array(
                isset($property['company_id']) ? (int) $property['company_id'] : '',
                isset($property['name']) ? $property['name'] : '',
                $owner,
                isset($property['number_of_rooms']) ? (int) $property['number_of_rooms'] : '',
                isset($property['subscription_state']) ? $property['subscription_state'] : '',
                isset($property['pricing_tier_name']) ? $property['pricing_tier_name'] : '',
                $plan_expires,
                isset($property['creation_date']) ? $property['creation_date'] : '',
                !empty($property['is_deleted']) ? 'yes' : 'no',
            );
        }

        $this->_stream_csv(
            'veurion-properties-' . date('Y-m-d') . '.csv',
            array('Company ID', 'Property', 'Owner', 'Rooms', 'Subscription state', 'Pricing tier', 'Plan expires', 'Created', 'Deleted'),
            $rows
        );
    }

    function extend_subscription_grace()
    {
        $this->_require_platform_admin(true);

        $company_id = (int) $this->input->post('company_id');
        $grace_days = $this->input->post('grace_days');

        if (!$company_id) {
            $this->_json_response(array('success' => false, 'error' => 'company_id is required.'), 400);
            return;
        }

        $days = ($grace_days !== null && $grace_days !== '') ? (int) $grace_days : null;
        $result = extend_subscription_grace_period($company_id, $days);
        if (empty($result['success'])) {
            $this->_json_response(array(
                'success' => false,
                'error' => !empty($result['error']) ? $result['error'] : 'Grace period could not be extended.',
            ), 422);
            return;
        }

        $this->_log_admin_action(
            $company_id,
            'Extended grace by ' . (int) $result['grace_days'] . ' day'
            . ((int) $result['grace_days'] === 1 ? '' : 's')
            . ' — new expiry ' . $result['new_expiry_date']
        );

        $grace_email_sent = send_subscription_grace_extended_email($company_id, array(
            'new_expiry_date' => $result['new_expiry_date'],
            'grace_days' => $result['grace_days'],
            'subscription_state' => $result['subscription_state'],
        ));

        $this->_json_response(array(
            'success' => true,
            'new_expiry_date' => $result['new_expiry_date'],
            'subscription_state' => $result['subscription_state'],
            'grace_email_sent' => $grace_email_sent,
        ));
    }

    function send_payment_reminder()
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

        $recipient = !empty($company['owner_email']) ? trim((string) $company['owner_email']) : '';
        if ($recipient === '' && !empty($company['email'])) {
            $recipient = trim((string) $company['email']);
        }
        if ($recipient === '') {
            $this->_json_response(array('success' => false, 'error' => 'No owner or property email on file.'), 422);
            return;
        }

        if (!minical_env('SMTP_USER', '')) {
            $this->_json_response(array(
                'success' => false,
                'error' => 'Email is not configured (SMTP_USER missing in .env).',
            ), 503);
            return;
        }

        $tiers = $this->Platform_settings_model->get_pricing_tiers(false);
        $tier_id = resolve_company_pricing_tier_id($company, $tiers);
        $amount_due = isset($company['renewal_cost']) ? (float) $company['renewal_cost'] : 0;
        if ($amount_due <= 0 && $tier_id > 0) {
            $tier = $this->Platform_settings_model->get_pricing_tier($tier_id);
            if ($tier && isset($tier['monthly_price'])) {
                $amount_due = (float) $tier['monthly_price'];
            }
        }

        $due_date = '';
        $state = isset($company['subscription_state']) ? (string) $company['subscription_state'] : '';
        if (in_array($state, array('trialing', 'trial_ended'), true) && !empty($company['trial_expiry_date'])) {
            $due_date = (string) $company['trial_expiry_date'];
        } elseif (!empty($company['expiration_date']) && $company['expiration_date'] !== '0000-00-00') {
            $due_date = (string) $company['expiration_date'];
        }

        $email_content = build_subscription_payment_reminder_email($company, array(
            'property_name' => isset($company['name']) ? $company['name'] : '',
            'amount_due' => $amount_due,
            'due_date' => $due_date,
        ));

        $this->load->library('email');
        $this->load->helper('email');
        $from_email = minical_smtp_from_email();
        $from_name = 'Veurion';

        $this->email->from($from_email, $from_name);
        $this->email->to($recipient);
        $this->email->subject($email_content['subject']);
        $this->email->message($email_content['body']);
        $this->email->set_mailtype('html');

        if (!$this->email->send()) {
            log_message('error', 'Payment reminder email failed for company ' . $company_id . ': '
                . $this->email->print_debugger(array('headers')));
            $this->_json_response(array('success' => false, 'error' => 'Reminder email could not be sent.'), 502);
            return;
        }

        $this->_log_admin_action($company_id, 'Sent payment reminder email to ' . $recipient);

        platform_notify_debtor_overdue($company, array(
            'due_date' => $due_date,
            'amount_due' => $amount_due > 0 ? saas_currency_symbol() . format_saas_price_amount($amount_due) : '',
        ));

        $this->_json_response(array(
            'success' => true,
            'recipient' => $recipient,
        ));
    }

    function communication()
    {
        $this->_require_platform_admin();

        if ($this->input->post('save_email_template')) {
            $slug = trim((string) $this->input->post('template_slug'));
            $allowed = array('trial_outreach', 'payment_reminder', 'win_back');
            if (!in_array($slug, $allowed, true)) {
                $this->session->set_flashdata('admin_error', 'Unknown email template.');
                redirect('admin/communication');
            }
            if ($this->Platform_communication_model->save_email_template($slug, array(
                'subject' => $this->input->post('subject'),
                'body_html' => $this->input->post('body_html'),
                'is_active' => $this->input->post('is_active') ? 1 : 0,
            ))) {
                $this->session->set_flashdata('admin_message', 'Email template saved.');
            } else {
                $this->session->set_flashdata('admin_error', 'Email template could not be saved.');
            }
            redirect('admin/communication#templates');
        }

        if ($this->input->post('save_announcement')) {
            $announcement_id = (int) $this->input->post('announcement_id');
            $saved = $this->Platform_communication_model->save_announcement(array(
                'title' => $this->input->post('title'),
                'body' => $this->input->post('body'),
                'severity' => $this->input->post('severity'),
                'starts_at' => $this->input->post('starts_at'),
                'ends_at' => $this->input->post('ends_at'),
                'is_active' => $this->input->post('is_active') ? 1 : 0,
                'dismissible' => $this->input->post('dismissible') ? 1 : 0,
            ), $announcement_id > 0 ? $announcement_id : null);
            if ($saved) {
                $this->session->set_flashdata('admin_message', 'Announcement saved.');
            } else {
                $this->session->set_flashdata('admin_error', 'Announcement could not be saved.');
            }
            redirect('admin/communication#announcements');
        }

        if ($this->input->post('delete_announcement')) {
            if ($this->Platform_communication_model->delete_announcement((int) $this->input->post('announcement_id'))) {
                $this->session->set_flashdata('admin_message', 'Announcement deleted.');
            } else {
                $this->session->set_flashdata('admin_error', 'Announcement could not be deleted.');
            }
            redirect('admin/communication#announcements');
        }

        if ($this->input->post('save_webhooks')) {
            $events = $this->input->post('webhook_enabled_events');
            if (!is_array($events)) {
                $events = array();
            }
            if ($this->Platform_communication_model->save_webhook_settings(array(
                'webhook_url' => $this->input->post('webhook_url'),
                'slack_webhook_url' => $this->input->post('slack_webhook_url'),
                'webhook_enabled_events' => $events,
            ))) {
                $this->session->set_flashdata('admin_message', 'Webhook settings saved.');
            } else {
                $this->session->set_flashdata('admin_error', 'Webhook settings could not be saved.');
            }
            redirect('admin/communication#webhooks');
        }

        $edit_announcement = null;
        $edit_id = (int) $this->input->get('edit_announcement');
        if ($edit_id > 0) {
            $edit_announcement = $this->Platform_communication_model->get_announcement($edit_id);
        }

        $data['email_templates'] = $this->Platform_communication_model->get_email_templates();
        $data['announcements'] = $this->Platform_communication_model->get_announcements();
        $data['webhook_settings'] = $this->Platform_communication_model->get_webhook_settings();
        $data['webhook_event_labels'] = platform_webhook_event_labels();
        $data['edit_announcement'] = $edit_announcement;
        $data['template_placeholders'] = array(
            'trial_outreach' => '{first_name}, {property_name}, {room_count}, {lead_email}',
            'payment_reminder' => '{property_name}, {due_date}, {amount_due}, {subscription_url}',
            'win_back' => '{property_name}, {subscription_url}',
        );
        $data['main_content'] = 'admin/communication';
        $data['js_files'] = array(base_url() . auto_version('js/admin/communication.js'));
        $data['css_files'] = array();

        $this->load->view('includes/admin_template', $data);
    }

    function send_trial_lead_email()
    {
        $this->_require_platform_admin(true);

        $request_id = (int) $this->input->post('request_id');
        if (!$request_id) {
            $this->_json_response(array('success' => false, 'error' => 'request_id is required.'), 400);
            return;
        }

        $lead = $this->Trial_registration_request_model->get_by_id($request_id);
        if (!$lead) {
            $this->_json_response(array('success' => false, 'error' => 'Trial lead not found.'), 404);
            return;
        }

        $recipient = trim((string) $lead['email']);
        if ($recipient === '') {
            $this->_json_response(array('success' => false, 'error' => 'Lead has no email address.'), 422);
            return;
        }

        $email_content = build_trial_lead_outreach_email($lead);
        $result = send_platform_email($recipient, $email_content['subject'], $email_content['body']);
        if (!$result['success']) {
            $this->_json_response(array('success' => false, 'error' => $result['error']), 502);
            return;
        }

        if ($lead['status'] === 'new') {
            $this->Trial_registration_request_model->update_status($request_id, 'contacted');
        }

        $this->_json_response(array(
            'success' => true,
            'recipient' => $recipient,
        ));
    }

    function send_win_back_email()
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

        $state = isset($company['subscription_state']) ? (string) $company['subscription_state'] : '';
        if (!in_array($state, array('canceled', 'unpaid', 'trial_ended'), true)) {
            $this->_json_response(array('success' => false, 'error' => 'Property is not eligible for win-back (must be canceled, unpaid, or trial ended).'), 422);
            return;
        }

        $recipient = !empty($company['owner_email']) ? trim((string) $company['owner_email']) : '';
        if ($recipient === '' && !empty($company['email'])) {
            $recipient = trim((string) $company['email']);
        }
        if ($recipient === '') {
            $this->_json_response(array('success' => false, 'error' => 'No owner or property email on file.'), 422);
            return;
        }

        $email_content = build_win_back_email($company);
        $result = send_platform_email($recipient, $email_content['subject'], $email_content['body']);
        if (!$result['success']) {
            $this->_json_response(array('success' => false, 'error' => $result['error']), 502);
            return;
        }

        $this->_log_admin_action($company_id, 'Sent win-back email to ' . $recipient);

        $this->_json_response(array(
            'success' => true,
            'recipient' => $recipient,
        ));
    }

    function test_webhook()
    {
        $this->_require_platform_admin(true);

        $sent = platform_notify_event('trial_signup', array(
            'property' => 'Test Hotel Lagos',
            'contact' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+2348000000000',
            'rooms' => '24',
            'note' => 'This is a test notification from Platform Admin.',
        ));

        if (!$sent) {
            $this->_json_response(array(
                'success' => false,
                'error' => 'Webhook not sent. Configure a Slack or generic webhook URL and enable the trial sign-up event.',
            ), 422);
            return;
        }

        $this->_json_response(array('success' => true));
    }

    function security()
    {
        $this->_require_platform_admin();

        if ($this->input->post('add_platform_admin')) {
            $email = strtolower(trim((string) $this->input->post('admin_email')));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->session->set_flashdata('admin_error', 'Enter a valid email address.');
                redirect('admin/security#admins');
            }

            $admin_id = $this->Platform_admin_model->add_admin($email, (int) $this->session->userdata('user_id'));
            if ($admin_id) {
                $this->_log_platform_audit('admin.add', array(
                    'target_type' => 'platform_admin',
                    'target_id' => $admin_id,
                    'target_label' => $email,
                    'details' => 'Added platform admin ' . $email,
                ));
                $this->session->set_flashdata('admin_message', 'Platform admin added: ' . $email);
            } else {
                $this->session->set_flashdata('admin_error', 'Could not add platform admin. Run database migrations and try again.');
            }
            redirect('admin/security#admins');
        }

        $filters = array(
            'action' => trim((string) $this->input->get('audit_action')),
            'search' => trim((string) $this->input->get('audit_search')),
        );
        $page = max(1, (int) $this->input->get('audit_page'));
        $per_page = 50;
        $offset = ($page - 1) * $per_page;

        $current_record = $this->_current_platform_admin_record();
        $pending_secret = $this->session->userdata('platform_admin_2fa_setup_secret');

        $data['platform_admins'] = $this->Platform_admin_model->get_all(true);
        $data['audit_entries'] = $this->Platform_audit_log_model->get_entries($filters, $per_page, $offset);
        $data['audit_total'] = $this->Platform_audit_log_model->count_entries($filters);
        $data['audit_filters'] = $filters;
        $data['audit_page'] = $page;
        $data['audit_per_page'] = $per_page;
        $data['audit_action_labels'] = $this->Platform_audit_log_model->action_labels();
        $data['current_admin'] = $current_record;
        $data['current_admin_2fa_enabled'] = $current_record && !empty($current_record['totp_enabled']);
        $data['totp_setup_secret'] = $pending_secret ? (string) $pending_secret : null;
        $data['totp_setup_uri'] = null;
        if ($data['totp_setup_secret']) {
            $email = (string) $this->session->userdata('email');
            $data['totp_setup_uri'] = Totp::provisioning_uri($data['totp_setup_secret'], $email);
        }
        $data['main_content'] = 'admin/security';
        $data['js_files'] = array(base_url() . auto_version('js/admin/security.js'));
        $data['css_files'] = array();

        $this->load->view('includes/admin_template', $data);
    }

    function revoke_platform_admin()
    {
        $this->_require_platform_admin(true);

        $platform_admin_id = (int) $this->input->post('platform_admin_id');
        if (!$platform_admin_id) {
            $this->_json_response(array('success' => false, 'error' => 'platform_admin_id is required.'), 400);
            return;
        }

        $target = $this->Platform_admin_model->get_by_id($platform_admin_id);
        if (!$target || empty($target['is_active'])) {
            $this->_json_response(array('success' => false, 'error' => 'Platform admin not found.'), 404);
            return;
        }

        $current = $this->_current_platform_admin_record();
        if ($current && (int) $current['platform_admin_id'] === $platform_admin_id) {
            $this->_json_response(array('success' => false, 'error' => 'You cannot revoke your own access.'), 400);
            return;
        }

        if ($this->Platform_admin_model->count_active_admins() <= 1) {
            $this->_json_response(array('success' => false, 'error' => 'At least one active platform admin is required.'), 400);
            return;
        }

        if (!$this->Platform_admin_model->revoke_admin($platform_admin_id, (int) $this->session->userdata('user_id'))) {
            $this->_json_response(array('success' => false, 'error' => 'Could not revoke platform admin.'), 500);
            return;
        }

        $this->_log_platform_audit('admin.revoke', array(
            'target_type' => 'platform_admin',
            'target_id' => $platform_admin_id,
            'target_label' => isset($target['email']) ? $target['email'] : '',
            'details' => 'Revoked platform admin access',
        ));

        $this->_json_response(array('success' => true));
    }

    function start_2fa_setup()
    {
        $this->_require_platform_admin(true);

        $record = $this->_current_platform_admin_record();
        if (!$record) {
            $this->_json_response(array('success' => false, 'error' => 'Platform admin record not found.'), 404);
            return;
        }

        if (!empty($record['totp_enabled'])) {
            $this->_json_response(array('success' => false, 'error' => 'Two-factor authentication is already enabled.'), 400);
            return;
        }

        $secret = Totp::generate_secret();
        $this->session->set_userdata('platform_admin_2fa_setup_secret', $secret);
        $email = (string) $this->session->userdata('email');

        $this->_json_response(array(
            'success' => true,
            'secret' => $secret,
            'provisioning_uri' => Totp::provisioning_uri($secret, $email),
        ));
    }

    function confirm_2fa_setup()
    {
        $this->_require_platform_admin(true);

        $record = $this->_current_platform_admin_record();
        if (!$record) {
            $this->_json_response(array('success' => false, 'error' => 'Platform admin record not found.'), 404);
            return;
        }

        $secret = (string) $this->session->userdata('platform_admin_2fa_setup_secret');
        $code = trim((string) $this->input->post('totp_code'));
        if ($secret === '' || !Totp::verify($secret, $code)) {
            $this->_json_response(array('success' => false, 'error' => 'Invalid authentication code.'), 422);
            return;
        }

        $encrypted = platform_admin_encrypt_secret($secret);
        $this->Platform_admin_model->save_totp_secret((int) $record['platform_admin_id'], $encrypted, true);
        $this->session->unset_userdata('platform_admin_2fa_setup_secret');
        $this->session->set_userdata('platform_admin_2fa_verified', 1);

        $this->_log_platform_audit('2fa.enable', array(
            'target_type' => 'platform_admin',
            'target_id' => (int) $record['platform_admin_id'],
            'target_label' => isset($record['email']) ? $record['email'] : '',
            'details' => 'Enabled two-factor authentication',
        ));

        $this->_json_response(array('success' => true));
    }

    function disable_2fa()
    {
        $this->_require_platform_admin(true);

        $record = $this->_current_platform_admin_record();
        if (!$record || empty($record['totp_enabled'])) {
            $this->_json_response(array('success' => false, 'error' => 'Two-factor authentication is not enabled.'), 400);
            return;
        }

        $code = trim((string) $this->input->post('totp_code'));
        if (!$this->_verify_platform_admin_totp((int) $this->session->userdata('user_id'), $this->session->userdata('email'), $code)) {
            $this->_json_response(array('success' => false, 'error' => 'Invalid authentication code.'), 422);
            return;
        }

        $this->Platform_admin_model->disable_2fa((int) $record['platform_admin_id']);
        $this->session->unset_userdata('platform_admin_2fa_verified');

        $this->_log_platform_audit('2fa.disable', array(
            'target_type' => 'platform_admin',
            'target_id' => (int) $record['platform_admin_id'],
            'target_label' => isset($record['email']) ? $record['email'] : '',
            'details' => 'Disabled two-factor authentication',
        ));

        $this->_json_response(array('success' => true));
    }

    function settings()
    {
        $this->_require_platform_admin();

        if ($this->input->post('save_website_logo')) {
            $result = platform_save_branding_upload('logo');
            if ($result['ok']) {
                $this->_log_platform_audit('settings.branding', array(
                    'target_type' => 'settings',
                    'details' => 'Uploaded website logo',
                ));
                $this->session->set_flashdata('admin_message', $result['message']);
            } else {
                $this->session->set_flashdata('admin_error', $result['message']);
            }
            redirect('admin/settings#branding');
        }

        if ($this->input->post('save_website_favicon')) {
            $result = platform_save_branding_upload('favicon');
            if ($result['ok']) {
                $this->_log_platform_audit('settings.branding', array(
                    'target_type' => 'settings',
                    'details' => 'Uploaded website favicon',
                ));
                $this->session->set_flashdata('admin_message', $result['message']);
            } else {
                $this->session->set_flashdata('admin_error', $result['message']);
            }
            redirect('admin/settings#branding');
        }

        if ($this->input->post('remove_website_logo')) {
            platform_remove_branding_asset('logo');
            $this->_log_platform_audit('settings.branding', array(
                'target_type' => 'settings',
                'details' => 'Removed website logo',
            ));
            $this->session->set_flashdata('admin_message', 'Website logo removed.');
            redirect('admin/settings#branding');
        }

        if ($this->input->post('remove_website_favicon')) {
            platform_remove_branding_asset('favicon');
            $this->_log_platform_audit('settings.branding', array(
                'target_type' => 'settings',
                'details' => 'Removed website favicon',
            ));
            $this->session->set_flashdata('admin_message', 'Favicon removed.');
            redirect('admin/settings#branding');
        }

        if ($this->input->post('save_settings')) {
            $trial_days = (int) $this->input->post('default_trial_days');
            if ($trial_days < 1) {
                $trial_days = 14;
            }
            $grace_days = (int) $this->input->post('paid_plan_grace_days');
            if ($grace_days < 1) {
                $grace_days = 7;
            }
            $default_currency = strtoupper(trim((string) $this->input->post('default_currency')));
            if ($default_currency === '') {
                $default_currency = 'NGN';
            }
            if (
                $this->Platform_settings_model->set('default_trial_days', $trial_days)
                && $this->Platform_settings_model->set('paid_plan_grace_days', $grace_days)
                && $this->Platform_settings_model->set('default_currency', $default_currency)
                && $this->Platform_settings_model->set('marketing_hero_title', $this->input->post('marketing_hero_title'))
                && $this->Platform_settings_model->set('marketing_hero_subtitle', $this->input->post('marketing_hero_subtitle'))
            ) {
                $this->_log_platform_audit('settings.update', array(
                    'target_type' => 'settings',
                    'details' => 'Updated platform settings',
                ));
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
        $data['paid_plan_grace_days'] = $this->Platform_settings_model->get_paid_plan_grace_days();
        $data['default_currency'] = $this->Platform_settings_model->get_default_currency();
        $data['marketing_hero_title'] = $this->Platform_settings_model->get('marketing_hero_title', '');
        $data['marketing_hero_subtitle'] = $this->Platform_settings_model->get('marketing_hero_subtitle', '');
        $data['website_logo_url'] = platform_website_logo_url();
        $data['website_favicon_url'] = platform_website_favicon_url();
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

    function download_database_backup()
    {
        $this->_require_platform_admin();

        $db_name = trim((string) getenv('DATABASE_NAME'));
        if ($db_name === '') {
            show_error('Database configuration is missing.', 500);
        }

        $this->_log_platform_audit('backup.download', array(
            'target_type' => 'database',
            'target_label' => $db_name,
            'details' => 'Downloaded full database SQL backup',
        ));

        platform_admin_stream_database_backup();
    }

    function trial_registrations()
    {
        $this->_require_platform_admin();

        $search_query = trim((string) $this->input->get('search_query'));
        $data['search_query'] = $search_query;
        $data['registrations'] = $this->Trial_registration_request_model->get_all(array(
            'search' => $search_query,
        ));
        $data['default_trial_days'] = $this->Platform_settings_model->get_default_trial_days();
        $data['pricing_tiers'] = $this->Platform_settings_model->get_pricing_tiers(false);
        $open_lead_id = (int) $this->input->get('from_lead');
        $data['open_lead_id'] = 0;
        if ($open_lead_id > 0) {
            $lead = $this->Trial_registration_request_model->get_by_id($open_lead_id);
            if ($lead && empty($lead['company_id'])) {
                $data['open_lead_id'] = $open_lead_id;
            }
        }
        $data['main_content'] = 'admin/trial_registrations';
        $data['js_files'] = array(
            base_url() . auto_version('js/admin/saas-admin.js'),
            base_url() . auto_version('js/admin/trial-registrations.js'),
        );
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

        $tiers = $this->Platform_settings_model->get_pricing_tiers(false);
        foreach ($properties as &$property) {
            $this->_enrich_tenant_subscription_fields($property, $tiers);
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

    function property_detail($company_id = null)
    {
        $this->_require_platform_admin();

        $company_id = (int) $company_id;
        if ($company_id < 1) {
            show_404();
            return;
        }

        $tenant = $this->Admin_model->get_tenant_detail($company_id);
        if (!$tenant) {
            show_404();
            return;
        }

        $tiers = $this->Platform_settings_model->get_pricing_tiers(false);
        $this->_enrich_tenant_subscription_fields($tenant, $tiers);

        $tier_id = resolve_company_pricing_tier_id($tenant, $tiers);
        $default_payment_amount = isset($tenant['renewal_cost']) ? (float) $tenant['renewal_cost'] : 0;
        if ($default_payment_amount <= 0 && $tier_id > 0) {
            $tier_row = $this->Platform_settings_model->get_pricing_tier($tier_id);
            if ($tier_row && isset($tier_row['monthly_price'])) {
                $default_payment_amount = (float) $tier_row['monthly_price'];
            }
        }

        $payments = $this->Admin_model->get_saas_subscription_payments_for_company($company_id);
        $payment_total = 0.0;
        foreach ($payments as $payment) {
            $payment_total += isset($payment['amount']) ? (float) $payment['amount'] : 0;
        }

        $data['tenant'] = $tenant;
        $data['company_id'] = $company_id;
        $data['pricing_tiers'] = $tiers;
        $data['default_payment_amount'] = $default_payment_amount;
        $data['default_grace_days'] = saas_paid_plan_grace_days();
        $data['default_trial_days'] = $this->Platform_settings_model->get_default_trial_days();
        $data['subscription_payments'] = $payments;
        $data['payment_total'] = $payment_total;
        $data['activity_log'] = $this->Admin_model->get_company_log($company_id, 100);
        $data['booking_stats'] = $this->Admin_model->get_tenant_booking_stats($company_id);
        $data['setup_progress'] = $this->Admin_model->get_tenant_setup_progress($company_id);
        $data['extensions'] = $this->Admin_model->get_tenant_active_extensions($company_id);
        $data['channels'] = $this->Admin_model->get_tenant_channel_summary($company_id);
        $data['activity_log_enabled'] = $this->db->table_exists('company_log');
        $data['saas_currency_symbol'] = saas_currency_symbol();
        $data['main_content'] = 'admin/property_detail';
        $data['js_files'] = array(base_url() . auto_version('js/admin/tenant-detail.js'));
        $data['css_files'] = array();

        $this->load->view('includes/admin_template', $data);
    }

    function save_admin_comment()
    {
        $this->_require_platform_admin(true);

        $company_id = (int) $this->input->post('company_id');
        $comment = trim((string) $this->input->post('admin_comment'));

        if (!$company_id) {
            $this->_json_response(array('success' => false, 'error' => 'company_id is required.'), 400);
            return;
        }

        $tenant = $this->Admin_model->get_tenant_detail($company_id);
        if (!$tenant) {
            $this->_json_response(array('success' => false, 'error' => 'Property not found.'), 404);
            return;
        }

        $panel = $this->Admin_model->get_single_company_admin_panel_info($company_id);
        if ($panel) {
            $this->Admin_model->update_company_admin_panel_info($company_id, array(
                'admin_comment' => $comment,
            ));
        } else {
            $this->Admin_model->insert_company_admin_panel_info(array(
                'company_id' => $company_id,
                'creation_date' => date('Y-m-d G:i'),
                'admin_comment' => $comment,
            ));
        }

        $this->_log_admin_action($company_id, 'Updated internal admin notes.', 'action');
        $this->_json_response(array('success' => true));
    }

    function add_activity_note()
    {
        $this->_require_platform_admin(true);

        $company_id = (int) $this->input->post('company_id');
        $note = trim((string) $this->input->post('note'));

        if (!$company_id || $note === '') {
            $this->_json_response(array('success' => false, 'error' => 'company_id and note are required.'), 400);
            return;
        }

        $tenant = $this->Admin_model->get_tenant_detail($company_id);
        if (!$tenant) {
            $this->_json_response(array('success' => false, 'error' => 'Property not found.'), 404);
            return;
        }

        if (!$this->db->table_exists('company_log')) {
            $this->_json_response(array(
                'success' => false,
                'error' => 'Activity log is unavailable until database migrations are run.',
            ), 503);
            return;
        }

        $log_id = $this->Admin_model->add_company_log_entry($company_id, $note, 'note');
        if (!$log_id) {
            $this->_json_response(array('success' => false, 'error' => 'Note could not be saved.'), 500);
            return;
        }

        $this->_json_response(array(
            'success' => true,
            'log_id' => (int) $log_id,
        ));
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

        $this->_log_admin_action($company_id, 'Assigned owner: ' . $owner_email);

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

        $log_parts = array();
        if (isset($update['subscription_state'])) {
            $log_parts[] = 'state → ' . $update['subscription_state'];
        }
        if ($tier_id > 0 && isset($tier['name'])) {
            $log_parts[] = 'tier → ' . $tier['name'];
        }
        if (!empty($log_parts)) {
            $this->_log_admin_action($company_id, 'Updated subscription: ' . implode(', ', $log_parts), 'action', 'subscription.update');
        }

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
        $this->_log_admin_action($company_id, 'Updated property details.');
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

        if (!empty($company['is_deleted'])) {
            $this->_json_response(array('success' => false, 'error' => 'Property is already deleted.'), 400);
            return;
        }

        if ($confirm_name === '' || strcasecmp($confirm_name, $company['name']) !== 0) {
            $this->_json_response(array(
                'success' => false,
                'error' => 'Type the exact property name to confirm deletion.',
            ), 400);
            return;
        }

        $this->Company_model->update_company($company_id, array('is_deleted' => 1));
        $this->Company_subscription_model->update_company_subscription($company_id, array(
            'subscription_state' => 'deleted',
        ));

        if ($this->db->_error_message()) {
            $this->_json_response(array(
                'success' => false,
                'error' => 'Property could not be deleted. Check server logs.',
            ), 500);
            return;
        }

        if ((int) $this->session->userdata('current_company_id') === $company_id) {
            $this->session->unset_userdata('current_company_id');
        }

        $this->_log_admin_action($company_id, 'Soft-deleted property.', 'action', 'property.soft_delete');

        $this->_json_response(array('success' => true, 'company_id' => $company_id));
    }

    function restore_property()
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

        if (empty($company['is_deleted'])) {
            $this->_json_response(array('success' => false, 'error' => 'Property is not deleted.'), 400);
            return;
        }

        $this->Company_model->update_company($company_id, array('is_deleted' => 0));

        $subscription = $this->Company_subscription_model->get_company_subscription($company_id);
        $restore_state = 'canceled';
        if ($subscription && isset($subscription['subscription_state']) && $subscription['subscription_state'] === 'deleted') {
            $this->Company_subscription_model->update_company_subscription($company_id, array(
                'subscription_state' => $restore_state,
            ));
        }

        if ($this->db->_error_message()) {
            $this->_json_response(array(
                'success' => false,
                'error' => 'Property could not be restored. Check server logs.',
            ), 500);
            return;
        }

        $this->_log_admin_action($company_id, 'Restored soft-deleted property.', 'action', 'property.restore');

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

        $this->_log_admin_action($company_id, 'Set trial expiry to ' . $expiry);

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

        $this->_log_admin_action($company_id, 'Set plan expiry to ' . $plan_expiry_date);

        $this->_json_response(array(
            'success' => true,
            'plan_expiry_date' => $plan_expiry_date,
        ));
    }

    function _enrich_tenant_subscription_fields(&$property, $tiers)
    {
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

    function _log_admin_action($company_id, $message, $log_type = 'action', $platform_audit_action = 'tenant.action')
    {
        if ($this->db->table_exists('company_log')) {
            $this->Admin_model->add_company_log_entry((int) $company_id, (string) $message, $log_type);
        }

        if ($platform_audit_action) {
            $company = $this->Company_model->get_company((int) $company_id);
            $this->_log_platform_audit($platform_audit_action, array(
                'target_type' => 'company',
                'target_id' => (int) $company_id,
                'target_label' => ($company && isset($company['name'])) ? $company['name'] : null,
                'details' => (string) $message,
            ), (int) $company_id);
        }
    }

    function _log_platform_audit($action, $data = array(), $company_id = null)
    {
        if ($company_id !== null && empty($data['target_id'])) {
            $data['target_id'] = (int) $company_id;
        }
        if ($company_id !== null && empty($data['target_type'])) {
            $data['target_type'] = 'company';
        }
        platform_audit_log($action, $data);
    }

    function _require_platform_admin($json = false)
    {
        if (!$this->_is_platform_admin()) {
            if ($json) {
                $this->_json_response(array('success' => false, 'error' => 'Platform admin access required.'), 403);
                exit;
            }
            if ($this->tank_auth->is_logged_in()) {
                show_error('Platform admin access required.', 403);
            }
            redirect('admin/login');
        }

        if ($this->_platform_admin_needs_2fa_verification()) {
            if ($json) {
                $this->_json_response(array('success' => false, 'error' => 'Two-factor authentication required.'), 403);
                exit;
            }
            redirect('admin/verify_2fa');
        }
    }

    function _platform_admin_needs_2fa_verification()
    {
        if (!$this->tank_auth->is_logged_in()) {
            return false;
        }

        $user_id = $this->user_id ? $this->user_id : (int) $this->session->userdata('user_id');
        $email = isset($this->user_email) && $this->user_email
            ? $this->user_email
            : $this->session->userdata('email');

        if (!$this->Platform_admin_model->is_2fa_enabled($user_id, $email)) {
            return false;
        }

        return !(bool) $this->session->userdata('platform_admin_2fa_verified');
    }

    function _verify_platform_admin_totp($user_id, $email, $code)
    {
        $record = $this->Platform_admin_model->get_record_for_user($user_id, $email);
        if (!$record || empty($record['totp_secret'])) {
            return false;
        }

        $secret = platform_admin_decrypt_secret($record['totp_secret']);
        if ($secret === '') {
            return false;
        }

        return Totp::verify($secret, $code);
    }

    function _current_platform_admin_record()
    {
        $user_id = $this->user_id ? $this->user_id : (int) $this->session->userdata('user_id');
        $email = isset($this->user_email) && $this->user_email
            ? $this->user_email
            : $this->session->userdata('email');

        return $this->Platform_admin_model->get_record_for_user($user_id, $email);
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

    function _webhooks_configured()
    {
        $settings = $this->Platform_communication_model->get_webhook_settings();
        $slack = trim((string) $settings['slack_webhook_url']);
        $webhook = trim((string) $settings['webhook_url']);

        return $slack !== '' || $webhook !== '';
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

    function _stream_csv($filename, $headers, $rows)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9._-]+/', '-', $filename) . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    function _build_funnel_summary($funnel)
    {
        if (!is_array($funnel) || empty($funnel)) {
            return array(
                'latest_month' => null,
                'total_sign_ups' => 0,
                'completed_setup_wizard' => 0,
                'completed_tutorial' => 0,
                'created_booking' => 0,
            );
        }

        ksort($funnel);
        $months = array_keys($funnel);
        $latest = end($months);
        $row = isset($funnel[$latest]) && is_array($funnel[$latest]) ? $funnel[$latest] : array();

        return array(
            'latest_month' => $latest,
            'total_sign_ups' => isset($row['total_sign_ups']) ? (int) $row['total_sign_ups'] : 0,
            'completed_setup_wizard' => isset($row['completed_setup_wizard']) ? (int) $row['completed_setup_wizard'] : 0,
            'completed_tutorial' => isset($row['completed_tutorial']) ? (int) $row['completed_tutorial'] : 0,
            'created_booking' => isset($row['created_booking']) ? (int) $row['created_booking'] : 0,
            'new_users' => isset($row['new_users']) ? (int) $row['new_users'] : 0,
            'ran_night_audit' => isset($row['ran_night_audit']) ? (int) $row['ran_night_audit'] : 0,
        );
    }

    function _build_room_size_table_rows($data_by_month, $month_limit = 12, $is_currency = false)
    {
        if (!is_array($data_by_month) || empty($data_by_month)) {
            return array('months' => array(), 'buckets' => array(), 'rows' => array());
        }

        ksort($data_by_month);
        $months = array_keys($data_by_month);
        if ($month_limit > 0 && count($months) > $month_limit) {
            $months = array_slice($months, -$month_limit);
        }

        $bucket_totals = array();
        foreach ($months as $month) {
            if (!isset($data_by_month[$month]) || !is_array($data_by_month[$month])) {
                continue;
            }
            foreach ($data_by_month[$month] as $bucket => $value) {
                $num = $is_currency ? (float) $value : (int) $value;
                if (!isset($bucket_totals[$bucket])) {
                    $bucket_totals[$bucket] = 0;
                }
                $bucket_totals[$bucket] += $num;
            }
        }

        arsort($bucket_totals);
        $buckets = array_keys($bucket_totals);
        if (count($buckets) > 10) {
            $buckets = array_slice($buckets, 0, 10);
        }

        $rows = array();
        foreach ($months as $month) {
            $cells = array();
            foreach ($buckets as $bucket) {
                $raw = isset($data_by_month[$month][$bucket]) ? $data_by_month[$month][$bucket] : 0;
                $cells[$bucket] = $is_currency ? (float) $raw : (int) $raw;
            }
            $rows[] = array('month' => $month, 'cells' => $cells);
        }

        return array(
            'months' => $months,
            'buckets' => $buckets,
            'rows' => $rows,
        );
    }

    function _build_funnel_chart_series($funnel, $month_count = 24)
    {
        $labels = array();
        $signups = array();
        $setup_wizard = array();
        $tutorial = array();
        $first_booking = array();
        $all_values = array();

        $cursor = new DateTime('first day of this month');
        $cursor->modify('-' . ((int) $month_count - 1) . ' months');

        for ($i = 0; $i < $month_count; $i++) {
            $month_key = $cursor->format('Y-m');
            $labels[] = $month_key;
            $row = (is_array($funnel) && isset($funnel[$month_key]) && is_array($funnel[$month_key]))
                ? $funnel[$month_key]
                : array();

            $signup_val = isset($row['total_sign_ups']) ? (int) $row['total_sign_ups'] : 0;
            $setup_val = isset($row['completed_setup_wizard']) ? (int) $row['completed_setup_wizard'] : 0;
            $tutorial_val = isset($row['completed_tutorial']) ? (int) $row['completed_tutorial'] : 0;
            $booking_val = isset($row['created_booking']) ? (int) $row['created_booking'] : 0;

            $signups[] = $signup_val;
            $setup_wizard[] = $setup_val;
            $tutorial[] = $tutorial_val;
            $first_booking[] = $booking_val;
            $all_values = array_merge($all_values, array($signup_val, $setup_val, $tutorial_val, $booking_val));

            $cursor->modify('+1 month');
        }

        $max_value = !empty($all_values) ? max($all_values) : 0;

        return array(
            'labels' => $labels,
            'signups' => $signups,
            'setupWizard' => $setup_wizard,
            'tutorial' => $tutorial,
            'firstBooking' => $first_booking,
            'maxValue' => $max_value,
        );
    }

    function _build_admin_growth_chart_payload($funnel, $subscriptions_by_room, $charges_by_room)
    {
        $funnel_series = $this->_build_funnel_chart_series($funnel, 24);

        $room_labels = array();
        $room_totals = array();
        if (is_array($subscriptions_by_room) && !empty($subscriptions_by_room)) {
            ksort($subscriptions_by_room);
            $room_months = array_keys($subscriptions_by_room);
            if (count($room_months) > 12) {
                $room_months = array_slice($room_months, -12);
            }
            foreach ($room_months as $month) {
                $room_labels[] = $month;
                $sum = 0;
                if (isset($subscriptions_by_room[$month]) && is_array($subscriptions_by_room[$month])) {
                    foreach ($subscriptions_by_room[$month] as $count) {
                        $sum += (int) $count;
                    }
                }
                $room_totals[] = $sum;
            }
        }

        $charge_labels = array();
        $charge_totals = array();
        if (is_array($charges_by_room) && !empty($charges_by_room)) {
            ksort($charges_by_room);
            $charge_months = array_keys($charges_by_room);
            if (count($charge_months) > 12) {
                $charge_months = array_slice($charge_months, -12);
            }
            foreach ($charge_months as $month) {
                $charge_labels[] = $month;
                $sum = 0.0;
                if (isset($charges_by_room[$month]) && is_array($charges_by_room[$month])) {
                    foreach ($charges_by_room[$month] as $amount) {
                        $sum += (float) $amount;
                    }
                }
                $charge_totals[] = round($sum, 2);
            }
        }

        return array(
            'funnel' => $funnel_series,
            'subscriptionsByMonth' => array(
                'labels' => $room_labels,
                'totals' => $room_totals,
            ),
            'chargesByMonth' => array(
                'labels' => $charge_labels,
                'totals' => $charge_totals,
            ),
            'currencySymbol' => saas_currency_symbol(),
        );
    }

    /**
     * Friendly label for extension slugs in adoption charts.
     *
     * @param string $extension_name
     * @return string
     */
    function _extension_display_name($extension_name)
    {
        $map = array(
            'channex-integration' => 'Channex',
            'channexpci_integration' => 'Channex PCI',
            'whatsapp-booking-management' => 'WhatsApp',
            'paystack_integration' => 'Paystack',
            'booking_engine' => 'Booking engine',
            'ttlock' => 'TTLock',
        );
        $name = (string) $extension_name;
        if (isset($map[$name])) {
            return $map[$name];
        }

        return ucwords(str_replace(array('-', '_'), ' ', $name));
    }
}
