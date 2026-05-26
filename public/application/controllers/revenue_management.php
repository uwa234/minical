<?php

class Revenue_management extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Revenue_management_model');
        $this->load->model('Rate_plan_model');
        $this->load->model('Rate_model');
        $this->load->model('Room_type_model');
        $this->load->model('Currency_model');
        $this->load->model('Company_model');

        $language = $this->session->userdata('language');
        $this->lang->load('revenue_management', $language ? $language : 'english');

        $this->load->vars(array(
            'menu_on' => true,
            'selected_menu' => 'revenue_management',
        ));
    }

    public function index()
    {
        $selling_date = $this->session->userdata('current_selling_date');
        if (!$selling_date) {
            $company = $this->Company_model->get_company($this->company_id);
            $selling_date = $company['selling_date'];
        }

        $forecast_days = (int) $this->input->get('days');
        if ($forecast_days < 14 || $forecast_days > 60) {
            $forecast_days = 28;
        }

        $forecast_end = date('Y-m-d', strtotime($selling_date . ' +' . ($forecast_days - 1) . ' days'));
        $forecast = $this->Revenue_management_model->get_revenue_forecast(
            $this->company_id,
            $selling_date,
            $forecast_end
        );

        $currency_symbol = $this->session->userdata('currency_symbol');
        if (!$currency_symbol) {
            $default_currency = $this->Currency_model->get_default_currency($this->company_id);
            $currency_symbol = isset($default_currency['currency_code'])
                ? $default_currency['currency_code']
                : '$';
        }

        $rate_plan_options = $this->Revenue_management_model->get_rate_plan_options($this->company_id);
        $default_rate_plan_id = '';
        $default_room_type_id = '';

        if (!empty($rate_plan_options)) {
            $default_rate_plan_id = $rate_plan_options[0]['rate_plan_id'];
            $default_room_type_id = $rate_plan_options[0]['room_type_id'];
        }

        $data = array(
            'selling_date' => $selling_date,
            'forecast_days' => $forecast_days,
            'rate_plan_options' => $rate_plan_options,
            'default_rate_plan_id' => $default_rate_plan_id,
            'default_room_type_id' => $default_room_type_id,
            'currency_symbol' => $currency_symbol,
            'forecast_payload' => $this->build_forecast_payload($forecast, $currency_symbol),
            'selected_menu' => 'revenue_management',
            'css_files' => array(
                base_url() . auto_version('css/app-modern-page.css'),
                base_url() . auto_version('css/revenue_management/rms.css'),
            ),
            'js_files' => array(
                base_url() . 'js/moment.min.js',
                base_url() . auto_version('js/revenue_management/rms.js'),
            ),
            'main_content' => 'revenue_management/index',
        );

        $this->load->view('includes/bootstrapped_template', $data);
    }

    public function get_rates_AJAX()
    {
        $rate_plan_id = $this->input->post('rate_plan_id');
        $room_type_id = $this->input->post('room_type_id');
        $start_date = $this->input->post('start_date');

        if (!$rate_plan_id || !$this->Rate_plan_model->check_if_rate_plan_belongs_to_company($rate_plan_id, $this->company_id)) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => false, 'rates' => array())));
            return;
        }

        $date_range = 28;
        $end_date = date('Y-m-d', strtotime('+' . $date_range . ' days', strtotime($start_date)));

        $rates = array(
            'rates' => $this->Rate_model->get_daily_rates($rate_plan_id, $start_date, $end_date, $room_type_id),
            'errors' => array(),
        );

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($rates));
    }

    public function get_forecast_AJAX()
    {
        $selling_date = $this->session->userdata('current_selling_date');
        if (!$selling_date) {
            $company = $this->Company_model->get_company($this->company_id);
            $selling_date = $company['selling_date'];
        }

        $days = (int) $this->input->get('days');
        if ($days < 14 || $days > 60) {
            $days = 28;
        }

        $forecast_end = date('Y-m-d', strtotime($selling_date . ' +' . ($days - 1) . ' days'));
        $forecast = $this->Revenue_management_model->get_revenue_forecast(
            $this->company_id,
            $selling_date,
            $forecast_end
        );

        $currency_symbol = $this->session->userdata('currency_symbol');
        if (!$currency_symbol) {
            $default_currency = $this->Currency_model->get_default_currency($this->company_id);
            $currency_symbol = isset($default_currency['currency_code'])
                ? $default_currency['currency_code']
                : '$';
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'data' => $this->build_forecast_payload($forecast, $currency_symbol),
            )));
    }

    private function build_forecast_payload($forecast, $currency_symbol)
    {
        $summary = isset($forecast['summary']) ? $forecast['summary'] : array();

        return array(
            'labels' => isset($forecast['labels']) ? $forecast['labels'] : array(),
            'onBooks' => isset($forecast['on_books']) ? $forecast['on_books'] : array(),
            'potential' => isset($forecast['potential']) ? $forecast['potential'] : array(),
            'forecastTotal' => isset($forecast['forecast_total']) ? $forecast['forecast_total'] : array(),
            'occupancy' => isset($forecast['occupancy']) ? $forecast['occupancy'] : array(),
            'adr' => isset($forecast['adr']) ? $forecast['adr'] : array(),
            'revpar' => isset($forecast['revpar']) ? $forecast['revpar'] : array(),
            'rows' => isset($forecast['rows']) ? $forecast['rows'] : array(),
            'startDate' => isset($forecast['start_date']) ? $forecast['start_date'] : '',
            'endDate' => isset($forecast['end_date']) ? $forecast['end_date'] : '',
            'totalRooms' => isset($forecast['total_rooms']) ? $forecast['total_rooms'] : 0,
            'currencySymbol' => $currency_symbol,
            'summary' => array(
                'onBooksRevenue' => isset($summary['on_books_revenue']) ? $summary['on_books_revenue'] : 0,
                'potentialRevenue' => isset($summary['potential_revenue']) ? $summary['potential_revenue'] : 0,
                'forecastTotal' => isset($summary['forecast_total']) ? $summary['forecast_total'] : 0,
                'avgOccupancy' => isset($summary['avg_occupancy']) ? $summary['avg_occupancy'] : 0,
            ),
            'i18n' => array(
                'onBooks' => l('rms_on_books', true),
                'potential' => l('rms_potential', true),
                'forecastTotal' => l('rms_forecast_total', true),
            ),
        );
    }
}
