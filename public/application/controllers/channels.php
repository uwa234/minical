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
        $mappings_by_channel = array();

        foreach (Channel_ical_model::supported_channel_keys() as $channel_key) {
            $mappings_by_channel[$channel_key] = array();
            foreach ($this->Channel_ical_model->get_mappings($this->company_id, $channel_key) as $mapping) {
                $mappings_by_channel[$channel_key][$mapping['room_type_id']] = $mapping;
            }
        }

        $booking_engine_url = base_url('online_reservation/select_dates_and_rooms/' . $this->company_id);
        $website_enabled = !empty($company['website_is_taking_online_reservation']);

        $data = array(
            'company' => $company,
            'room_types' => $room_types,
            'mappings_by_channel' => $mappings_by_channel,
            'ical_channels' => $this->_ical_channels_ui_config(),
            'booking_engine_url' => $booking_engine_url,
            'website_enabled' => $website_enabled,
            'selected_menu' => 'channels',
            'css_files' => array(
                base_url() . auto_version('css/app-modern-page.css'),
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

    public function save_ical_AJAX()
    {
        $channel_key = $this->input->post('channel_key');
        if (!Channel_ical_model::is_valid_channel_key($channel_key)) {
            echo json_encode(array('success' => false, 'message' => l('Invalid request', true)));
            return;
        }

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
            ), $channel_key);
            $saved++;
        }

        echo json_encode(array(
            'success' => true,
            'message' => l('Channel settings saved', true),
            'saved' => $saved,
        ));
    }

    /** @deprecated Use save_ical_AJAX with channel_key booking_dot_com */
    public function save_booking_com_ical_AJAX()
    {
        $_POST['channel_key'] = Channel_ical_model::CHANNEL_BOOKING_DOT_COM;
        $this->save_ical_AJAX();
    }

    public function sync_ical_AJAX()
    {
        $channel_key = $this->input->post('channel_key');
        if (!Channel_ical_model::is_valid_channel_key($channel_key)) {
            echo json_encode(array('success' => false, 'message' => l('Invalid request', true)));
            return;
        }

        $results = $this->Channel_ical_model->import_all_for_company($this->company_id, $channel_key);
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

    /** @deprecated Use sync_ical_AJAX with channel_key booking_dot_com */
    public function sync_booking_com_ical_AJAX()
    {
        $_POST['channel_key'] = Channel_ical_model::CHANNEL_BOOKING_DOT_COM;
        $this->sync_ical_AJAX();
    }

    public function regenerate_export_token_AJAX()
    {
        $room_type_id = (int) $this->input->post('room_type_id');
        $channel_key = $this->input->post('channel_key');

        if (!Channel_ical_model::is_valid_channel_key($channel_key)) {
            $channel_key = Channel_ical_model::CHANNEL_BOOKING_DOT_COM;
        }

        $mapping = $this->Channel_ical_model->get_mapping_by_room_type($this->company_id, $room_type_id, $channel_key);

        if (!$mapping) {
            $this->Channel_ical_model->save_mapping($this->company_id, $room_type_id, array(
                'export_enabled' => 1,
            ), $channel_key);
            $mapping = $this->Channel_ical_model->get_mapping_by_room_type($this->company_id, $room_type_id, $channel_key);
        }

        $token = $this->Channel_ical_model->regenerate_export_token($mapping['id'], $this->company_id);

        echo json_encode(array(
            'success' => true,
            'export_url' => base_url('channels/ical_export/' . $token . '/' . $room_type_id),
            'channel_key' => $channel_key,
        ));
    }

    /**
     * Public iCal feed for OTA calendar import (availability / busy dates).
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
        $channel_label = $this->Channel_ical_model->channel_label($mapping['channel_key']);

        $busy = $this->Channel_ical_model->get_busy_periods_for_export(
            $mapping['company_id'],
            $mapping['room_type_id'],
            $start_date,
            $end_date,
            $mapping['channel_key']
        );

        $ics = ical_build_calendar($busy, 'Veurion availability — ' . $channel_label);

        $this->output
            ->set_content_type('text/calendar', 'utf-8')
            ->set_header('Content-Disposition: inline; filename="veurion-' . $mapping['channel_key'] . '-availability.ics"')
            ->set_output($ics);
    }

    private function channels_asset_url($relative_path)
    {
        $path = FCPATH . ltrim($relative_path, '/');
        $version = file_exists($path) ? filemtime($path) : time();

        return base_url() . ltrim($relative_path, '/') . '?v=' . $version;
    }

    private function _ical_channels_ui_config()
    {
        return array(
            Channel_ical_model::CHANNEL_BOOKING_DOT_COM => array(
                'card_class' => 'channel-card--booking',
                'import_placeholder' => 'https://admin.booking.com/...',
                'lang' => array(
                    'title' => 'channel_booking_com',
                    'desc' => 'channel_booking_com_desc',
                    'limitations' => 'channel_booking_com_ical_limitations',
                    'import_url' => 'channel_booking_com_import_url',
                    'export_url' => 'channel_booking_com_export_url',
                    'enable_import' => 'channel_booking_com_enable_import',
                    'enable_export' => 'channel_booking_com_enable_export',
                    'regenerate_hint' => 'channel_booking_com_regenerate_hint',
                ),
            ),
            Channel_ical_model::CHANNEL_AIRBNB => array(
                'card_class' => 'channel-card--airbnb',
                'import_placeholder' => 'https://www.airbnb.com/calendar/ical/...',
                'lang' => array(
                    'title' => 'channel_airbnb',
                    'desc' => 'channel_airbnb_desc',
                    'limitations' => 'channel_airbnb_ical_limitations',
                    'import_url' => 'channel_airbnb_import_url',
                    'export_url' => 'channel_airbnb_export_url',
                    'enable_import' => 'channel_airbnb_enable_import',
                    'enable_export' => 'channel_airbnb_enable_export',
                    'regenerate_hint' => 'channel_airbnb_regenerate_hint',
                ),
            ),
            Channel_ical_model::CHANNEL_EXPEDIA => array(
                'card_class' => 'channel-card--expedia',
                'import_placeholder' => 'https://www.expediapartnercentral.com/...',
                'lang' => array(
                    'title' => 'channel_expedia',
                    'desc' => 'channel_expedia_desc',
                    'limitations' => 'channel_expedia_ical_limitations',
                    'import_url' => 'channel_expedia_import_url',
                    'export_url' => 'channel_expedia_export_url',
                    'enable_import' => 'channel_expedia_enable_import',
                    'enable_export' => 'channel_expedia_enable_export',
                    'regenerate_hint' => 'channel_expedia_regenerate_hint',
                ),
            ),
        );
    }
}
