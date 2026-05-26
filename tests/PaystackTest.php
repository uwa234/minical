<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public/application/libraries/Paystack.php';

class PaystackTest extends TestCase
{
    public function testBuildAndParseObeReference()
    {
        $reference = Paystack::buildObeReference(1, 42);
        $this->assertStringStartsWith('obe_1_42_', $reference);

        $parsed = Paystack::parseObeReference($reference);
        $this->assertNotNull($parsed);
        $this->assertSame(1, $parsed['company_id']);
        $this->assertSame(42, $parsed['booking_id']);
    }

    public function testToMinorUnits()
    {
        $this->assertSame(1050, Paystack::toMinorUnits(10.5));
        $this->assertSame(10000, Paystack::toMinorUnits(100));
    }

    public function testParseInvalidReference()
    {
        $this->assertNull(Paystack::parseObeReference('invalid'));
    }

    public function testBuildAndParseWhatsappReference()
    {
        $reference = Paystack::buildWhatsappReference(5, 99);
        $this->assertStringStartsWith('waps_5_99_', $reference);

        $parsed = Paystack::parseWhatsappReference($reference);
        $this->assertNotNull($parsed);
        $this->assertSame(5, $parsed['company_id']);
        $this->assertSame(99, $parsed['booking_id']);
    }
}
