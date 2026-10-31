<?php

/* 
*	Base Controller that takes care of Security & Permission (User Access) of Minical
*/
// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH.'/libraries/REST_Controller.php';

class MY_Controller extends REST_Controller {

    /** Company IDs the authenticated API key is linked to (key_x_company). */
    protected $api_company_ids = array();

    /** True when the key is listed in API_PLATFORM_KEYS and may act for any company. */
    protected $is_platform_key = false;

    public function __construct()
    {
        parent::__construct();
		$this->ci =& get_instance();

        $this->load->model(array('Booking_model'));
        $this->load->helper('image_storage');

        $this->image_url = image_storage_base_url();
        
		$this->validate_permissions();
	}

	public function validate_permissions()
	{
        $key = isset($this->rest->key) ? (string) $this->rest->key : '';
        if ($key === '') {
            // REST_Controller rejects requests without a valid key in _remap().
            return;
        }

        if (in_array($key, $this->_platform_keys(), true)) {
            $this->is_platform_key = true;
            return;
        }

        $this->api_company_ids = $this->Booking_model->get_api_key_company_ids($key);
        if (empty($this->api_company_ids)) {
            $this->_deny('API key is not linked to any company.');
        }

        // get_company_id looks up the company for a key supplied in the body.
        if ($this->router->class === 'company' && $this->router->method === 'get_company_id') {
            return;
        }

        $requested = $this->_requested_company_ids();
        if (empty($requested)) {
            if (count($this->api_company_ids) !== 1) {
                $this->_deny('company_id is required for this API key.');
            }
            $this->_post_args['company_id'] = $this->api_company_ids[0];
            $this->_get_args['company_id'] = $this->api_company_ids[0];
            return;
        }

        foreach ($requested as $company_id) {
            if (!in_array((int) $company_id, $this->api_company_ids, true)) {
                $this->_deny('Invalid API Key.');
            }
        }
	}

    /**
     * Stop the request unless the booking belongs to the request's company.
     */
    protected function assert_booking_belongs_to_company($booking_id, $company_id)
    {
        $booking = $this->Booking_model->get_company_by_booking($booking_id);
        if (!$booking || (int) $booking['company_id'] !== (int) $company_id) {
            $this->response(array('status' => false, 'error' => 'Booking not found for this company.'), 200);
            exit;
        }
        return $booking;
    }

    private function _requested_company_ids()
    {
        $ids = array();
        foreach (array($this->post('company_id'), $this->get('company_id')) as $value) {
            if ($value !== false && $value !== null && $value !== '') {
                $ids[] = $value;
            }
        }

        $booking = $this->post('booking');
        if (is_array($booking) && isset($booking['company_id']) && $booking['company_id'] !== '') {
            $ids[] = $booking['company_id'];
        }

        foreach ($ids as $value) {
            if (!ctype_digit((string) $value)) {
                $this->_deny('Invalid company_id.');
            }
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    private function _platform_keys()
    {
        $raw = isset($_SERVER['API_PLATFORM_KEYS']) ? $_SERVER['API_PLATFORM_KEYS'] : getenv('API_PLATFORM_KEYS');
        if (!$raw) {
            return array();
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw)), 'strlen'));
    }

    private function _deny($message)
    {
        $this->response(array('status' => false, 'error' => $message), 401);
        exit;
    }

}
