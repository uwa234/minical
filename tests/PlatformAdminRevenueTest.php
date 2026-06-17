<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public/application/helpers/platform_admin_helper.php';

class PlatformAdminRevenueTest extends TestCase
{
    public function testConversionDateSetOnlyWhenMissing()
    {
        $this->assertTrue(should_set_subscription_conversion_date(null));
        $this->assertTrue(should_set_subscription_conversion_date(''));
        $this->assertTrue(should_set_subscription_conversion_date('0000-00-00'));
        $this->assertFalse(should_set_subscription_conversion_date('2024-06-01'));
    }

    public function testPaymentTypeLabels()
    {
        $this->assertSame('New subscription', classify_saas_subscription_payment_type(0));
        $this->assertSame('Renewal', classify_saas_subscription_payment_type(1));
        $this->assertSame('Renewal', classify_saas_subscription_payment_type(3));
    }

    public function testDebtorReasonLabels()
    {
        $this->assertSame('Plan expired — renewal due', subscription_debtor_reason_label('plan_overdue'));
        $this->assertSame('Trial ended — not subscribed', subscription_debtor_reason_label('trial_ended'));
    }

    public function testNormalizeSaasRenewalPeriod()
    {
        $this->assertSame('1 month', normalize_saas_renewal_period('month'));
        $this->assertSame('1 year', normalize_saas_renewal_period('year'));
        $this->assertSame('1 year', normalize_saas_renewal_period('annual'));
    }

    public function testGraceExtensionFromFutureDueDate()
    {
        $future = gmdate('Y-m-d', strtotime('+5 days'));
        $expected = gmdate('Y-m-d', strtotime($future . ' +7 days'));

        $this->assertSame($expected, compute_date_after_grace_extension($future, 7));
    }

    public function testGraceExtensionFromOverdueUsesToday()
    {
        $past = gmdate('Y-m-d', strtotime('-10 days'));
        $expected = gmdate('Y-m-d', strtotime('+7 days'));

        $this->assertSame($expected, compute_date_after_grace_extension($past, 7));
    }

    public function testManualPaymentReferencePrefix()
    {
        $ref = build_manual_saas_payment_reference(42);
        $this->assertStringStartsWith('manual_admin_42_', $ref);
    }

    public function testSaasReceiptNumberFromReference()
    {
        $this->assertSame('MANUAL-REF', build_saas_receipt_number('manual-ref'));
        $this->assertStringStartsWith('VEU-', build_saas_receipt_number(''));
    }

    public function testSubscriptionDebtorLastContactedAt()
    {
        $this->assertNull(subscription_debtor_last_contacted_at(null));
        $meta = json_encode(array('debtor_last_contacted_at' => '2026-05-01T12:00:00+00:00'));
        $this->assertSame('2026-05-01T12:00:00+00:00', subscription_debtor_last_contacted_at($meta));
    }

    public function testCustomerReceiptTokenValidation()
    {
        $token = saas_subscription_receipt_token(42, 'saas_42_test');
        $this->assertTrue(saas_subscription_receipt_token_valid(42, 'saas_42_test', $token));
        $this->assertFalse(saas_subscription_receipt_token_valid(42, 'saas_42_test', 'invalid'));
        $this->assertFalse(saas_subscription_receipt_token_valid(99, 'saas_42_test', $token));
    }
}
