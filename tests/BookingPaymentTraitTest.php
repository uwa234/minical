<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'traits/Booking_payment_trait.php';

class BookingPaymentTraitTest extends TestCase
{
    public function testSumPaymentAmounts()
    {
        $subject = new BookingPaymentTraitTestStub();
        $subject->restrict_checkout_with_balance = true;

        $total = $subject->exposeSum(array(
            array('amount' => 10.5),
            array('amount' => 4.5),
        ));

        $this->assertSame(15.0, $total);
    }

    public function testCheckoutBlockedWhenRateDoesNotMatchPayments()
    {
        $subject = new BookingPaymentTraitTestStub();
        $subject->restrict_checkout_with_balance = true;

        $blocked = $subject->exposeCheckoutBlocked(
            array(array('amount' => 50)),
            array('rate' => 100, 'balance' => 0, 'balance_without_forecast' => 0),
            '2',
            array('number_of_days' => 0)
        );

        $this->assertTrue($blocked);
    }
}

class BookingPaymentTraitTestStub
{
    use Booking_payment_trait;

    public $restrict_checkout_with_balance = false;
    public $booking_cancelled_with_balance = false;

    public function exposeSum($payments)
    {
        return $this->_sum_payment_amounts($payments);
    }

    public function exposeCheckoutBlocked($payment_details, $booking_existing_data, $new_state, $new_data)
    {
        return $this->_checkout_blocked_by_hourly_balance(
            $payment_details,
            $booking_existing_data,
            $new_state,
            $new_data
        );
    }
}
