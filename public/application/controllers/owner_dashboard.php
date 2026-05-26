<?php

class Owner_dashboard extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Owners often also have is_admin; user_permission may be is_admin (see get_user_by_id).
        if (!$this->User_model->is_property_owner($this->user_id, $this->company_id)) {
            show_error('You do not have permission to access this page.', 403);
            exit;
        }

        $this->load->model('Owner_dashboard_model');
        $this->load->model('Currency_model');

        $language = $this->session->userdata('language');
        $this->lang->load('menu', $language ? $language : 'english');

        $this->load->vars(array('menu_on' => true));
    }

    public function index()
    {
        $selling_date = $this->session->userdata('current_selling_date');
        if (!$selling_date) {
            $company      = $this->Company_model->get_company($this->company_id);
            $selling_date = $company['selling_date'];
        }

        $currency_symbol = $this->session->userdata('currency_symbol');
        if (!$currency_symbol) {
            $default_currency = $this->Currency_model->get_default_currency($this->company_id);
            $currency_symbol  = isset($default_currency['currency_code']) ? $default_currency['currency_code'] : '$';
            $this->session->set_userdata(array('currency_symbol' => $currency_symbol));
        }

        $kpi         = $this->Owner_dashboard_model->get_kpi_summary($this->company_id, $selling_date);
        $monthly_rev = $this->Owner_dashboard_model->get_monthly_revenue($this->company_id, $selling_date, 12);
        $monthly_occ = $this->Owner_dashboard_model->get_monthly_occupancy($this->company_id, $selling_date, 12);
        $sources     = $this->Owner_dashboard_model->get_booking_source_breakdown($this->company_id, $selling_date);
        $top_rooms   = $this->Owner_dashboard_model->get_top_rooms_by_revenue($this->company_id, $selling_date);
        $fwd_occ     = $this->Owner_dashboard_model->get_forward_occupancy($this->company_id, $selling_date, 30);
        $recent      = $this->Owner_dashboard_model->get_recent_bookings($this->company_id, 10);
        $staff_count = $this->Owner_dashboard_model->get_staff_count($this->company_id);
        $ytd_revenue = $this->Owner_dashboard_model->get_ytd_revenue($this->company_id, $selling_date);

        // Build JS-ready payloads
        $rev_labels   = array();
        $rev_values   = array();
        foreach ($monthly_rev as $m) {
            $rev_labels[] = $m['label'];
            $rev_values[] = $m['revenue'];
        }

        $occ_labels = array();
        $occ_values = array();
        foreach ($monthly_occ as $m) {
            $occ_labels[] = $m['label'];
            $occ_values[] = $m['occupancy'];
        }

        $source_labels = array();
        $source_values = array();
        foreach ($sources as $s) {
            $source_labels[] = $s['source'];
            $source_values[] = (int) $s['count'];
        }

        $room_labels  = array();
        $room_revenue = array();
        foreach ($top_rooms as $r) {
            $room_labels[]  = $r['room_name'];
            $room_revenue[] = round((float) $r['revenue'], 2);
        }

        $data = array(
            'selling_date'    => $selling_date,
            'selling_date_label' => date('l, F j, Y', strtotime($selling_date)),
            'currency_symbol' => $currency_symbol,
            'kpi'             => $kpi,
            'ytd_revenue'     => $ytd_revenue,
            'staff_count'     => $staff_count,
            'recent_bookings' => $recent,
            'top_rooms'       => $top_rooms,
            'chart_data'      => json_encode(array(
                'currency'     => $currency_symbol,
                'revLabels'    => $rev_labels,
                'revValues'    => $rev_values,
                'occLabels'    => $occ_labels,
                'occValues'    => $occ_values,
                'sourceLabels' => $source_labels,
                'sourceValues' => $source_values,
                'roomLabels'   => $room_labels,
                'roomRevenue'  => $room_revenue,
                'fwdLabels'    => $fwd_occ['labels'],
                'fwdValues'    => $fwd_occ['values'],
            )),
            'selected_menu'   => 'owner_dashboard',
            'css_files'       => array(
                base_url() . auto_version('css/owner_dashboard/owner_dashboard.css'),
            ),
            'js_files'        => array(
                base_url() . 'js/Chart.min.js',
                base_url() . auto_version('js/owner_dashboard/owner_dashboard.js'),
            ),
            'main_content'    => 'owner_dashboard/index',
        );

        $this->load->view('includes/bootstrapped_template', $data);
    }
}
