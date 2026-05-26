<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Load action/filter hooks for extensions active on a company.
 */
function load_active_extension_hooks($company_id)
{
    $CI =& get_instance();

    if (!function_exists('add_action')) {
        $CI->load->helper('includes/extension');
    }

    $CI->load->model('Extension_model');
    $CI->load->helper('module');

    $active_extensions = $CI->Extension_model->get_active_extensions($company_id);
    $modules_path = APPPATH . 'extensions/';
    $active_modules = array();

    if ($active_extensions) {
        foreach ($active_extensions as $extension) {
            $active_modules[] = $extension['extension_name'];
        }
    }

    $autoload_packages = array();

    if ($active_modules && count($active_modules) > 0) {
        foreach ($active_modules as $module) {
            if ($module === '.' || $module === '..') {
                continue;
            }
            if (!is_module_directory($modules_path, $module)) {
                continue;
            }

            if (file_exists('application/extensions/' . $module . '/hooks/actions.php')) {
                $autoload_packages[$module . '-actions'] = '../extensions/' . $module . '/hooks/actions';
            }
            if (file_exists('application/extensions/' . $module . '/hooks/filters.php')) {
                $autoload_packages[$module . '-filters'] = '../extensions/' . $module . '/hooks/filters';
            }
        }
    }

    if ($autoload_packages && count($autoload_packages) > 0) {
        $CI->load->helper($autoload_packages, true);
    }
}

/**
 * Build payload for post.booking.state_changed hook listeners.
 */
function build_booking_state_changed_payload($booking_id, $old_state, $new_state, $company_id)
{
    $CI =& get_instance();
    $CI->load->model('Booking_model');
    $CI->load->model('Booking_room_history_model');
    $CI->load->model('Room_model');

    $booking = $CI->Booking_model->get_booking($booking_id);
    $block = $CI->Booking_room_history_model->get_block($booking_id);

    $room_id = $block ? $block['room_id'] : null;
    $room_name = null;
    $lock_room_name = null;

    if ($room_id) {
        $room = $CI->Room_model->get_room($room_id);
        if ($room) {
            $room_name = isset($room['room_name']) ? $room['room_name'] : null;
            $lock_room_name = $room_name;
        }
    }

    return array(
        'booking_id' => (int) $booking_id,
        'company_id' => (int) $company_id,
        'old_state' => $old_state !== null && $old_state !== '' ? (string) $old_state : null,
        'new_state' => $new_state !== null && $new_state !== '' ? (string) $new_state : null,
        'state' => $new_state !== null && $new_state !== '' ? (string) $new_state : null,
        'room_id' => $room_id ? (int) $room_id : null,
        'room_type_id' => $block && isset($block['room_type_id']) ? (int) $block['room_type_id'] : null,
        'check_in_date' => $block && isset($block['check_in_date']) ? $block['check_in_date'] : null,
        'check_out_date' => $block && isset($block['check_out_date']) ? $block['check_out_date'] : null,
        'room_name' => $room_name,
        'guest_name' => $booking && isset($booking['booking_customer_name']) ? $booking['booking_customer_name'] : null,
        'source' => isset($booking['source']) ? $booking['source'] : null,
    );
}

/**
 * Notify extensions that a booking state transition occurred.
 */
function notify_booking_state_changed($booking_id, $old_state, $new_state, $company_id)
{
    if (!$booking_id || !$company_id) {
        return;
    }

    if ((string) $old_state === (string) $new_state) {
        return;
    }

    $CI =& get_instance();

    if (!function_exists('do_action')) {
        $CI->load->helper('includes/extension');
    }

    load_active_extension_hooks($company_id);

    $payload = build_booking_state_changed_payload($booking_id, $old_state, $new_state, $company_id);

    do_action('post.booking.state_changed', $payload);
}

/**
 * Build payload for post.booking.room_changed hook listeners.
 */
function build_booking_room_changed_payload($booking_id, $old_room_id, $new_room_id, $company_id)
{
    $payload = build_booking_state_changed_payload($booking_id, INHOUSE, INHOUSE, $company_id);
    $payload['old_room_id'] = $old_room_id ? (int) $old_room_id : null;
    $payload['new_room_id'] = $new_room_id ? (int) $new_room_id : null;
    $payload['room_id'] = $payload['new_room_id'];

    return $payload;
}

/**
 * Notify extensions that an in-house guest was moved to another room.
 */
function notify_booking_room_changed($booking_id, $old_room_id, $new_room_id, $company_id)
{
    if (!$booking_id || !$company_id || !$old_room_id || !$new_room_id) {
        return;
    }

    if ((int) $old_room_id === (int) $new_room_id) {
        return;
    }

    $CI =& get_instance();

    if (!function_exists('do_action')) {
        $CI->load->helper('includes/extension');
    }

    load_active_extension_hooks($company_id);

    $payload = build_booking_room_changed_payload($booking_id, $old_room_id, $new_room_id, $company_id);

    do_action('post.booking.room_changed', $payload);
}
