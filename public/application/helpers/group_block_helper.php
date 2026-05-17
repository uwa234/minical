<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('group_block_reduce_available_rooms')) {
    /**
     * Limit available room list when inventory blocks hold capacity for the room type.
     *
     * @param array $available_rooms
     * @param int $company_id
     * @param int $room_type_id
     * @param string $check_in_date
     * @param string $check_out_date
     * @param int|null $exclude_block_id
     * @return array
     */
    function group_block_reduce_available_rooms($available_rooms, $company_id, $room_type_id, $check_in_date, $check_out_date, $exclude_block_id = null)
    {
        if (!$room_type_id || !$check_in_date || !$check_out_date) {
            return $available_rooms;
        }

        $CI =& get_instance();
        if (!isset($CI->Inventory_block_model)) {
            $CI->load->model('Inventory_block_model');
        }
        if (!isset($CI->Room_model)) {
            $CI->load->model('Room_model');
        }

        $total_rooms = $CI->Room_model->get_room_count_by_room_type_id($room_type_id);
        $physical = isset($total_rooms['room_count']) ? (int) $total_rooms['room_count'] : count($available_rooms);

        $max_hold = $CI->Inventory_block_model->get_max_hold_for_range(
            $company_id,
            $room_type_id,
            $check_in_date,
            $check_out_date,
            $exclude_block_id
        );

        if ($max_hold <= 0) {
            return $available_rooms;
        }

        $allowed = max(0, $physical - $max_hold);

        return array_slice($available_rooms, 0, $allowed);
    }
}

if (!function_exists('group_block_is_past_cutoff')) {
    function group_block_is_past_cutoff($block)
    {
        if (empty($block['cutoff_date'])) {
            return false;
        }

        return date('Y-m-d') > $block['cutoff_date'];
    }
}
