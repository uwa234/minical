<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Guest room move, exchange, unassign, and folio rate updates.
 */
trait Booking_room_operations_trait
{
    protected function _combine_booking_blocks($booking_id)
    {
        $this->Booking_room_history_model->check_and_combine_booking_blocks($booking_id);
    }

    /**
     * Move an in-house or future reservation guest to another room; optionally refresh folio rates.
     *
     * @param int|string $booking_id
     * @param int|string $new_room_id
     * @param array $options rate_plan_id, update_rates (bool), effective_date (Y-m-d)
     * @return array success, message, balance, warning
     */
    protected function _move_guest_room($booking_id, $new_room_id, $options = array())
    {
        $booking_id = (int) $booking_id;
        $new_room_id = (int) $new_room_id;

        if (!$booking_id || !$new_room_id) {
            return array('success' => false, 'message' => l('Room selection is mandatory', true));
        }

        $booking = $this->Booking_model->get_booking($booking_id);
        if (empty($booking) || (int) $booking['company_id'] !== (int) $this->company_id) {
            return array('success' => false, 'message' => l('Warning: Selected cannot be modified', true));
        }

        if (!in_array((int) $booking['state'], array(INHOUSE, RESERVATION, UNCONFIRMED_RESERVATION), true)) {
            return array('success' => false, 'message' => l('Warning: Selected cannot be modified', true));
        }

        $new_room = $this->Room_model->get_room($new_room_id);
        if (empty($new_room)) {
            return array('success' => false, 'message' => l('Warning: Selected cannot be modified', true));
        }

        $new_room_type_id = (int) $new_room['room_type_id'];
        $latest_block = $this->Booking_room_history_model->get_latest_booking_room_history($booking_id);
        if (empty($latest_block)) {
            return array('success' => false, 'message' => l('Warning: Selected cannot be modified', true));
        }

        $old_room_id = (int) $latest_block['room_id'];
        if ($old_room_id === $new_room_id) {
            return array('success' => true, 'message' => l('success', true));
        }

        $effective_date = isset($options['effective_date']) ? $options['effective_date'] : $this->selling_date;
        $check_out = date('Y-m-d', strtotime($latest_block['check_out_date']));
        $check_in = date('Y-m-d', strtotime($latest_block['check_in_date']));

        if ($this->Booking_room_history_model->check_if_booking_exists_between_two_dates(
            $new_room_id,
            max($effective_date, $check_in),
            $check_out,
            $booking_id
        )) {
            return array('success' => false, 'message' => l('Warning: This room is already occupied', true));
        }

        $warning = '';
        $room_change_result = $this->_apply_room_id_change_to_booking_blocks(
            $booking_id,
            $booking,
            $latest_block,
            $new_room_id,
            $new_room_type_id,
            $check_out
        );

        if (!$room_change_result['success']) {
            return $room_change_result;
        }
        if (!empty($room_change_result['warning'])) {
            $warning = $room_change_result['warning'];
        }

        $update_rates = !isset($options['update_rates']) || $options['update_rates'];
        if ($update_rates) {
            $new_parent_rate_plan_id = isset($options['rate_plan_id']) ? $options['rate_plan_id'] : null;
            $this->_apply_folio_rates_after_room_change(
                $booking_id,
                $old_room_id,
                $new_room_id,
                $new_room_type_id,
                $new_parent_rate_plan_id,
                $effective_date,
                $check_out
            );
        }

        $this->Booking_model->update_booking_balance($booking_id);
        $balance = $this->Booking_model->get_booking($booking_id);
        $return_type = $this->is_total_balance_include_forecast ? 'balance' : 'balance_without_forecast';

        $this->_create_booking_log(
            $booking_id,
            l('Room name', true) . ' changed from ' . $this->_room_label($old_room_id) . ' to ' . $this->_room_label($new_room_id)
        );

        return array(
            'success' => true,
            'message' => l('success', true),
            'warning' => $warning,
            'balance' => isset($balance[$return_type]) ? $balance[$return_type] : null,
        );
    }

    /**
     * Swap rooms between two in-house guests (effective from selling date).
     */
    protected function _exchange_guest_rooms($booking_id_a, $booking_id_b)
    {
        $booking_id_a = (int) $booking_id_a;
        $booking_id_b = (int) $booking_id_b;

        if (!$booking_id_a || !$booking_id_b || $booking_id_a === $booking_id_b) {
            return array('success' => false, 'message' => l('Warning: Selected cannot be modified', true));
        }

        $booking_a = $this->Booking_model->get_booking($booking_id_a);
        $booking_b = $this->Booking_model->get_booking($booking_id_b);

        if (
            empty($booking_a) || empty($booking_b) ||
            (int) $booking_a['company_id'] !== (int) $this->company_id ||
            (int) $booking_b['company_id'] !== (int) $this->company_id ||
            (int) $booking_a['state'] !== INHOUSE ||
            (int) $booking_b['state'] !== INHOUSE
        ) {
            return array('success' => false, 'message' => l('room_exchange_inhouse_only', true));
        }

        $block_a = $this->Booking_room_history_model->get_latest_booking_room_history($booking_id_a);
        $block_b = $this->Booking_room_history_model->get_latest_booking_room_history($booking_id_b);

        if (empty($block_a['room_id']) || empty($block_b['room_id'])) {
            return array('success' => false, 'message' => l('room_exchange_requires_assigned_rooms', true));
        }

        $room_a = (int) $block_a['room_id'];
        $room_b = (int) $block_b['room_id'];

        if ($room_a === $room_b) {
            return array('success' => false, 'message' => l('room_exchange_same_room', true));
        }

        $this->db->trans_start();

        $unassign_a = $this->_unassign_guest_room($booking_id_a);
        if (empty($unassign_a['success'])) {
            $this->db->trans_rollback();
            return $unassign_a;
        }

        $move_b = $this->_move_guest_room($booking_id_b, $room_a, array('update_rates' => true));
        if (empty($move_b['success'])) {
            $this->db->trans_rollback();
            return $move_b;
        }

        $move_a = $this->_move_guest_room($booking_id_a, $room_b, array('update_rates' => true));
        if (empty($move_a['success'])) {
            $this->db->trans_rollback();
            return $move_a;
        }

        $this->db->trans_complete();

        $this->_create_booking_log(
            $booking_id_a,
            l('room_exchange_log', true) . ' #' . $booking_id_b
        );
        $this->_create_booking_log(
            $booking_id_b,
            l('room_exchange_log', true) . ' #' . $booking_id_a
        );

        return array(
            'success' => true,
            'message' => l('room_exchange_success', true),
            'balance_a' => isset($move_a['balance']) ? $move_a['balance'] : null,
            'balance_b' => isset($move_b['balance']) ? $move_b['balance'] : null,
        );
    }

    /**
     * Remove room assignment from the active stay segment (room_id = 0).
     */
    protected function _unassign_guest_room($booking_id)
    {
        $booking_id = (int) $booking_id;
        $booking = $this->Booking_model->get_booking($booking_id);

        if (empty($booking) || (int) $booking['company_id'] !== (int) $this->company_id) {
            return array('success' => false, 'message' => l('Warning: Selected cannot be modified', true));
        }

        if (!in_array((int) $booking['state'], array(INHOUSE, RESERVATION, UNCONFIRMED_RESERVATION), true)) {
            return array('success' => false, 'message' => l('Warning: Selected cannot be modified', true));
        }

        $latest_block = $this->Booking_room_history_model->get_latest_booking_room_history($booking_id);
        if (empty($latest_block) || empty($latest_block['room_id'])) {
            return array('success' => true, 'message' => l('unassign_room_already', true));
        }

        $old_room_id = (int) $latest_block['room_id'];
        $room_type_id = !empty($latest_block['room_type_id']) ? (int) $latest_block['room_type_id'] : null;
        if (!$room_type_id && $old_room_id) {
            $rt = $this->Room_type_model->get_room_type_by_room_id($old_room_id);
            $room_type_id = isset($rt['id']) ? (int) $rt['id'] : 0;
        }

        $check_out = date('Y-m-d', strtotime($latest_block['check_out_date']));
        $result = $this->_apply_room_id_change_to_booking_blocks(
            $booking_id,
            $booking,
            $latest_block,
            0,
            $room_type_id,
            $check_out
        );

        if (empty($result['success'])) {
            return $result;
        }

        $this->Booking_model->update_booking_balance($booking_id);
        $this->_create_booking_log(
            $booking_id,
            l('unassign_room_log', true) . ' ' . $this->_room_label($old_room_id)
        );

        return array(
            'success' => true,
            'message' => l('unassign_room_success', true),
            'warning' => isset($result['warning']) ? $result['warning'] : '',
        );
    }

    /**
     * Split or update booking blocks when the room assignment changes.
     */
    protected function _apply_room_id_change_to_booking_blocks($booking_id, $booking, $latest_block, $new_room_id, $new_room_type_id, $check_out_date)
    {
        $new_room_id = (int) $new_room_id;
        $warning = '';

        if (
            (int) $booking['state'] === INHOUSE &&
            strtotime($latest_block['check_out_date']) === strtotime($this->selling_date) &&
            strtotime($check_out_date) === strtotime($this->selling_date)
        ) {
            return array(
                'success' => false,
                'message' => l('Room did not change, because the guest is checking out today', true),
            );
        }

        if (
            strtotime($latest_block['check_out_date']) !== strtotime($this->selling_date) ||
            strtotime($latest_block['check_out_date']) < strtotime($check_out_date)
        ) {
            if (
                (int) $booking['state'] === INHOUSE &&
                strtotime($latest_block['check_in_date']) < strtotime($this->selling_date)
            ) {
                if (strtotime($check_out_date) > strtotime($this->selling_date)) {
                    $this->Booking_room_history_model->update_check_out_date($latest_block, $this->selling_date);

                    $new_booking_block = array(
                        'booking_id' => $booking_id,
                        'room_id' => $new_room_id,
                        'room_type_id' => $new_room_type_id,
                        'check_in_date' => $this->selling_date,
                        'check_out_date' => $check_out_date,
                    );
                    $this->Booking_room_history_model->create_booking_room_history($new_booking_block);

                    do_action('post.update.booking', array(
                        'booking_id' => $booking_id,
                        'company_id' => $this->company_id,
                        'room_id' => $new_room_id,
                        'room_type_id' => $new_room_type_id,
                        'check_in_date' => $this->selling_date,
                        'check_out_date' => $check_out_date,
                    ));
                } else {
                    $this->Booking_room_history_model->update_check_out_date($latest_block, $check_out_date);
                }
            } else {
                $this->Booking_room_history_model->update_room_id($latest_block, $new_room_id, $new_room_type_id);
                $this->Booking_room_history_model->update_check_out_date($latest_block, $check_out_date);
            }
        } else {
            $warning = l('Room did not change, because the guest is checking out today', true);
            return array('success' => false, 'message' => $warning);
        }

        $this->_combine_booking_blocks($booking_id);

        return array('success' => true, 'warning' => $warning);
    }

    /**
     * When the guest moves to a room type with a different rate, rebuild booking rate plan from effective date.
     */
    protected function _apply_folio_rates_after_room_change(
        $booking_id,
        $old_room_id,
        $new_room_id,
        $new_room_type_id,
        $new_parent_rate_plan_id,
        $effective_date,
        $check_out_date
    ) {
        $booking = $this->Booking_model->get_booking($booking_id);
        if (empty($booking['use_rate_plan']) || (int) $booking['use_rate_plan'] !== 1) {
            return;
        }

        $old_room_type_id = null;
        if ($old_room_id) {
            $old_rt = $this->Room_type_model->get_room_type_by_room_id($old_room_id);
            $old_room_type_id = isset($old_rt['id']) ? (int) $old_rt['id'] : null;
        }

        $parent_rate_plan_id = $new_parent_rate_plan_id;
        if (!$parent_rate_plan_id) {
            $parent_rate_plan_id = $this->_default_rate_plan_id_for_room_type($new_room_type_id, $booking['rate_plan_id']);
        }

        if (!$parent_rate_plan_id) {
            return;
        }

        $existing_rate_plan = $this->Rate_plan_model->get_rate_plan($booking['rate_plan_id']);
        $existing_parent = isset($existing_rate_plan['parent_rate_plan_id']) ? $existing_rate_plan['parent_rate_plan_id'] : null;

        $room_type_changed = $old_room_type_id && (int) $old_room_type_id !== (int) $new_room_type_id;
        $rate_plan_changed = $existing_parent && (int) $existing_parent !== (int) $parent_rate_plan_id;

        if (!$room_type_changed && !$rate_plan_changed && $old_room_id && (int) $old_room_id !== (int) $new_room_id) {
            return;
        }

        if ($rate_plan_changed || $room_type_changed) {
            $this->_replace_booking_rate_plan_from_parent(
                $booking_id,
                $booking,
                $parent_rate_plan_id,
                $new_room_type_id,
                $effective_date,
                $check_out_date
            );
            return;
        }

        $latest = $this->Booking_room_history_model->get_latest_booking_room_history($booking_id);
        if (empty($latest)) {
            return;
        }

        $destination = array(
            'booking_id' => $booking_id,
            'room_id' => $new_room_id,
            'check_in_date' => date('Y-m-d', strtotime($latest['check_in_date'])),
            'check_out_date' => date('Y-m-d', strtotime($latest['check_out_date'])),
        );
        $this->_update_booking_rate_plan($booking_id, $destination, $effective_date);
    }

    protected function _replace_booking_rate_plan_from_parent(
        $booking_id,
        $booking,
        $parent_rate_plan_id,
        $room_type_id,
        $date_start,
        $date_end
    ) {
        $this->load->library('rate');
        $raw_rate_array = $this->rate->get_rate_array(
            $parent_rate_plan_id,
            $date_start,
            $date_end,
            $booking['adult_count'],
            $booking['children_count']
        );

        if (empty($raw_rate_array)) {
            return;
        }

        $rate_array = array();
        foreach ($raw_rate_array as $rate) {
            $rate_array[] = array(
                'date' => $rate['date'],
                'base_rate' => $rate['base_rate'],
                'adult_1_rate' => $rate['adult_1_rate'],
                'adult_2_rate' => $rate['adult_2_rate'],
                'adult_3_rate' => $rate['adult_3_rate'],
                'adult_4_rate' => $rate['adult_4_rate'],
                'additional_adult_rate' => $rate['additional_adult_rate'],
                'additional_child_rate' => $rate['additional_child_rate'],
                'minimum_length_of_stay' => $rate['minimum_length_of_stay'],
                'maximum_length_of_stay' => $rate['maximum_length_of_stay'],
                'minimum_length_of_stay_arrival' => $rate['minimum_length_of_stay_arrival'],
                'maximum_length_of_stay_arrival' => $rate['maximum_length_of_stay_arrival'],
            );
        }

        $currency = $this->Currency_model->get_default_currency($this->company_id);
        $rate_plan_data = $this->Rate_plan_model->get_rate_plan($parent_rate_plan_id);
        $rate_plan = array(
            'rate_plan_name' => $rate_plan_data['rate_plan_name'] . ' #' . $booking_id,
            'number_of_adults_included_for_base_rate' => $booking['adult_count'],
            'rates' => get_array_with_range_of_dates($rate_array),
            'currency_id' => $currency['currency_id'],
            'charge_type_id' => $rate_plan_data['charge_type_id'],
            'company_id' => $this->company_id,
            'is_selectable' => '0',
            'room_type_id' => $room_type_id,
            'parent_rate_plan_id' => $parent_rate_plan_id,
            'policy_code' => $rate_plan_data['policy_code'],
        );

        $rates = $rate_plan['rates'];
        unset($rate_plan['rates']);

        $new_rate_plan_id = $this->Rate_plan_model->create_rate_plan($rate_plan);
        $this->Booking_model->update_booking($booking_id, array(
            'rate_plan_id' => $new_rate_plan_id,
            'charge_type_id' => $rate_plan_data['charge_type_id'],
        ));

        foreach ($rates as $rate) {
            $rate_id = $this->Rate_model->create_rate(array(
                'rate_plan_id' => $new_rate_plan_id,
                'base_rate' => $rate['base_rate'],
                'adult_1_rate' => $rate['adult_1_rate'] ? $rate['adult_1_rate'] : 0,
                'adult_2_rate' => $rate['adult_2_rate'] ? $rate['adult_2_rate'] : 0,
                'adult_3_rate' => $rate['adult_3_rate'] ? $rate['adult_3_rate'] : 0,
                'adult_4_rate' => $rate['adult_4_rate'] ? $rate['adult_4_rate'] : 0,
                'additional_adult_rate' => $rate['additional_adult_rate'] ? $rate['additional_adult_rate'] : 0,
                'additional_child_rate' => $rate['additional_child_rate'] ? $rate['additional_child_rate'] : 0,
                'minimum_length_of_stay' => $rate['minimum_length_of_stay'] ? $rate['minimum_length_of_stay'] : 0,
                'maximum_length_of_stay' => $rate['maximum_length_of_stay'] ? $rate['maximum_length_of_stay'] : 0,
                'minimum_length_of_stay_arrival' => $rate['minimum_length_of_stay_arrival'] ? $rate['minimum_length_of_stay_arrival'] : 0,
                'maximum_length_of_stay_arrival' => $rate['maximum_length_of_stay_arrival'] ? $rate['maximum_length_of_stay_arrival'] : 0,
            ));

            $date_range_id = $this->Date_range_model->create_date_range(array(
                'date_start' => $rate['date_start'],
                'date_end' => $rate['date_end'],
            ));

            $this->Date_range_model->create_date_range_x_rate(array(
                'rate_id' => $rate_id,
                'date_range_id' => $date_range_id,
            ));
        }

        do_action('post.update.booking', array(
            'booking_id' => $booking_id,
            'company_id' => $this->company_id,
            'rate_plan_id' => $new_rate_plan_id,
        ));
    }

    protected function _default_rate_plan_id_for_room_type($room_type_id, $fallback_rate_plan_id = null)
    {
        if (!$room_type_id) {
            return $fallback_rate_plan_id;
        }

        $room_type = $this->Room_type_model->get_room_type($room_type_id);
        if (empty($room_type['default_room_charge'])) {
            return $fallback_rate_plan_id;
        }

        $candidate = $room_type['default_room_charge'];
        $rp = $this->Rate_plan_model->get_rate_plan($candidate);
        if (!empty($rp['rate_plan_id'])) {
            return $rp['rate_plan_id'];
        }

        $plans = $this->Rate_plan_model->get_rate_plans_by_room_type_id($room_type_id, $fallback_rate_plan_id);
        if (!empty($plans[0]['rate_plan_id'])) {
            return $plans[0]['rate_plan_id'];
        }

        return $fallback_rate_plan_id;
    }

    protected function _room_label($room_id)
    {
        if (!$room_id) {
            return l('unassigned', true);
        }
        $room = $this->Room_model->get_room($room_id);
        return !empty($room['room_name']) ? $room['room_name'] : (string) $room_id;
    }

    /**
     * Append daily rates to the booking's custom rate plan from a date range (calendar drag / resize).
     */
    protected function _update_booking_rate_plan($booking_id, $data, $prev_checkout_date = null)
    {
        $booking = $this->Booking_model->get_booking($booking_id);

        if (isset($booking['use_rate_plan']) && $booking['use_rate_plan'] == 1) {
            $rate_plan_id = $booking['rate_plan_id'];

            $this->load->model('Rate_plan_model');
            $rate_plan = $this->Rate_plan_model->get_rate_plan($rate_plan_id);
            $parent_rate_plan_id = $rate_plan['parent_rate_plan_id'];

            if ($rate_plan_id == $parent_rate_plan_id) {
                return;
            }

            $date_start = $data['check_in_date'];
            $date_end = $data['check_out_date'];
            $adult_count = $booking['adult_count'];
            $children_count = $booking['children_count'];

            if ($prev_checkout_date) {
                $date_start = $prev_checkout_date;
            }

            $this->load->library('rate');
            $rate_array = $this->rate->get_rate_array($parent_rate_plan_id, $date_start, $date_end, $adult_count, $children_count);

            $this->load->model(array('Rate_model', 'Date_range_model'));
            foreach ($rate_array as $rate) {
                $rate_id = $this->Rate_model->create_rate(array(
                    'rate_plan_id' => $rate_plan_id,
                    'base_rate' => $rate['base_rate'],
                    'adult_1_rate' => $rate['adult_1_rate'] ? $rate['adult_1_rate'] : 0,
                    'adult_2_rate' => $rate['adult_2_rate'] ? $rate['adult_2_rate'] : 0,
                    'adult_3_rate' => $rate['adult_3_rate'] ? $rate['adult_3_rate'] : 0,
                    'adult_4_rate' => $rate['adult_4_rate'] ? $rate['adult_4_rate'] : 0,
                    'additional_adult_rate' => $rate['additional_adult_rate'] ? $rate['additional_adult_rate'] : 0,
                    'additional_child_rate' => $rate['additional_child_rate'] ? $rate['additional_child_rate'] : 0,
                    'minimum_length_of_stay' => $rate['minimum_length_of_stay'] ? $rate['minimum_length_of_stay'] : 0,
                    'maximum_length_of_stay' => $rate['maximum_length_of_stay'] ? $rate['maximum_length_of_stay'] : 0,
                    'minimum_length_of_stay_arrival' => $rate['minimum_length_of_stay_arrival'] ? $rate['minimum_length_of_stay_arrival'] : 0,
                    'maximum_length_of_stay_arrival' => $rate['maximum_length_of_stay_arrival'] ? $rate['maximum_length_of_stay_arrival'] : 0,
                ));

                $date_range_id = $this->Date_range_model->create_date_range(array(
                    'date_start' => $rate['date'],
                    'date_end' => $rate['date'],
                ));

                $this->Date_range_model->create_date_range_x_rate(array(
                    'rate_id' => $rate_id,
                    'date_range_id' => $date_range_id,
                ));
            }
        }
    }
}
