<?php

class Account_settings extends MY_Controller {
	
	function __construct()
	{
		parent::__construct();		
		
        $auth_required = true;
        
        if ($this->router->method === 'paystack_webhook') {
            $auth_required = false;
        }
		
		$this->load->model('Company_model');
		$this->load->model('Company_subscription_model');
		$this->load->model('Platform_settings_model');
		$this->load->model('Company_charge_model');
		$this->load->model('Company_payment_model');
		$this->load->model('Admin_model');
		$this->load->model('User_model');
                // Load Translation Model for Language Translation
		$this->load->model('translation_model');
                
		$this->load->library('form_validation');
		$this->load->library('Paystack');
                // Load language Translation Helper
                $this->load->helper('language_translation');

		$language = $this->session->userdata('language');
		$this->lang->load('dashboard', $language ? $language : 'english');
		
		$view_data['menu_on'] = true;
		$view_data['selected_menu'] = 'my account';		
		$view_data['submenu'] = 'account_settings/account_settings_submenu.php';
		
		$this->load->vars($view_data);
    }
	
	function index()
	{		
		$this->password();
	}
	
	function password()
	{
		$old_password = $this->input->post('old_password');
		$new_password = $this->input->post('new_password');
		
		// updating the password happens here while checking for _incorrect_old_password
		$this->form_validation->set_rules('old_password', 'Old Password', 'trim|xss_clean');
		$this->form_validation->set_rules('new_password', 'New Password', 'required|trim|xss_clean');
		$this->form_validation->set_rules('confirm_new_password', 'Confirm new Password', 'required|trim|xss_clean|matches[new_password]');

		// this validation actually changes password
		$this->form_validation->set_rules('old_password', 'Old Password', 'callback__incorrect_old_password['.$new_password.']');

		if ($this->form_validation->run()) // validation ok
		{
			echo "<script>alert('successfully updated password!');</script>";
		}

		$data['selected_submenu'] = 'password';
		$data['main_content'] = 'account_settings/password';
		
		$this->load->view('includes/bootstrapped_template',$data);
	}

	/**
	 * Updates the password. 
	 * If the old password is incorrectly entered, then returns form validation error
	 *
	 * @access	public
	 * @param	string, string (from custom Form validation)
	 * @return	boolean
	 */
	function _incorrect_old_password($old_password, $new_password)
	{
		if ($this->tank_auth->change_password($old_password, $new_password)) 
		{
			return true;
		}
		else // update password failed
		{		
			$this->form_validation->set_message('_incorrect_old_password', 'Incorrect old password');
			return false;
		}
	}

	function language() {
		$this->form_validation->set_rules('language', 'Language', 'required');
		if ($this->form_validation->run()) 
		{
			// update language settings in database
			$explode = explode(',', $this->input->post('language'));
                        $language_id = $explode[0];
                        $new_language = $explode[1];
                        $this->User_model->update_user_profile($this->user_id, Array(
                            'language' => $new_language,
                            'language_id' => $language_id
                        ));
				
			// apply language change immediately by updating session variable
			$this->session->set_userdata(array( 'language' => $new_language ));
            $this->session->set_userdata(array( 'language_id' => $language_id ));
            // Call function to load translation of language
            load_translations($language_id);
            
            if($this->input->is_ajax_request())
            {
                echo l('success',true);
                return;
            }
            else
            {
                redirect('/account_settings/language');
            }
		}
		
		$data['selected_submenu'] = 'language';
		$data['current_language'] = $this->session->userdata('language');
		$data['main_content'] = 'account_settings/language';
		
		$this->load->view('includes/bootstrapped_template',$data);
		
	}

	function subscription()
	{
		if ($this->_deny_billing_for_housekeeping()) {
			return;
		}

		$language = $this->session->userdata('language');
		$this->lang->load('dashboard', $language ? $language : 'english');

		$trial_lockout_mode = !empty($this->trial_lockout_active);
		$trial_context = build_trial_subscription_context($this->company_data);
		$plan_expiry_context = build_paid_plan_expiry_context($this->company_data);

		$data = array(
			'selected_submenu' => 'subscription',
			'trial_lockout_mode' => $trial_lockout_mode,
			'trial_context' => $trial_context,
			'subscription_state' => $this->company_subscription_state,
			'current_plan_name' => $trial_context
				? $trial_context['current_plan_name']
				: subscription_level_display_name($this->company_subscription_level),
			'pricing_tiers' => $this->Platform_settings_model->get_pricing_tiers(true),
			'room_count' => $trial_context
				? $trial_context['room_count']
				: (isset($this->company_data['number_of_rooms_actual']) ? (int) $this->company_data['number_of_rooms_actual'] : 1),
			'paystack_configured' => $this->_paystack_secret_key() !== '',
			'subscription_flash' => $this->session->flashdata('subscription_flash'),
			'plan_expiry_context' => $plan_expiry_context,
			'css_files' => array(base_url() . auto_version('css/dashboard/dashboard.css')),
			'js_files' => array(
				base_url() . auto_version('js/dashboard/dashboard.js'),
				base_url() . auto_version('js/account_settings/subscription.js'),
			),
			'main_content' => 'account_settings/subscription',
		);

		if ($trial_lockout_mode) {
			$data['submenu'] = null;
			$data['menu_on'] = false;
		}

		$this->load->view('includes/bootstrapped_template', $data);
	}

	function paystack_checkout()
	{
		if (!$this->tank_auth->is_logged_in()) {
			return $this->_json_response(array('success' => false, 'error' => 'Please log in to continue.'), 401);
		}
		if ($this->_deny_billing_for_housekeeping(true)) {
			return;
		}

		$tier_id = (int) $this->input->post('tier_id');
		$renewal_period = strtolower(trim((string) $this->input->post('renewal_period')));
		if ($renewal_period !== 'year') {
			$renewal_period = 'month';
		}

		$tier = $this->Platform_settings_model->get_pricing_tier($tier_id);
		if (!$tier || (int) $tier['is_active'] !== 1) {
			return $this->_json_response(array('success' => false, 'error' => 'Selected plan is not available.'), 422);
		}

		$secret_key = $this->_paystack_secret_key();
		if ($secret_key === '') {
			return $this->_json_response(array('success' => false, 'error' => 'Billing is not configured yet. Please contact support.'), 500);
		}

		$email = (string) $this->session->userdata('email');
		if ($email === '' && !empty($this->company_data['owner_email'])) {
			$email = (string) $this->company_data['owner_email'];
		}
		if ($email === '') {
			$email = (string) $this->company_email;
		}
		if ($email === '') {
			return $this->_json_response(array('success' => false, 'error' => 'No billing email found for this account.'), 422);
		}

		$monthly_price = (float) $tier['monthly_price'];
		$amount_major = $renewal_period === 'year' ? ($monthly_price * 12) : $monthly_price;
		$currency = !empty($tier['currency']) ? strtoupper((string) $tier['currency']) : 'USD';
		$reference = $this->_build_paystack_reference($this->company_id);

		$metadata = array(
			'payment_context' => 'saas_subscription',
			'company_id' => (int) $this->company_id,
			'tier_id' => (int) $tier['id'],
			'subscription_level' => resolve_subscription_level_for_tier($tier),
			'renewal_period' => $renewal_period,
			'amount_major' => round($amount_major, 2),
			'currency' => $currency,
			'user_id' => (int) $this->user_id,
			'email' => $email,
		);

		$this->paystack->setSecretKey($secret_key);
		$callback_url = base_url('account_settings/paystack_callback');
		$initialized = $this->paystack->initializeTransaction(
			$email,
			Paystack::toMinorUnits($amount_major),
			$reference,
			$callback_url,
			$metadata
		);
		if (!$initialized || empty($initialized['authorization_url'])) {
			return $this->_json_response(array(
				'success' => false,
				'error' => $this->paystack->getLastError() ?: 'Unable to start payment.',
			), 502);
		}

		$this->Company_subscription_model->insert_or_update_company_subscription($this->company_id, array(
			'meta_data' => json_encode(array(
				'pending_reference' => $reference,
				'tier_id' => (int) $tier['id'],
				'renewal_period' => $renewal_period,
				'initiated_at' => gmdate('c'),
			)),
		));

		return $this->_json_response(array(
			'success' => true,
			'authorization_url' => $initialized['authorization_url'],
			'reference' => $reference,
		));
	}

	function paystack_callback()
	{
		if ($this->_deny_billing_for_housekeeping()) {
			return;
		}

		$reference = trim((string) $this->input->get('reference'));
		if ($reference === '') {
			$this->session->set_flashdata('subscription_flash', array('type' => 'danger', 'message' => 'Payment verification failed: missing reference.'));
			redirect('/account_settings/subscription');
			return;
		}

		$result = $this->_finalize_paystack_subscription_payment($reference);
		if (!empty($result['success'])) {
			$this->session->set_flashdata('subscription_flash', array('type' => 'success', 'message' => 'Payment received. Your subscription is now active.'));
			redirect('/dashboard/');
			return;
		} else {
			$this->session->set_flashdata('subscription_flash', array(
				'type' => 'danger',
				'message' => !empty($result['error']) ? $result['error'] : 'We could not verify this payment.',
			));
		}

		redirect('/account_settings/subscription');
	}

	function paystack_billing_health()
	{
		if (!$this->tank_auth->is_logged_in()) {
			return $this->_json_response(array('success' => false, 'error' => 'Please log in to continue.'), 401);
		}
		if ($this->_deny_billing_for_housekeeping(true)) {
			return;
		}

		$has_company_payment = $this->db->table_exists('company_payment');
		$has_company_payments = $this->db->table_exists('company_payments');
		$active_table = $has_company_payment ? 'company_payment' : ($has_company_payments ? 'company_payments' : null);

		return $this->_json_response(array(
			'success' => true,
			'company_id' => (int) $this->company_id,
			'company_payment_exists' => $has_company_payment,
			'company_payments_exists' => $has_company_payments,
			'active_payment_table' => $active_table,
			'migration_target_version' => (int) config_item('migration_version'),
		));
	}

	function paystack_webhook()
	{
		$raw_body = file_get_contents('php://input');
		$signature = isset($_SERVER['HTTP_X_PAYSTACK_SIGNATURE']) ? $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] : '';
		$secret_key = $this->_paystack_secret_key();

		if (!Paystack::verifyWebhookSignature($raw_body, $signature, $secret_key)) {
			return $this->_json_response(array('success' => false, 'error' => 'Invalid signature'), 401);
		}

		$event = json_decode($raw_body, true);
		if (!is_array($event)) {
			return $this->_json_response(array('success' => false, 'error' => 'Invalid payload'), 400);
		}

		if (!isset($event['event']) || $event['event'] !== 'charge.success') {
			return $this->_json_response(array('success' => true, 'message' => 'Ignored'));
		}

		$reference = isset($event['data']['reference']) ? trim((string) $event['data']['reference']) : '';
		if ($reference === '') {
			return $this->_json_response(array('success' => false, 'error' => 'Missing reference'), 422);
		}

		$result = $this->_finalize_paystack_subscription_payment($reference, isset($event['data']) ? $event['data'] : null);
		if (!empty($result['success'])) {
			return $this->_json_response(array('success' => true, 'message' => 'Processed'));
		}

		return $this->_json_response(array('success' => false, 'error' => !empty($result['error']) ? $result['error'] : 'Failed to process'), 422);
	}

	private function _finalize_paystack_subscription_payment($reference, $verified_data = null)
	{
		$reference = trim((string) $reference);
		if ($reference === '') {
			return array('success' => false, 'error' => 'Invalid payment reference.');
		}

		if (!$this->db->table_exists('company_payment') && !$this->db->table_exists('company_payments')) {
			log_message('error', 'Paystack callback failed: missing company payment table for reference ' . $reference . ' and company ' . (int) $this->company_id);
			return array('success' => false, 'error' => 'Billing records table is missing. Please contact support.');
		}

		if ($this->Company_charge_model->does_transaction_exist($this->company_id, $reference)
			&& $this->Company_payment_model->does_transaction_exist($this->company_id, $reference)) {
			return array('success' => true, 'already_processed' => true);
		}

		$data = $verified_data;
		if (!$data) {
			$this->paystack->setSecretKey($this->_paystack_secret_key());
			$data = $this->paystack->verifyTransaction($reference);
		}

		if (!$data) {
			return array('success' => false, 'error' => $this->paystack->getLastError() ?: 'Unable to verify payment.');
		}

		if (!isset($data['status']) || strtolower((string) $data['status']) !== 'success') {
			return array('success' => false, 'error' => 'Payment is not marked as successful by Paystack.');
		}

		$metadata = isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : array();
		if (!isset($metadata['payment_context']) || $metadata['payment_context'] !== 'saas_subscription') {
			return array('success' => false, 'error' => 'Unsupported payment context.');
		}

		$company_id = isset($metadata['company_id']) ? (int) $metadata['company_id'] : (int) $this->company_id;
		$tier_id = isset($metadata['tier_id']) ? (int) $metadata['tier_id'] : 0;
		$renewal_period = isset($metadata['renewal_period']) && strtolower((string) $metadata['renewal_period']) === 'year' ? '1 year' : '1 month';
		$amount_minor = isset($data['amount']) ? (int) $data['amount'] : 0;
		$amount_major = round($amount_minor / 100, 2);

		if ($company_id < 1 || $tier_id < 1 || $amount_major <= 0) {
			return array('success' => false, 'error' => 'Invalid payment payload.');
		}

		$tier = $this->Platform_settings_model->get_pricing_tier($tier_id);
		if (!$tier) {
			return array('success' => false, 'error' => 'Pricing tier no longer exists.');
		}

		if ($this->Company_charge_model->does_transaction_exist($company_id, $reference)
			|| $this->Company_payment_model->does_transaction_exist($company_id, $reference)) {
			return array('success' => true, 'already_processed' => true);
		}

		$today = gmdate('Y-m-d');
		$expiry_date = $renewal_period === '1 year'
			? gmdate('Y-m-d', strtotime($today . ' +1 year'))
			: gmdate('Y-m-d', strtotime($today . ' +1 month'));
		$charge = array(array(
			'company_id' => $company_id,
			'description' => $reference,
			'amount' => $amount_major,
			'charge_type' => 'subscription',
			'date' => $today,
			'is_deleted' => 0,
		));
		$payment = array(array(
			'company_id' => $company_id,
			'description' => $reference,
			'amount' => $amount_major,
			'date' => $today,
			'is_deleted' => 0,
		));

		$this->Company_charge_model->insert_company_charge($charge);
		$this->Company_payment_model->insert_company_payment($payment);

		$meta = array(
			'last_reference' => $reference,
			'last_transaction_id' => isset($data['id']) ? $data['id'] : null,
			'last_paid_at' => isset($data['paid_at']) ? $data['paid_at'] : gmdate('c'),
			'last_gateway' => 'paystack',
			'last_currency' => isset($data['currency']) ? strtoupper((string) $data['currency']) : 'USD',
			'tier_id' => (int) $tier['id'],
			'tier_name' => (string) $tier['name'],
			'tier_updated_at' => gmdate('c'),
		);

		$this->Company_subscription_model->insert_or_update_company_subscription($company_id, array(
			'subscription_id' => isset($data['id']) ? (string) $data['id'] : $reference,
			'subscription_state' => 'active',
			'subscription_level' => resolve_subscription_level_for_tier($tier),
			'limit_feature' => 1,
			'renewal_cost' => $amount_major,
			'renewal_period' => $renewal_period,
			'payment_method' => 'paystack',
			'expiration_date' => $expiry_date,
			'meta_data' => json_encode($meta),
		));
		$this->Company_subscription_model->update_company_balance($company_id);
		$this->Company_model->update_company_admin_panel_info($company_id, array(
			'conversion_date' => $today,
		));

		return array('success' => true);
	}

	private function _build_paystack_reference($company_id)
	{
		return 'saas_' . (int) $company_id . '_' . gmdate('YmdHis') . '_' . bin2hex(random_bytes(4));
	}

	private function _paystack_secret_key()
	{
		$primary = trim((string) minical_env('PAYSTACK_SAAS_SECRET_KEY', ''));
		if ($primary !== '') {
			return $primary;
		}
		return trim((string) minical_env('PAYSTACK_SECRET_KEY', ''));
	}

	private function _json_response($payload, $status = 200)
	{
		$this->output
			->set_status_header((int) $status)
			->set_content_type('application/json')
			->set_output(json_encode($payload));
	}

	private function _is_housekeeping_user()
	{
		if ($this->session->userdata('user_role') === 'is_housekeeping') {
			return true;
		}
		$permissions = $this->session->userdata('permissions');
		return is_array($permissions) && in_array('is_housekeeping', $permissions, true);
	}

	private function _deny_billing_for_housekeeping($json = false)
	{
		if (!$this->_is_housekeeping_user()) {
			return false;
		}
		if ($json) {
			$this->_json_response(array('success' => false, 'error' => 'You do not have permission to manage billing.'), 403);
			return true;
		}
		redirect('/auth/forbidden');
		return true;
	}

	function change_language() {
		// update language settings in database
		$explode = explode(',', $this->input->post('language'));
        $language_id = $explode[0];
        $new_language = $explode[1];
        $this->User_model->update_user_profile($this->user_id, Array(
            'language' => $new_language,
            'language_id' => $language_id
        ));
				
		// apply language change immediately by updating session variable
		$this->session->set_userdata(array( 'language' => $new_language ));
        $this->session->set_userdata(array( 'language_id' => $language_id ));
        // Call function to load translation of language
        load_translations($language_id);
        echo l('success',true);
	}
}

