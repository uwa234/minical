<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Corporate (city ledger) accounts: credit limits, payment terms and aged statements.
 */
class Corporate_account extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->model('Corporate_account_model');
        $this->load->model('Company_model');
        $this->load->helper('veurion_ui');
    }

    function index()
    {
        $selling_date = $this->_selling_date();
        $accounts = $this->Corporate_account_model->get_accounts($this->company_id, $selling_date);
        foreach ($accounts as $i => $account) {
            $accounts[$i]['available'] = $account['credit_limit'] !== null
                ? round((float) $account['credit_limit'] - (float) $account['balance'], 2)
                : null;
            $accounts[$i]['over_limit'] = $account['credit_limit'] !== null && (float) $account['balance'] > (float) $account['credit_limit'];
        }

        $data = $this->_base_data();
        $data['accounts'] = $accounts;
        $data['customers'] = $this->Corporate_account_model->get_customers_for_select($this->company_id);
        $data['main_content'] = 'corporate_account/index';
        $this->load->view('includes/bootstrapped_template', $data);
    }

    function save()
    {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('corporate_account');
        }

        $terms = $this->_account_terms();
        $customer_id = (int) $this->input->post('customer_id');
        $new_name = trim((string) $this->input->post('customer_name', TRUE));

        if (!$customer_id && $new_name !== '') {
            $customer_id = $this->Corporate_account_model->create_customer($this->company_id, array(
                'customer_name' => $new_name,
                'email' => trim((string) $this->input->post('email', TRUE)),
                'phone' => trim((string) $this->input->post('phone', TRUE)),
                'address' => trim((string) $this->input->post('address', TRUE)),
            ));
        }

        if (!$customer_id || !$this->Corporate_account_model->get_account($this->company_id, $customer_id)) {
            $this->session->set_flashdata('flash_warning_message', l('Choose an existing customer or enter a company name.', true));
            redirect('corporate_account');
        }

        $this->Corporate_account_model->update_account($this->company_id, $customer_id, array_merge($terms, array('is_corporate_account' => 1)));
        redirect('corporate_account/statement/' . $customer_id);
    }

    function update($customer_id = null)
    {
        if ($this->input->server('REQUEST_METHOD') !== 'POST' || !$this->Corporate_account_model->get_account($this->company_id, $customer_id)) {
            redirect('corporate_account');
        }

        if ($this->input->post('remove')) {
            $this->Corporate_account_model->update_account($this->company_id, $customer_id, array('is_corporate_account' => 0));
            redirect('corporate_account');
        }

        $this->Corporate_account_model->update_account($this->company_id, $customer_id, $this->_account_terms());
        redirect('corporate_account/statement/' . (int) $customer_id);
    }

    function statement($customer_id = null)
    {
        $account = $this->Corporate_account_model->get_account($this->company_id, $customer_id);
        if (!$account) {
            show_404();
        }

        $selling_date = $this->_selling_date();
        $buckets = array('current' => 0, '31_60' => 0, '61_90' => 0, 'over_90' => 0);
        $bookings = $this->Corporate_account_model->get_open_bookings($this->company_id, $customer_id);
        $balance = 0;
        $overdue = 0;
        foreach ($bookings as $i => $booking) {
            $days = $booking['check_out_date'] ? (int) floor((strtotime($selling_date) - strtotime($booking['check_out_date'])) / 86400) : 0;
            $days = max(0, $days);
            $bookings[$i]['days'] = $days;
            $bookings[$i]['overdue'] = $account['payment_terms_days'] !== null && $days > (int) $account['payment_terms_days'];
            $amount = (float) $booking['balance'];
            $balance += $amount;
            if ($bookings[$i]['overdue']) {
                $overdue += $amount;
            }
            if ($days <= 30) {
                $buckets['current'] += $amount;
            } elseif ($days <= 60) {
                $buckets['31_60'] += $amount;
            } elseif ($days <= 90) {
                $buckets['61_90'] += $amount;
            } else {
                $buckets['over_90'] += $amount;
            }
        }

        $data = $this->_base_data();
        $data['account'] = $account;
        $data['bookings'] = $bookings;
        $data['payments'] = $this->Corporate_account_model->get_recent_payments($this->company_id, $customer_id);
        $data['buckets'] = $buckets;
        $data['balance'] = round($balance, 2);
        $data['overdue'] = round($overdue, 2);
        $data['selling_date'] = $selling_date;
        $data['company'] = $this->Company_model->get_company($this->company_id);
        $data['main_content'] = 'corporate_account/statement';
        $this->load->view('includes/bootstrapped_template', $data);
    }

    private function _account_terms()
    {
        $limit = $this->input->post('credit_limit');
        $terms = $this->input->post('payment_terms_days');
        return array(
            'credit_limit' => ($limit === '' || $limit === false || $limit === null) ? null : max(0, round((float) $limit, 2)),
            'payment_terms_days' => ($terms === '' || $terms === false || $terms === null) ? null : max(0, (int) $terms),
        );
    }

    private function _selling_date()
    {
        $selling_date = $this->Company_model->get_selling_date($this->company_id);
        return $selling_date ? $selling_date : date('Y-m-d');
    }

    private function _base_data()
    {
        return array_merge(vx_page_assets('customer'), array(
            'currency_symbol' => $this->session->userdata('currency_symbol') ? $this->session->userdata('currency_symbol') : '₦',
        ));
    }
}
