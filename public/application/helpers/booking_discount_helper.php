<?php defined('BASEPATH') OR exit('No direct script access allowed');

define('BOOKING_DISCOUNT_NONE', 0);
define('BOOKING_DISCOUNT_PERCENT', 1);
define('BOOKING_DISCOUNT_FIXED', 2);

define('BOOKING_DISCOUNT_SCOPE_PER_NIGHT', 0);
define('BOOKING_DISCOUNT_SCOPE_TOTAL', 1);

/**
 * Apply a booking discount to a room rate amount.
 *
 * @param float $rate Base rate before discount (per pay period: night, week, or month)
 * @param int $discount_type 0=none, 1=percent, 2=fixed amount
 * @param float $discount_value Percentage (0-100) or fixed amount
 * @param int $discount_scope 0=per night/period, 1=on total stay amount
 * @param int $nights Number of nights in stay (for total-scope fixed discounts)
 * @return float Discounted rate, never negative
 */
function apply_booking_discount($rate, $discount_type, $discount_value, $discount_scope = BOOKING_DISCOUNT_SCOPE_PER_NIGHT, $nights = 1)
{
	$rate = (float) $rate;
	$discount_type = (int) $discount_type;
	$discount_value = (float) $discount_value;
	$discount_scope = (int) $discount_scope;
	$nights = max(1, (int) $nights);

	if ($discount_type === BOOKING_DISCOUNT_NONE || $discount_value <= 0) {
		return $rate;
	}

	if ($discount_type === BOOKING_DISCOUNT_PERCENT) {
		$discount_value = min(100, $discount_value);
		return max(0, round($rate * (1 - $discount_value / 100), 2, PHP_ROUND_HALF_UP));
	}

	if ($discount_type === BOOKING_DISCOUNT_FIXED) {
		if ($discount_scope === BOOKING_DISCOUNT_SCOPE_TOTAL) {
			$stay_total = $rate * $nights;
			return max(0, round(($stay_total - $discount_value) / $nights, 2, PHP_ROUND_HALF_UP));
		}
		return max(0, round($rate - $discount_value, 2, PHP_ROUND_HALF_UP));
	}

	return $rate;
}

/**
 * Count nights between booking check-in and check-out for discount calculations.
 *
 * @param array $booking_details
 * @return int
 */
function booking_discount_night_count($booking_details)
{
	if (!isset($booking_details['check_in_date'], $booking_details['check_out_date'])) {
		return 1;
	}

	$start = date_create(date('Y-m-d', strtotime($booking_details['check_in_date'])));
	$end = date_create(date('Y-m-d', strtotime($booking_details['check_out_date'])));

	if (!$start || !$end) {
		return 1;
	}

	$interval = date_diff($start, $end);
	$nights = (int) $interval->format('%a');

	return max(1, $nights);
}

/**
 * Apply stored booking discount fields to rate and residual_rate on a booking row.
 *
 * @param array $booking_details
 * @return array
 */
function apply_booking_discount_to_booking_details($booking_details)
{
	if (!is_array($booking_details) || empty($booking_details)) {
		return $booking_details;
	}

	$discount_type = isset($booking_details['discount_type']) ? (int) $booking_details['discount_type'] : 0;
	$discount_value = isset($booking_details['discount_value']) ? $booking_details['discount_value'] : 0;
	$discount_scope = isset($booking_details['discount_scope']) ? (int) $booking_details['discount_scope'] : 0;

	if ($discount_type === BOOKING_DISCOUNT_NONE || $discount_value <= 0) {
		return $booking_details;
	}

	$nights = booking_discount_night_count($booking_details);

	if (isset($booking_details['rate'])) {
		$booking_details['rate'] = apply_booking_discount(
			$booking_details['rate'],
			$discount_type,
			$discount_value,
			$discount_scope,
			$nights
		);
	}

	if (isset($booking_details['residual_rate']) && $booking_details['residual_rate']) {
		$booking_details['residual_rate'] = apply_booking_discount(
			$booking_details['residual_rate'],
			$discount_type,
			$discount_value,
			$discount_scope,
			$nights
		);
	}

	return $booking_details;
}

/**
 * Resolve the discounted room charge amount for a specific selling date.
 *
 * @param array $booking Booking row from get_booking_detail
 * @param string $selling_date Charge selling date (Y-m-d)
 * @param int|null $pay_period Charge pay period constant
 * @return float
 */
function resolve_discounted_room_charge_amount($booking, $selling_date, $pay_period = null)
{
	$discount_type = isset($booking['discount_type']) ? (int) $booking['discount_type'] : 0;
	$discount_value = isset($booking['discount_value']) ? $booking['discount_value'] : 0;
	$discount_scope = isset($booking['discount_scope']) ? (int) $booking['discount_scope'] : 0;
	$nights = booking_discount_night_count($booking);

	if (!empty($booking['use_rate_plan']) && !empty($booking['rate_plan_id'])) {
		$CI =& get_instance();
		$CI->load->library('rate');
		$date = date('Y-m-d', strtotime($selling_date));
		$end = date('Y-m-d', strtotime($date . ' +1 day'));
		$rates = $CI->rate->get_rate_array(
			$booking['rate_plan_id'],
			$date,
			$end,
			$booking['adult_count'],
			$booking['children_count']
		);
		$base = isset($rates[0]['rate']) ? (float) $rates[0]['rate'] : (float) $booking['rate'];
	} else {
		$base = (float) $booking['rate'];
	}

	return apply_booking_discount($base, $discount_type, $discount_value, $discount_scope, $nights);
}

/**
 * Update posted room charges on the invoice to reflect booking discount settings.
 *
 * @param int $booking_id
 */
function sync_booking_room_charges_with_discount($booking_id)
{
	$CI =& get_instance();
	$CI->load->helper('booking_discount');
	$CI->load->model('Booking_model');
	$CI->load->model('Charge_type_model');

	$booking = $CI->Booking_model->get_booking_detail($booking_id);
	if (!$booking) {
		return;
	}

	$room_charge_type_id = $CI->Charge_type_model->get_room_charge_type_id($booking_id);
	if (!$room_charge_type_id) {
		return;
	}

	$CI->db->where('booking_id', $booking_id);
	$CI->db->where('charge_type_id', $room_charge_type_id);
	$CI->db->where('is_deleted', '0');
	$query = $CI->db->get('charge');

	if (!$query || $query->num_rows() < 1) {
		return;
	}

	$CI->load->model('Charge_model');
	foreach ($query->result_array() as $charge) {
		$new_amount = resolve_discounted_room_charge_amount(
			$booking,
			$charge['selling_date'],
			isset($charge['pay_period']) ? $charge['pay_period'] : null
		);

		if ((float) $new_amount !== (float) $charge['amount']) {
			$CI->Charge_model->update_charge(array(
				'charge_id' => $charge['charge_id'],
				'amount' => $new_amount,
			));
		}
	}
}
