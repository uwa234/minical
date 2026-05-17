<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public/application/helpers/paystack_obe_helper.php';

class PaystackObeHelperTest extends TestCase
{
    private function gateway($mode = OBE_PAYMENT_MODE_BOTH)
    {
        return array(
            'selected_payment_gateway' => 'paystack',
            'obe_online_payment_mode' => $mode,
        );
    }

    public function testAllowedMethodsBoth()
    {
        $methods = obe_allowed_payment_methods($this->gateway(OBE_PAYMENT_MODE_BOTH), true);
        $this->assertSame(array('paystack', 'pay_at_hotel'), $methods);
    }

    public function testAllowedMethodsPaystackOnly()
    {
        $methods = obe_allowed_payment_methods($this->gateway(OBE_PAYMENT_MODE_PAYSTACK_ONLY), true);
        $this->assertSame(array('paystack'), $methods);
    }

    public function testAllowedMethodsPayAtHotelOnly()
    {
        $methods = obe_allowed_payment_methods($this->gateway(OBE_PAYMENT_MODE_PAY_AT_HOTEL_ONLY), true);
        $this->assertSame(array('pay_at_hotel'), $methods);
    }

    public function testReturnsNullWhenNotPaystack()
    {
        $gateway = array('selected_payment_gateway' => 'stripe');
        $this->assertNull(obe_allowed_payment_methods($gateway, true));
    }
}
