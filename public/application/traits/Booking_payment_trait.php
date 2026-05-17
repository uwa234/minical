<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Payment guard helpers for booking create/update/delete flows.
 */
trait Booking_payment_trait
{
    protected function _sum_payment_amounts($payment_details)
    {
        $total = 0;

        if (!$payment_details) {
            return $total;
        }

        foreach ($payment_details as $payment) {
            $total += $payment['amount'];
        }

        return $total;
    }

    protected function _respond_booking_payment_failure($message)
    {
        echo json_encode(array('response' => 'failure', 'message' => l($message, true)));
    }

    protected function _cancellation_blocked_by_payments($booking_id, $new_state, $booking_existing_data)
    {
        if ($new_state != 4) {
            return false;
        }

        $payment_details = $this->Payment_model->get_payments($booking_id);
        if (!$payment_details) {
            return false;
        }

        $final_amount = $this->_sum_payment_amounts($payment_details);

        return isset($final_amount) &&
            ($booking_existing_data['balance'] && $booking_existing_data['balance_without_forecast']) &&
            !$this->booking_cancelled_with_balance;
    }

    protected function _checkout_blocked_by_hourly_balance($payment_details, $booking_existing_data, $new_state, $new_data)
    {
        if (
            !isset($new_data['number_of_days']) ||
            $new_data['number_of_days'] != 0 ||
            $new_state != CHECKOUT
        ) {
            return false;
        }

        if (empty($payment_details)) {
            return ($booking_existing_data['balance'] || $booking_existing_data['balance_without_forecast']) &&
                $this->restrict_checkout_with_balance;
        }

        $final_amount = $this->_sum_payment_amounts($payment_details);

        return $booking_existing_data['rate'] != $final_amount;
    }

    protected function _deletion_blocked_by_payments($booking_id)
    {
        $payment_details = $this->Payment_model->get_payments($booking_id);

        return $this->_sum_payment_amounts($payment_details) > 0;
    }
}
