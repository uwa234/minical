<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Groups extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Booking_linked_group_model');
        $this->load->model('Group_rooming_model');
        $this->load->model('Inventory_block_model');
        $this->load->model('Booking_model');
        $this->load->model('Room_type_model');
        $this->load->model('Customer_model');
        $this->load->helper('group_block');

        $language = $this->session->userdata('language');
        $this->lang->load('groups', $language ? $language : 'english');

        $this->load->vars(array('menu_on' => true));
    }

    public function index()
    {
        $this->Inventory_block_model->process_auto_releases($this->company_id);

        $filters = array(
            'group_name' => $this->input->get('q'),
            'group_id' => $this->input->get('group_id'),
        );
        $groups = $this->Booking_linked_group_model->get_linked_groups($filters, $this->company_id);
        $blocks = $this->Inventory_block_model->get_blocks($this->company_id, array(
            'search' => $this->input->get('block_q'),
            'status' => $this->input->get('block_status'),
        ));

        $data = array(
            'groups' => $groups ? $groups : array(),
            'blocks' => $blocks,
            'room_types' => $this->Room_type_model->get_room_types($this->company_id),
            'selected_menu' => 'groups',
            'css_files' => array(
                base_url() . auto_version('css/app-modern-page.css'),
                base_url() . auto_version('css/groups/groups.css'),
            ),
            'js_files' => array(
                base_url() . 'js/moment.min.js',
                base_url() . auto_version('js/groups/groups.js'),
            ),
            'main_content' => 'groups/index',
        );

        $this->load->view('includes/bootstrapped_template', $data);
    }

    public function view($group_id)
    {
        $group = $this->Booking_linked_group_model->get_group_summary($group_id, $this->company_id);
        if (!$group) {
            show_404();
        }

        $rooming = $this->Group_rooming_model->get_rooming_list($group_id, $this->company_id);
        $bookings = $this->Booking_model->get_bookings_by_group_id($group_id);
        $blocks = $this->Inventory_block_model->get_blocks($this->company_id, array());
        $linked_blocks = array();
        foreach ($blocks as $block) {
            if (!empty($block['booking_group_id']) && (int) $block['booking_group_id'] === (int) $group_id) {
                $linked_blocks[] = $this->Inventory_block_model->get_block($block['id'], $this->company_id);
            }
        }

        $master_customer = null;
        if (!empty($group['master_customer_id'])) {
            $master_customer = $this->Customer_model->get_customer($group['master_customer_id']);
        }

        $data = array(
            'group' => $group,
            'rooming' => $rooming,
            'bookings' => $bookings ? $bookings : array(),
            'linked_blocks' => $linked_blocks,
            'room_types' => $this->Room_type_model->get_room_types($this->company_id),
            'master_customer' => $master_customer,
            'selected_menu' => 'groups',
            'css_files' => array(
                base_url() . auto_version('css/app-modern-page.css'),
                base_url() . auto_version('css/groups/groups.css'),
            ),
            'js_files' => array(
                base_url() . 'js/moment.min.js',
                base_url() . auto_version('js/booking/bookingModal.js'),
                base_url() . auto_version('js/groups/groups.js'),
            ),
            'main_content' => 'groups/view',
        );

        $this->load->view('includes/bootstrapped_template', $data);
    }

    public function rooming_list_AJAX()
    {
        $group_id = (int) $this->input->post('group_id');
        if (!$this->Booking_linked_group_model->get_group_by_id($group_id, $this->company_id)) {
            echo json_encode(array('success' => false, 'message' => l('group_not_found', true)));
            return;
        }

        echo json_encode(array(
            'success' => true,
            'rooming' => $this->Group_rooming_model->get_rooming_list($group_id, $this->company_id),
        ));
    }

    public function save_rooming_entry_AJAX()
    {
        $group_id = (int) $this->input->post('group_id');
        if (!$this->Booking_linked_group_model->get_group_by_id($group_id, $this->company_id)) {
            echo json_encode(array('success' => false, 'message' => l('group_not_found', true)));
            return;
        }

        $entry_id = $this->Group_rooming_model->save_entry($group_id, array(
            'id' => $this->input->post('entry_id'),
            'booking_id' => $this->input->post('booking_id'),
            'room_type_id' => $this->input->post('room_type_id'),
            'guest_name' => $this->input->post('guest_name'),
            'guest_customer_id' => $this->input->post('guest_customer_id'),
            'sort_order' => $this->input->post('sort_order'),
            'notes' => $this->input->post('notes'),
        ));

        $booking_id = $this->input->post('booking_id');
        $guest_name = $this->input->post('guest_name');
        $guest_customer_id = $this->input->post('guest_customer_id');
        if ($booking_id && ($guest_name || $guest_customer_id)) {
            $this->Group_rooming_model->assign_guest_to_booking($booking_id, $guest_name, $guest_customer_id);
        }

        echo json_encode(array('success' => true, 'entry_id' => $entry_id));
    }

    public function add_unassigned_slots_AJAX()
    {
        $group_id = (int) $this->input->post('group_id');
        $room_type_id = (int) $this->input->post('room_type_id');
        $count = max(1, (int) $this->input->post('count'));

        if (!$this->Booking_linked_group_model->get_group_by_id($group_id, $this->company_id)) {
            echo json_encode(array('success' => false));
            return;
        }

        $ids = $this->Group_rooming_model->add_unassigned_slots($group_id, $room_type_id, $count);
        echo json_encode(array('success' => true, 'entry_ids' => $ids));
    }

    public function delete_rooming_entry_AJAX()
    {
        $group_id = (int) $this->input->post('group_id');
        $entry_id = (int) $this->input->post('entry_id');
        $ok = $this->Group_rooming_model->delete_entry($entry_id, $group_id);
        echo json_encode(array('success' => $ok));
    }

    public function export_rooming_csv($group_id)
    {
        $group = $this->Booking_linked_group_model->get_group_summary($group_id, $this->company_id);
        if (!$group) {
            show_404();
        }

        $rooming = $this->Group_rooming_model->get_rooming_list($group_id, $this->company_id);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=rooming-list-' . $group_id . '.csv');

        $out = fopen('php://output', 'w');
        fputcsv($out, array('Room', 'Room type', 'Guest', 'Check-in', 'Check-out', 'Status', 'Notes'));

        foreach ($rooming as $row) {
            $status = 'Unassigned';
            if (!empty($row['booking_id'])) {
                if (!empty($row['room_cancelled'])) {
                    $status = 'Cancelled';
                } elseif (!empty($row['room_name'])) {
                    $status = 'Assigned';
                } else {
                    $status = 'Reserved';
                }
            }
            fputcsv($out, array(
                isset($row['room_name']) ? $row['room_name'] : '',
                isset($row['room_type_name']) ? $row['room_type_name'] : '',
                isset($row['customer_name']) ? $row['customer_name'] : (isset($row['guest_name']) ? $row['guest_name'] : ''),
                isset($row['check_in_date']) ? $row['check_in_date'] : '',
                isset($row['check_out_date']) ? $row['check_out_date'] : '',
                $status,
                isset($row['notes']) ? $row['notes'] : '',
            ));
        }
        fclose($out);
        exit;
    }

    public function update_billing_settings_AJAX()
    {
        $group_id = (int) $this->input->post('group_id');
        $billing_mode = $this->input->post('billing_mode') === 'master' ? 'master' : 'room';
        $master_customer_id = $this->input->post('master_customer_id');

        $ok = $this->Booking_linked_group_model->update_group_settings($group_id, $this->company_id, array(
            'billing_mode' => $billing_mode,
            'master_customer_id' => $master_customer_id ? (int) $master_customer_id : null,
        ));

        if ($ok && $billing_mode === 'master' && $this->input->post('route_existing') === '1') {
            $bookings = $this->Booking_model->get_bookings_by_group_id($group_id);
            if ($bookings) {
                $ids = array();
                foreach ($bookings as $b) {
                    $ids[] = $b['booking_id'];
                }
                $this->load->model('Charge_model');
                $this->Charge_model->route_charges_to_master($ids, $group_id);
            }
        }

        echo json_encode(array('success' => (bool) $ok));
    }

    public function create_block_AJAX()
    {
        $lines = $this->input->post('lines');
        if (!is_array($lines)) {
            $lines = json_decode($this->input->post('lines_json'), true);
        }

        $block_id = $this->Inventory_block_model->create_block($this->company_id, array(
            'name' => $this->input->post('name'),
            'booking_group_id' => $this->input->post('booking_group_id'),
            'account_customer_id' => $this->input->post('account_customer_id'),
            'check_in_date' => $this->input->post('check_in_date'),
            'check_out_date' => $this->input->post('check_out_date'),
            'cutoff_date' => $this->input->post('cutoff_date'),
            'release_date' => $this->input->post('release_date'),
            'notes' => $this->input->post('notes'),
        ), $lines ? $lines : array());

        echo json_encode(array('success' => (bool) $block_id, 'block_id' => $block_id));
    }

    public function block_detail_AJAX($block_id)
    {
        $block = $this->Inventory_block_model->get_block($block_id, $this->company_id);
        if (!$block) {
            echo json_encode(array('success' => false));
            return;
        }
        $block['pickups'] = $this->Inventory_block_model->get_pickups_for_block($block_id);
        $block['past_cutoff'] = group_block_is_past_cutoff($block);
        echo json_encode(array('success' => true, 'block' => $block));
    }

    public function release_block_AJAX()
    {
        $block_id = (int) $this->input->post('block_id');
        $ok = $this->Inventory_block_model->release_block($block_id, $this->company_id);
        echo json_encode(array('success' => $ok));
    }

    public function pickup_block_AJAX()
    {
        $line_id = (int) $this->input->post('line_id');
        $line = $this->Inventory_block_model->get_line($line_id, $this->company_id);

        if (!$line || $line['picked_up'] >= $line['quantity']) {
            echo json_encode(array('success' => false, 'message' => l('no_rooms_remaining_on_block', true)));
            return;
        }

        if (group_block_is_past_cutoff($line)) {
            echo json_encode(array('success' => false, 'message' => l('block_cutoff_passed', true)));
            return;
        }

        $this->load->model('Room_model');
        $this->load->model('Booking_room_history_model');

        $available = $this->Room_model->get_available_rooms(
            $line['check_in_date'],
            $line['check_out_date'],
            $line['room_type_id'],
            null,
            $this->company_id
        );

        if (empty($available)) {
            echo json_encode(array('success' => false, 'message' => l('no_rooms_available_for_pickup', true)));
            return;
        }

        $room = $available[0];
        $booking_data = array(
            'company_id' => $this->company_id,
            'state' => RESERVATION,
            'is_deleted' => 0,
            'source' => 0,
            'booking_notes' => 'Block pickup: ' . $line['block_name'],
        );

        $booking_id = $this->Booking_model->create_booking($booking_data);
        if (!$booking_id) {
            echo json_encode(array('success' => false, 'message' => l('pickup_failed', true)));
            return;
        }

        $this->Booking_room_history_model->create_booking_room_history(array(
            'booking_id' => $booking_id,
            'check_in_date' => $line['check_in_date'],
            'check_out_date' => $line['check_out_date'],
            'room_id' => $room['room_id'],
            'room_type_id' => $line['room_type_id'],
        ));

        if (!empty($line['booking_group_id'])) {
            $this->Booking_linked_group_model->insert_booking_x_booking_linked_group(array(
                'booking_id' => $booking_id,
                'booking_group_id' => $line['booking_group_id'],
            ));
        }

        $this->Inventory_block_model->record_pickup($line_id, $booking_id);

        echo json_encode(array(
            'success' => true,
            'booking_id' => $booking_id,
            'room_name' => $room['room_name'],
        ));
    }
}
