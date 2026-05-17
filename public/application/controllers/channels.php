<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Channels extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Channel_ical_model');
        $this->load->model('Room_type_model');
        $this->load->model('Company_model');
        $this->load->helper('ical');

        $language = $this->session->userdata('language');
        $this->lang->load('channels', $language ? $language : 'english');

        $this->load->vars(array('menu_on' => true));
    }

    public function index()
    {
        $company = $this->Company_model->get_company($this->company_id);
        $room_types = $this->Room_type_model->get_room_types($this->company_id);
        $mappings = $this->Channel_ical_model->get_mappings($this->company_id);
        $mappings_by_room = array();

        foreach ($mappings as $mapping) {
            $mappings_by_room[$mapping['room_type_id']] = $mapping;
        }

        $booking_engine_url = base_url('online_reservation/select_dates_and_rooms/' . $this->company_id);
        $website_enabled = !empty($company['website_is_taking_online_reservation']);

        $data = array(
            'company' => $company,
            'room_types' => $room_types,
            'mappings_by_room' => $mappings_by_room,
            'booking_engine_url' => $booking_engine_url,
            'website_enabled' => $website_enabled,
            'selected_menu' => 'channels',
            'css_files' => array(
                $this->channels_asset_url('css/channels/channels.css'),
            ),
            'js_files' => array(
                $this->channels_asset_url('js/channels/channels.js'),
            ),
            'main_content' => 'channels/index',
        );

        $this->load->view('includes/bootstrapped_template', $data);
    }

    public function update_website_online_reservations_AJAX()
    {
        $raw = $this->input->post('enabled');
        if ($raw === false || $raw === null) {
            $raw = $this->input->post('website_is_taking_online_reservation');
        }

        $value = in_array($raw, array('1', 1, 'on', 'true', true), true) ? 1 : 0;

        $this->Company_model->update_company($this->company_id, array(
            'website_is_taking_online_reservation' => $value,
        ));

        if ($this->db->_error_message()) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => l('website_online_update_failed', true),
                )));
            return;
        }

        $company = $this->Company_model->get_company($this->company_id);
        $saved = !empty($company['website_is_taking_online_reservation']);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'enabled' => $saved,
                'message' => $saved ? l('website_online_enabled', true) : l('website_online_disabled', true),
                'status_label' => $saved
                    ? $this->lang->line('channel_status_connected')
                    : $this->lang->line('channel_status_not_connected'),
                'status_class' => $saved ? 'ok' : 'muted',
            )));
    }

    private function channels_asset_url($relative_path)
    {
        $path = FCPATH . ltrim($relative_path, '/');
        $version = file_exists($path) ? filemtime($path) : time();

        return base_url() . ltrim($relative_path, '/') . '?v=' . $version;
    }

    public function save_booking_com_ical_AJAX()
    {
        $mappings_input = $this->input->post('mappings');
        if (!is_array($mappings_input)) {
            echo json_encode(array('success' => false, 'message' => l('Invalid request', true)));
            return;
        }

        $saved = 0;
        foreach ($mappings_input as $room_type_id => $row) {
            $room_type_id = (int) $room_type_id;
            if ($room_type_id < 1) {
                continue;
            }

            $this->Channel_ical_model->save_mapping($this->company_id, $room_type_id, array(
                'import_url' => isset($row['import_url']) ? $row['import_url'] : '',
                'import_enabled' => !empty($row['import_enabled']),
                'export_enabled' => !empty($row['export_enabled']),
            ));
            $saved++;
        }

        echo json_encode(array(
            'success' => true,
            'message' => l('Channel settings saved', true),
            'saved' => $saved,
        ));
    }

    public function sync_booking_com_ical_AJAX()
    {
        $results = $this->Channel_ical_model->import_all_for_company($this->company_id);
        $messages = array();
        $success = false;

        foreach ($results as $result) {
            $messages[] = $result['message'];
            if (!empty($result['success'])) {
                $success = true;
            }
        }

        if (empty($results)) {
            echo json_encode(array(
                'success' => false,
                'message' => l('Enable import URL on at least one room type first', true),
            ));
            return;
        }

        echo json_encode(array(
            'success' => $success,
            'message' => implode(' ', $messages),
            'results' => $results,
        ));
    }

    public function regenerate_export_token_AJAX()
    {
        $room_type_id = (int) $this->input->post('room_type_id');
        $mapping = $this->Channel_ical_model->get_mapping_by_room_type($this->company_id, $room_type_id);

        if (!$mapping) {
            $this->Channel_ical_model->save_mapping($this->company_id, $room_type_id, array(
                'export_enabled' => 1,
            ));
            $mapping = $this->Channel_ical_model->get_mapping_by_room_type($this->company_id, $room_type_id);
        }

        $token = $this->Channel_ical_model->regenerate_export_token($mapping['id'], $this->company_id);

        echo json_encode(array(
            'success' => true,
            'export_url' => base_url('channels/ical_export/' . $token . '/' . $room_type_id),
        ));
    }

    /**
     * Public iCal feed for Booking.com calendar import (availability / busy dates).
     */
    public function ical_export($export_token = null, $room_type_id = null)
    {
        $mapping = $this->Channel_ical_model->get_mapping_by_export_token($export_token);
        if (!$mapping || (int) $mapping['room_type_id'] !== (int) $room_type_id) {
            show_404();
            return;
        }

        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+18 months'));

        $busy = $this->Channel_ical_model->get_busy_periods_for_export(
            $mapping['company_id'],
            $mapping['room_type_id'],
            $start_date,
            $end_date
        );

        $ics = ical_build_calendar($busy, 'miniCal availability');

        $this->output
            ->set_content_type('text/calendar', 'utf-8')
            ->set_header('Content-Disposition: inline; filename="minical-availability.ics"')
            ->set_output($ics);
    }
}
