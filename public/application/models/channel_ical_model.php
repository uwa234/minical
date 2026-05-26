<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Channel_ical_model extends CI_Model {

    const CHANNEL_BOOKING_DOT_COM = 'booking_dot_com';
    const CHANNEL_AIRBNB = 'airbnb';
    const CHANNEL_EXPEDIA = 'expedia';

    /** @return string[] */
    public static function supported_channel_keys()
    {
        return array(
            self::CHANNEL_BOOKING_DOT_COM,
            self::CHANNEL_AIRBNB,
            self::CHANNEL_EXPEDIA,
        );
    }

    public static function is_valid_channel_key($channel_key)
    {
        return in_array($channel_key, self::supported_channel_keys(), true);
    }

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('ical');
    }

    public function get_mappings($company_id, $channel_key = self::CHANNEL_BOOKING_DOT_COM)
    {
        if (!$this->db->table_exists('channel_ical_mapping')) {
            return array();
        }

        $this->db->from('channel_ical_mapping');
        $this->db->where('company_id', (int) $company_id);
        $this->db->where('channel_key', $channel_key);
        $query = $this->db->get();

        return $query->result_array();
    }

    public function get_mapping_by_room_type($company_id, $room_type_id, $channel_key = self::CHANNEL_BOOKING_DOT_COM)
    {
        if (!$this->db->table_exists('channel_ical_mapping')) {
            return null;
        }

        $this->db->from('channel_ical_mapping');
        $this->db->where('company_id', (int) $company_id);
        $this->db->where('room_type_id', (int) $room_type_id);
        $this->db->where('channel_key', $channel_key);
        $query = $this->db->get();

        if ($query->num_rows() < 1) {
            return null;
        }

        return $query->row_array();
    }

    public function get_mapping_by_export_token($token)
    {
        if (!$this->db->table_exists('channel_ical_mapping')) {
            return null;
        }

        $this->db->from('channel_ical_mapping');
        $this->db->where('export_token', $token);
        $this->db->where('export_enabled', 1);
        $query = $this->db->get();

        if ($query->num_rows() < 1) {
            return null;
        }

        return $query->row_array();
    }

    public function save_mapping($company_id, $room_type_id, $data, $channel_key = self::CHANNEL_BOOKING_DOT_COM)
    {
        $existing = $this->get_mapping_by_room_type($company_id, $room_type_id, $channel_key);
        $now = date('Y-m-d H:i:s');

        $row = array(
            'company_id' => (int) $company_id,
            'room_type_id' => (int) $room_type_id,
            'channel_key' => $channel_key,
            'import_url' => isset($data['import_url']) ? trim($data['import_url']) : null,
            'import_enabled' => !empty($data['import_enabled']) ? 1 : 0,
            'export_enabled' => !empty($data['export_enabled']) ? 1 : 0,
            'updated_at' => $now,
        );

        if ($existing) {
            if (empty($existing['export_token'])) {
                $row['export_token'] = $this->generate_export_token();
            }
            $this->db->where('id', $existing['id']);
            $this->db->update('channel_ical_mapping', $row);

            return $existing['id'];
        }

        $row['export_token'] = $this->generate_export_token();
        $row['created_at'] = $now;
        $this->db->insert('channel_ical_mapping', $row);

        return $this->db->insert_id();
    }

    public function regenerate_export_token($mapping_id, $company_id)
    {
        $token = $this->generate_export_token();
        $this->db->where('id', (int) $mapping_id);
        $this->db->where('company_id', (int) $company_id);
        $this->db->update('channel_ical_mapping', array(
            'export_token' => $token,
            'updated_at' => date('Y-m-d H:i:s'),
        ));

        return $token;
    }

    public function replace_busy_periods($company_id, $room_type_id, $events, $channel_key = self::CHANNEL_BOOKING_DOT_COM)
    {
        $this->db->where('company_id', (int) $company_id);
        $this->db->where('room_type_id', (int) $room_type_id);
        $this->db->where('channel_key', $channel_key);
        $this->db->delete('channel_ical_busy_period');

        $now = date('Y-m-d H:i:s');
        $count = 0;

        foreach ($events as $event) {
            if (empty($event['check_in_date']) || empty($event['check_out_date'])) {
                continue;
            }
            $uid = !empty($event['uid']) ? $event['uid'] : md5(json_encode($event));

            $this->db->insert('channel_ical_busy_period', array(
                'company_id' => (int) $company_id,
                'channel_key' => $channel_key,
                'room_type_id' => (int) $room_type_id,
                'ical_uid' => substr($uid, 0, 255),
                'check_in_date' => $event['check_in_date'],
                'check_out_date' => $event['check_out_date'],
                'summary' => isset($event['summary']) ? substr($event['summary'], 0, 255) : null,
                'updated_at' => $now,
            ));
            $count++;
        }

        return $count;
    }

    public function get_busy_periods_for_export($company_id, $room_type_id, $start_date, $end_date, $exclude_channel_key = null)
    {
        $this->load->model('Room_model');
        $total_rooms = $this->count_rooms_for_type($company_id, $room_type_id);
        $periods = array();
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);

        while ($start < $end) {
            $night = $start->format('Y-m-d');
            $next = clone $start;
            $next->modify('+1 day');

            $available = $this->Room_model->get_available_rooms(
                $night,
                $next->format('Y-m-d'),
                $room_type_id,
                null,
                $company_id
            );

            $free_rooms = count($available);
            $pms_occupied = max(0, $total_rooms - $free_rooms);
            $import_blocks = $this->count_import_blocks_on_night($company_id, $room_type_id, $night, $exclude_channel_key);

            if ($total_rooms > 0 && ($pms_occupied + $import_blocks) >= $total_rooms) {
                $periods[] = array(
                    'check_in_date' => $night,
                    'check_out_date' => $next->format('Y-m-d'),
                    'summary' => 'Not available',
                    'uid' => 'minical-avail-' . $company_id . '-' . $room_type_id . '-' . str_replace('-', '', $night),
                );
            }

            $start->modify('+1 day');
        }

        return $this->merge_consecutive_periods($periods);
    }

    public function count_rooms_for_type($company_id, $room_type_id)
    {
        $this->db->from('room');
        $this->db->where('company_id', (int) $company_id);
        $this->db->where('room_type_id', (int) $room_type_id);
        $this->db->where('is_deleted', '0');

        return (int) $this->db->count_all_results();
    }

    public function count_import_blocks_on_night($company_id, $room_type_id, $date, $exclude_channel_key = null)
    {
        if (!$this->db->table_exists('channel_ical_busy_period')) {
            return 0;
        }

        $this->db->from('channel_ical_busy_period');
        $this->db->where('company_id', (int) $company_id);
        $this->db->where('room_type_id', (int) $room_type_id);
        if ($exclude_channel_key !== null && $exclude_channel_key !== '') {
            $this->db->where('channel_key !=', $exclude_channel_key);
        }
        $this->db->where('check_in_date <=', $date);
        $this->db->where('check_out_date >', $date);

        return (int) $this->db->count_all_results();
    }

    public function import_from_url($mapping)
    {
        if (empty($mapping['import_url'])) {
            return array('success' => false, 'message' => 'Import URL is empty.', 'count' => 0);
        }

        $fetch = ical_fetch_url($mapping['import_url']);
        if (!$fetch['success']) {
            return array('success' => false, 'message' => $fetch['error'], 'count' => 0);
        }

        $events = ical_parse_events($fetch['body']);
        $normalized = array();

        $default_summary = $this->default_event_summary_for_channel($mapping['channel_key']);

        foreach ($events as $event) {
            $normalized[] = array(
                'uid' => isset($event['uid']) ? $event['uid'] : null,
                'summary' => isset($event['summary']) ? $event['summary'] : $default_summary,
                'check_in_date' => $event['check_in_date'],
                'check_out_date' => $event['check_out_date'],
            );
        }

        $count = $this->replace_busy_periods(
            $mapping['company_id'],
            $mapping['room_type_id'],
            $normalized,
            $mapping['channel_key']
        );

        $this->db->where('id', $mapping['id']);
        $this->db->update('channel_ical_mapping', array(
            'last_import_at' => date('Y-m-d H:i:s'),
            'last_import_message' => $count . ' reservation block(s) synced',
            'updated_at' => date('Y-m-d H:i:s'),
        ));

        $channel_label = $this->channel_label($mapping['channel_key']);

        return array(
            'success' => true,
            'message' => $count . ' reservation block(s) imported from ' . $channel_label . ' calendar.',
            'count' => $count,
        );
    }

    public function import_all_for_company($company_id, $channel_key = null)
    {
        $results = array();
        $channel_keys = $channel_key !== null
            ? array($channel_key)
            : self::supported_channel_keys();

        foreach ($channel_keys as $key) {
            if (!self::is_valid_channel_key($key)) {
                continue;
            }
            $mappings = $this->get_mappings($company_id, $key);
            foreach ($mappings as $mapping) {
                if (empty($mapping['import_enabled']) || empty($mapping['import_url'])) {
                    continue;
                }
                $results[] = $this->import_from_url($mapping);
            }
        }

        return $results;
    }

    public function channel_label($channel_key)
    {
        $labels = array(
            self::CHANNEL_BOOKING_DOT_COM => 'Booking.com',
            self::CHANNEL_AIRBNB => 'Airbnb',
            self::CHANNEL_EXPEDIA => 'Expedia',
        );

        return isset($labels[$channel_key]) ? $labels[$channel_key] : $channel_key;
    }

    protected function default_event_summary_for_channel($channel_key)
    {
        return $this->channel_label($channel_key);
    }

    protected function merge_consecutive_periods($periods)
    {
        if (empty($periods)) {
            return array();
        }

        $merged = array();
        $current = $periods[0];

        for ($i = 1; $i < count($periods); $i++) {
            if ($periods[$i]['check_in_date'] === $current['check_out_date']) {
                $current['check_out_date'] = $periods[$i]['check_out_date'];
            } else {
                $merged[] = $current;
                $current = $periods[$i];
            }
        }
        $merged[] = $current;

        return $merged;
    }

    protected function generate_export_token()
    {
        return bin2hex(random_bytes(16));
    }
}
