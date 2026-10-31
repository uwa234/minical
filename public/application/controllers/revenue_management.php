<?php

class Revenue_management extends MY_Controller
{
    private static $editable_rate_fields = array(
        'adult_1_rate',
        'adult_2_rate',
        'adult_3_rate',
        'adult_4_rate',
        'closed_to_arrival',
        'closed_to_departure',
        'can_be_sold_online',
        'minimum_length_of_stay',
        'maximum_length_of_stay',
    );

    public function __construct()
    {
        parent::__construct();

        $this->load->model('Revenue_management_model');
        $this->load->model('Rate_plan_model');
        $this->load->model('Rate_model');
        $this->load->model('Room_type_model');
        $this->load->model('Currency_model');
        $this->load->model('Company_model');
        $this->load->model('Date_range_model');

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

        $currency_symbol = $this->get_currency_symbol();
        $rate_plan_options = $this->Revenue_management_model->get_rate_plan_options($this->company_id);
        $default_rate_plan_id = '';
        $default_room_type_id = '';

        if (!empty($rate_plan_options)) {
            $default_rate_plan_id = $rate_plan_options[0]['rate_plan_id'];
            $default_room_type_id = $rate_plan_options[0]['room_type_id'];
        }

        $room_overview = $this->Revenue_management_model->get_room_type_overview(
            $this->company_id,
            $selling_date,
            14
        );

        $demand_signals = $this->Revenue_management_model->get_demand_signals(
            $this->company_id,
            $selling_date,
            $forecast_end
        );
        $connected_channels = $this->Revenue_management_model->get_connected_channels($this->company_id);

        $data = array(
            'selling_date' => $selling_date,
            'forecast_days' => $forecast_days,
            'rate_plan_options' => $rate_plan_options,
            'default_rate_plan_id' => $default_rate_plan_id,
            'default_room_type_id' => $default_room_type_id,
            'currency_symbol' => $currency_symbol,
            'room_overview' => $room_overview,
            'demand_signals' => $demand_signals,
            'connected_channels' => $connected_channels,
            'forecast_payload' => $this->build_forecast_payload($forecast, $currency_symbol, $selling_date, $forecast_days),
            'selected_menu' => 'revenue_management',
            'css_files' => array(
                base_url() . auto_version('css/app-modern-page.css'),
                base_url() . auto_version('css/revenue_management/rms.css'),
            ),
            'js_files' => array(
                base_url() . 'js/moment.min.js',
                base_url() . 'js/Chart.min.js',
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

    public function update_rate_cell_AJAX()
    {
        $rate_plan_id = (int) $this->input->post('rate_plan_id');
        $date = trim((string) $this->input->post('date'));
        $field = trim((string) $this->input->post('field'));
        $value = $this->input->post('value');

        if (
            $rate_plan_id < 1
            || !$this->Rate_plan_model->check_if_rate_plan_belongs_to_company($rate_plan_id, $this->company_id)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || !in_array($field, self::$editable_rate_fields, true)
        ) {
            $this->json_response(array('success' => false, 'message' => l('rms_update_failed', true)));
            return;
        }

        $processed = $this->normalize_rate_field_value($field, $value);
        $result = $this->persist_daily_rate_overrides($rate_plan_id, $date, array($field => $processed));

        if (!$result['success']) {
            $this->json_response($result);
            return;
        }

        $this->json_response(array(
            'success' => true,
            'rate' => $result['rate'],
            'message' => l('rms_update_saved', true),
        ));
    }

    public function apply_suggestion_AJAX()
    {
        $action = trim((string) $this->input->post('action'));
        $date = trim((string) $this->input->post('date'));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->json_response(array('success' => false, 'message' => l('rms_apply_failed', true)));
            return;
        }

        $plans = $this->Revenue_management_model->get_default_bar_rate_plans($this->company_id);
        if (empty($plans)) {
            $this->json_response(array('success' => false, 'message' => l('rms_no_bar_plans', true)));
            return;
        }

        if ($action === 'rate_adjust') {
            $adjustment_pct = (float) $this->input->post('adjustment_pct');
            if ($adjustment_pct < -50 || $adjustment_pct > 50 || abs($adjustment_pct) < 0.01) {
                $this->json_response(array('success' => false, 'message' => l('rms_apply_failed', true)));
                return;
            }

            $result = $this->execute_bar_plan_action($plans, $date, array(
                'action' => 'rate_adjust',
                'adjustment_pct' => $adjustment_pct,
            ));
        } elseif ($action === 'min_stay') {
            $value = max(1, min(30, (int) $this->input->post('value')));
            if ($value < 2) {
                $value = 2;
            }

            $result = $this->execute_bar_plan_action($plans, $date, array(
                'action' => 'min_stay',
                'value' => $value,
            ));
        } else {
            $this->json_response(array('success' => false, 'message' => l('rms_apply_failed', true)));
            return;
        }

        if ($result['updated_plans'] < 1) {
            $this->json_response(array('success' => false, 'message' => l('rms_apply_failed', true)));
            return;
        }

        $this->json_response(array(
            'success' => true,
            'updated_plans' => $result['updated_plans'],
            'message' => sprintf(l('rms_apply_success', true), $result['updated_plans']),
            'errors' => $result['errors'],
        ));
    }

    public function get_pace_AJAX()
    {
        $selling_date = $this->get_selling_date();
        $days = (int) $this->input->get('days');
        if ($days < 14 || $days > 60) {
            $days = 28;
        }

        $end_date = date('Y-m-d', strtotime($selling_date . ' +' . ($days - 1) . ' days'));
        $pace = $this->Revenue_management_model->get_daily_pace_report(
            $this->company_id,
            $selling_date,
            $end_date,
            7
        );

        $this->json_response(array(
            'success' => true,
            'data' => $this->build_pace_payload($pace),
        ));
    }

    public function get_property_rates_AJAX()
    {
        $start_date = trim((string) $this->input->get('start_date'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
            $start_date = $this->get_selling_date();
        }

        $day_count = (int) $this->input->get('days');
        if ($day_count < 7 || $day_count > 28) {
            $day_count = 14;
        }

        $grid = $this->Revenue_management_model->get_property_rate_grid(
            $this->company_id,
            $start_date,
            $day_count
        );

        $this->json_response(array(
            'success' => true,
            'data' => array_merge($grid, array(
                'currencySymbol' => $this->get_currency_symbol(),
            )),
        ));
    }

    public function preview_pricing_rules_AJAX()
    {
        $selling_date = $this->get_selling_date();
        $days = (int) $this->input->get('days');
        if ($days < 14 || $days > 60) {
            $days = 28;
        }

        $end_date = date('Y-m-d', strtotime($selling_date . ' +' . ($days - 1) . ' days'));
        $evaluation = $this->Revenue_management_model->evaluate_pricing_rules(
            $this->company_id,
            $selling_date,
            $end_date
        );

        $this->json_response(array(
            'success' => true,
            'actions' => isset($evaluation['actions']) ? $evaluation['actions'] : array(),
            'pace_summary' => isset($evaluation['pace_summary']) ? $evaluation['pace_summary'] : array(),
        ));
    }

    public function run_pricing_rules_AJAX()
    {
        $selling_date = $this->get_selling_date();
        $days = (int) $this->input->post('days');
        if ($days < 14 || $days > 60) {
            $days = 28;
        }

        $end_date = date('Y-m-d', strtotime($selling_date . ' +' . ($days - 1) . ' days'));
        $evaluation = $this->Revenue_management_model->evaluate_pricing_rules(
            $this->company_id,
            $selling_date,
            $end_date
        );

        $actions = isset($evaluation['actions']) ? $evaluation['actions'] : array();
        if (empty($actions)) {
            $this->json_response(array(
                'success' => true,
                'applied' => 0,
                'message' => l('rms_rules_none', true),
            ));
            return;
        }

        $plans = $this->Revenue_management_model->get_default_bar_rate_plans($this->company_id);
        if (empty($plans)) {
            $this->json_response(array('success' => false, 'message' => l('rms_no_bar_plans', true)));
            return;
        }

        $applied = 0;
        foreach ($actions as $action_row) {
            if (empty($action_row['date']) || empty($action_row['action'])) {
                continue;
            }

            $result = $this->execute_bar_plan_action($plans, $action_row['date'], $action_row);
            if ($result['updated_plans'] > 0) {
                $applied++;
            }
        }

        $this->json_response(array(
            'success' => true,
            'applied' => $applied,
            'message' => sprintf(l('rms_rules_applied', true), $applied, count($actions)),
        ));
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

        $currency_symbol = $this->get_currency_symbol();

        $this->json_response(array(
            'success' => true,
            'data' => $this->build_forecast_payload($forecast, $currency_symbol, $selling_date, $days),
        ));
    }

    public function get_demand_signals_AJAX()
    {
        $selling_date = $this->get_selling_date();
        $days = (int) $this->input->get('days');
        if ($days < 14 || $days > 60) {
            $days = 28;
        }

        $end_date = date('Y-m-d', strtotime($selling_date . ' +' . ($days - 1) . ' days'));
        $signals = $this->Revenue_management_model->get_demand_signals(
            $this->company_id,
            $selling_date,
            $end_date
        );

        $this->json_response(array(
            'success' => true,
            'data' => $signals,
        ));
    }

    public function get_channel_parity_AJAX()
    {
        $start_date = trim((string) $this->input->get('start_date'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
            $start_date = $this->get_selling_date();
        }

        $day_count = (int) $this->input->get('days');
        if ($day_count < 7 || $day_count > 28) {
            $day_count = 14;
        }

        $grid = $this->Revenue_management_model->get_channel_parity_grid(
            $this->company_id,
            $start_date,
            $day_count
        );

        $this->json_response(array(
            'success' => true,
            'data' => array_merge($grid, array(
                'currencySymbol' => $this->get_currency_symbol(),
            )),
        ));
    }

    public function sync_channel_rates_AJAX()
    {
        $start_date = trim((string) $this->input->post('start_date'));
        $end_date = trim((string) $this->input->post('end_date'));
        $rate_plan_id = (int) $this->input->post('rate_plan_id');

        if (
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)
            || $rate_plan_id < 1
            || !$this->Rate_plan_model->check_if_rate_plan_belongs_to_company($rate_plan_id, $this->company_id)
        ) {
            $this->json_response(array('success' => false, 'message' => l('rms_channel_sync_failed', true)));
            return;
        }

        $cm_url = $this->config->item('cm_url');
        if (empty($cm_url)) {
            $this->json_response(array('success' => false, 'message' => l('rms_channel_sync_unavailable', true)));
            return;
        }

        $end_exclusive = date('Y-m-d', strtotime($end_date . ' +1 day'));
        $url = rtrim($cm_url, '/') . '/sync/update_rates/'
            . (int) $this->company_id . '/'
            . $start_date . '/'
            . $end_exclusive . '/'
            . $rate_plan_id;

        $response = @file_get_contents($url);
        $decoded = $response ? json_decode($response, true) : null;

        $this->json_response(array(
            'success' => true,
            'message' => l('rms_channel_sync_started', true),
            'response' => $decoded,
        ));
    }

    public function get_ai_insights_AJAX()
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

        $context = $this->Revenue_management_model->build_insights_context(
            $this->company_id,
            $selling_date,
            $days
        );

        $this->load->library('Revenue_ai');
        $insights = $this->revenue_ai->generate_insights($context);
        $insights['suggestions'] = $this->Revenue_management_model->enrich_suggestions_with_actions(
            isset($insights['suggestions']) ? $insights['suggestions'] : array()
        );
        $insights['default_bar_plans'] = $context['default_bar_plans'];

        $this->json_response($insights);
    }

    private function build_forecast_payload($forecast, $currency_symbol, $selling_date, $forecast_days)
    {
        $summary = isset($forecast['summary']) ? $forecast['summary'] : array();
        $end_date = isset($forecast['end_date']) ? $forecast['end_date'] : $selling_date;

        $pickup = $this->Revenue_management_model->get_pickup_metrics(
            $this->company_id,
            $selling_date,
            $end_date,
            7
        );
        $yoy = $this->Revenue_management_model->get_yoy_comparison(
            $this->company_id,
            $selling_date,
            $end_date
        );

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
            'endDate' => $end_date,
            'totalRooms' => isset($forecast['total_rooms']) ? $forecast['total_rooms'] : 0,
            'currencySymbol' => $currency_symbol,
            'summary' => array(
                'onBooksRevenue' => isset($summary['on_books_revenue']) ? $summary['on_books_revenue'] : 0,
                'potentialRevenue' => isset($summary['potential_revenue']) ? $summary['potential_revenue'] : 0,
                'forecastTotal' => isset($summary['forecast_total']) ? $summary['forecast_total'] : 0,
                'avgOccupancy' => isset($summary['avg_occupancy']) ? $summary['avg_occupancy'] : 0,
            ),
            'pickup' => $pickup,
            'yoy' => $yoy,
            'i18n' => array(
                'onBooks' => l('rms_on_books', true),
                'potential' => l('rms_potential', true),
                'forecastTotal' => l('rms_forecast_total', true),
                'occupancy' => l('rms_occupancy', true),
            ),
        );
    }

    private function get_currency_symbol()
    {
        $currency_symbol = $this->session->userdata('currency_symbol');
        if (!$currency_symbol) {
            $default_currency = $this->Currency_model->get_default_currency($this->company_id);
            $currency_symbol = isset($default_currency['currency_code'])
                ? $default_currency['currency_code']
                : '$';
        }

        return $currency_symbol;
    }

    private function get_selling_date()
    {
        $selling_date = $this->session->userdata('current_selling_date');
        if (!$selling_date) {
            $company = $this->Company_model->get_company($this->company_id);
            $selling_date = $company['selling_date'];
        }

        return $selling_date;
    }

    private function build_pace_payload($pace)
    {
        return array(
            'labels' => isset($pace['labels']) ? $pace['labels'] : array(),
            'currentOccupancy' => isset($pace['currentOccupancy']) ? $pace['currentOccupancy'] : array(),
            'priorOccupancy' => isset($pace['priorOccupancy']) ? $pace['priorOccupancy'] : array(),
            'pickup' => isset($pace['pickup']) ? $pace['pickup'] : array(),
            'rows' => isset($pace['rows']) ? $pace['rows'] : array(),
            'summary' => isset($pace['summary']) ? $pace['summary'] : array(),
            'pickupDays' => isset($pace['pickup_days']) ? $pace['pickup_days'] : 7,
            'startDate' => isset($pace['start_date']) ? $pace['start_date'] : '',
            'endDate' => isset($pace['end_date']) ? $pace['end_date'] : '',
            'currencySymbol' => $this->get_currency_symbol(),
            'i18n' => array(
                'currentYear' => l('rms_pace_current', true),
                'priorYear' => l('rms_pace_prior', true),
            ),
        );
    }

    private function execute_bar_plan_action($plans, $date, array $action_row)
    {
        $updated_plans = 0;
        $errors = array();
        $action = isset($action_row['action']) ? $action_row['action'] : '';

        if ($action === 'rate_adjust') {
            $adjustment_pct = isset($action_row['adjustment_pct']) ? (float) $action_row['adjustment_pct'] : 0;
            if ($adjustment_pct < -50 || $adjustment_pct > 50 || abs($adjustment_pct) < 0.01) {
                return array('updated_plans' => 0, 'errors' => array());
            }

            $multiplier = 1 + ($adjustment_pct / 100);

            foreach ($plans as $plan) {
                $rate_plan_id = (int) $plan['rate_plan_id'];
                if (!$this->Rate_plan_model->check_if_rate_plan_belongs_to_company($rate_plan_id, $this->company_id)) {
                    continue;
                }

                $overrides = $this->build_rate_adjustment_overrides($rate_plan_id, $date, $multiplier);
                if (empty($overrides)) {
                    continue;
                }

                $result = $this->persist_daily_rate_overrides($rate_plan_id, $date, $overrides);
                if ($result['success']) {
                    $updated_plans++;
                } else {
                    $errors[] = $plan['room_type_name'];
                }
            }
        } elseif ($action === 'min_stay') {
            $value = isset($action_row['value']) ? max(2, min(30, (int) $action_row['value'])) : 2;

            foreach ($plans as $plan) {
                $rate_plan_id = (int) $plan['rate_plan_id'];
                if (!$this->Rate_plan_model->check_if_rate_plan_belongs_to_company($rate_plan_id, $this->company_id)) {
                    continue;
                }

                $result = $this->persist_daily_rate_overrides($rate_plan_id, $date, array(
                    'minimum_length_of_stay' => $value,
                ));

                if ($result['success']) {
                    $updated_plans++;
                } else {
                    $errors[] = $plan['room_type_name'];
                }
            }
        }

        return array(
            'updated_plans' => $updated_plans,
            'errors' => $errors,
        );
    }

    private function json_response($payload)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

    private function normalize_rate_field_value($field, $value)
    {
        if (in_array($field, array('closed_to_arrival', 'closed_to_departure', 'can_be_sold_online'), true)) {
            return ($value === '1' || $value === 1 || $value === true || $value === 'true') ? '1' : '0';
        }

        if (in_array($field, array('minimum_length_of_stay', 'maximum_length_of_stay'), true)) {
            return max(0, (int) $value);
        }

        return round((float) $value, 2);
    }

    private function build_rate_adjustment_overrides($rate_plan_id, $date, $multiplier)
    {
        $end_exclusive = date('Y-m-d', strtotime($date . ' +1 day'));
        $current_rates = $this->Rate_model->get_daily_rates($rate_plan_id, $date, $end_exclusive);

        if (empty($current_rates[0])) {
            return array();
        }

        $current = $current_rates[0];
        $overrides = array();

        foreach (array('adult_1_rate', 'adult_2_rate', 'adult_3_rate', 'adult_4_rate') as $field) {
            if (!isset($current[$field]) || $current[$field] === '' || $current[$field] === null) {
                continue;
            }

            $overrides[$field] = round(((float) $current[$field]) * $multiplier, 2);
        }

        return $overrides;
    }

    private function persist_daily_rate_overrides($rate_plan_id, $date, array $overrides)
    {
        $rate_plan_id = (int) $rate_plan_id;

        if (
            $rate_plan_id < 1
            || !$this->Rate_plan_model->check_if_rate_plan_belongs_to_company($rate_plan_id, $this->company_id)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || empty($overrides)
        ) {
            return array('success' => false, 'message' => l('rms_update_failed', true));
        }

        foreach (array_keys($overrides) as $field) {
            if (!in_array($field, self::$editable_rate_fields, true)) {
                return array('success' => false, 'message' => l('rms_update_failed', true));
            }
        }

        $end_exclusive = date('Y-m-d', strtotime($date . ' +1 day'));
        $current_rates = $this->Rate_model->get_daily_rates($rate_plan_id, $date, $end_exclusive);
        if (empty($current_rates[0])) {
            return array('success' => false, 'message' => l('rms_no_rates', true));
        }

        $current = $current_rates[0];
        $rate_data = array(
            'rate_plan_id' => $rate_plan_id,
            'base_rate' => isset($current['base_rate']) ? $current['base_rate'] : null,
            'adult_1_rate' => isset($current['adult_1_rate']) ? $current['adult_1_rate'] : null,
            'adult_2_rate' => isset($current['adult_2_rate']) ? $current['adult_2_rate'] : null,
            'adult_3_rate' => isset($current['adult_3_rate']) ? $current['adult_3_rate'] : null,
            'adult_4_rate' => isset($current['adult_4_rate']) ? $current['adult_4_rate'] : null,
            'additional_adult_rate' => isset($current['additional_adult_rate']) ? $current['additional_adult_rate'] : null,
            'additional_child_rate' => isset($current['additional_child_rate']) ? $current['additional_child_rate'] : null,
            'minimum_length_of_stay' => isset($current['minimum_length_of_stay']) ? $current['minimum_length_of_stay'] : null,
            'maximum_length_of_stay' => isset($current['maximum_length_of_stay']) ? $current['maximum_length_of_stay'] : null,
            'minimum_length_of_stay_arrival' => isset($current['minimum_length_of_stay_arrival']) ? $current['minimum_length_of_stay_arrival'] : null,
            'maximum_length_of_stay_arrival' => isset($current['maximum_length_of_stay_arrival']) ? $current['maximum_length_of_stay_arrival'] : null,
            'closed_to_arrival' => isset($current['closed_to_arrival']) ? $current['closed_to_arrival'] : '0',
            'closed_to_departure' => isset($current['closed_to_departure']) ? $current['closed_to_departure'] : '0',
            'can_be_sold_online' => isset($current['can_be_sold_online']) ? $current['can_be_sold_online'] : '1',
        );

        foreach ($overrides as $field => $value) {
            $rate_data[$field] = $this->normalize_rate_field_value($field, $value);
        }

        $rate_id = $this->Rate_model->create_rate($rate_data);
        if (!$rate_id) {
            return array('success' => false, 'message' => l('rms_update_failed', true));
        }

        $day_name = strtolower(date('l', strtotime($date)));
        $date_data = array(
            'date_start' => $date,
            'date_end' => $date,
            'monday' => $day_name === 'monday' ? 1 : 0,
            'tuesday' => $day_name === 'tuesday' ? 1 : 0,
            'wednesday' => $day_name === 'wednesday' ? 1 : 0,
            'thursday' => $day_name === 'thursday' ? 1 : 0,
            'friday' => $day_name === 'friday' ? 1 : 0,
            'saturday' => $day_name === 'saturday' ? 1 : 0,
            'sunday' => $day_name === 'sunday' ? 1 : 0,
        );

        $date_range_id = $this->Date_range_model->create_date_range($date_data);
        $this->Date_range_model->create_date_range_x_rate(array(
            'date_range_id' => $date_range_id,
            'rate_id' => $rate_id,
        ));

        $updated = $this->Rate_model->get_daily_rates($rate_plan_id, $date, $end_exclusive);

        return array(
            'success' => true,
            'rate' => !empty($updated[0]) ? $updated[0] : null,
        );
    }
}
