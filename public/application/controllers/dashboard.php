<?php

class Dashboard extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Dashboard_model');
        $this->load->model('Currency_model');

        $language = $this->session->userdata('language');
        $this->lang->load('dashboard', $language ? $language : 'english');

        $this->load->vars(array('menu_on' => true));
    }

    public function index()
    {
        $selling_date = $this->session->userdata('current_selling_date');
        if (!$selling_date) {
            $company = $this->Company_model->get_company($this->company_id);
            $selling_date = $company['selling_date'];
        }

        $snapshot = $this->Dashboard_model->get_operational_snapshot(
            $this->company_id,
            $selling_date
        );

        $analytics_days = (int) $this->input->get('days');
        if ($analytics_days < 7 || $analytics_days > 30) {
            $analytics_days = 14;
        }

        $analytics = $this->Dashboard_model->get_analytics(
            $this->company_id,
            $selling_date,
            $analytics_days
        );

        $currency_symbol = $this->session->userdata('currency_symbol');
        if (!$currency_symbol) {
            $default_currency = $this->Currency_model->get_default_currency($this->company_id);
            $currency_symbol = isset($default_currency['currency_code']) ? $default_currency['currency_code'] : '$';
            $this->session->set_userdata(array('currency_symbol' => $currency_symbol));
        }

        $trial_context = build_trial_subscription_context($this->company_data);

        $data = array(
            'selling_date' => $selling_date,
            'selling_date_label' => date('l, F j, Y', strtotime($selling_date)),
            'currency_symbol' => $currency_symbol,
            'snapshot' => $snapshot,
            'analytics' => $analytics,
            'analytics_days' => $analytics_days,
            'analytics_payload' => $this->build_analytics_payload($analytics, $currency_symbol),
            'trial_context' => $trial_context,
            'selected_menu' => 'dashboard',
            'css_files' => array(
                base_url() . auto_version('css/dashboard/dashboard.css'),
            ),
            'js_files' => array(
                base_url() . 'js/moment.min.js',
                base_url() . auto_version('js/booking/bookingModal.js'),
                base_url() . auto_version('js/dashboard/dashboard.js'),
            ),
            'main_content' => 'dashboard/index',
        );

        $this->load->view('includes/bootstrapped_template', $data);
    }

    public function analytics()
    {
        $selling_date = $this->session->userdata('current_selling_date');
        if (!$selling_date) {
            $company = $this->Company_model->get_company($this->company_id);
            $selling_date = $company['selling_date'];
        }

        $days = (int) $this->input->get('days');
        if ($days < 7 || $days > 30) {
            $days = 14;
        }

        $analytics = $this->Dashboard_model->get_analytics(
            $this->company_id,
            $selling_date,
            $days
        );

        $currency_symbol = $this->session->userdata('currency_symbol');
        if (!$currency_symbol) {
            $default_currency = $this->Currency_model->get_default_currency($this->company_id);
            $currency_symbol = isset($default_currency['currency_code']) ? $default_currency['currency_code'] : '$';
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'data' => $this->build_analytics_payload($analytics, $currency_symbol),
            )));
    }

    private function build_analytics_payload($analytics, $currency_symbol)
    {
        $summary = isset($analytics['summary']) ? $analytics['summary'] : array();
        $days = isset($analytics['days']) ? (int) $analytics['days'] : 14;

        return array(
            'labels' => isset($analytics['labels']) ? $analytics['labels'] : array(),
            'revenue' => isset($analytics['revenue']) ? $analytics['revenue'] : array(),
            'occupancy' => isset($analytics['occupancy']) ? $analytics['occupancy'] : array(),
            'arrivals' => isset($analytics['arrivals']) ? $analytics['arrivals'] : array(),
            'days' => $days,
            'startDate' => isset($analytics['start_date']) ? $analytics['start_date'] : '',
            'endDate' => isset($analytics['end_date']) ? $analytics['end_date'] : '',
            'periodLabel' => sprintf(l('analytics_period_days', true), $days),
            'dateRangeLabel' => $this->format_analytics_date_range(
                isset($analytics['start_date']) ? $analytics['start_date'] : '',
                isset($analytics['end_date']) ? $analytics['end_date'] : ''
            ),
            'currencySymbol' => $currency_symbol,
            'summary' => array(
                'periodRevenue' => isset($summary['period_revenue']) ? $summary['period_revenue'] : 0,
                'periodArrivals' => isset($summary['period_arrivals']) ? $summary['period_arrivals'] : 0,
                'avgOccupancy' => isset($summary['avg_occupancy']) ? $summary['avg_occupancy'] : 0,
            ),
            'i18n' => array(
                'revenueAxis' => l('chart_revenue_axis', true),
                'occupancyAxis' => l('chart_occupancy_axis', true),
                'arrivalsAxis' => l('chart_arrivals_axis', true),
            ),
        );
    }

    private function format_analytics_date_range($start_date, $end_date)
    {
        if (!$start_date || !$end_date) {
            return '';
        }

        if ($start_date === $end_date) {
            return date('M j, Y', strtotime($start_date));
        }

        return date('M j', strtotime($start_date)) . ' – ' . date('M j, Y', strtotime($end_date));
    }
}
