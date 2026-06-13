<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public/application/helpers/platform_admin_helper.php';

class SubscriptionRenewalTest extends TestCase
{
    public function testExpiryStacksFromCurrentExpirationWhenActive()
    {
        $company = array(
            'subscription_state' => 'active',
            'expiration_date' => gmdate('Y-m-d', strtotime('+20 days')),
        );

        $expiry = compute_subscription_expiry_after_renewal('1 month', $company);
        $expected = gmdate('Y-m-d', strtotime($company['expiration_date'] . ' +1 month'));

        $this->assertSame($expected, $expiry);
    }

    public function testExpiryStartsFromTodayWhenNotActive()
    {
        $expiry = compute_subscription_expiry_after_renewal('1 month', null);
        $expected = gmdate('Y-m-d', strtotime('+1 month'));

        $this->assertSame($expected, $expiry);
    }

    public function testBillingButtonLabelForActivePlan()
    {
        $label = resolve_subscription_billing_button_label('active', array(
            'days_left' => 12,
            'expiration_date' => gmdate('Y-m-d', strtotime('+12 days')),
        ));

        $this->assertSame('Extend subscription', $label);
    }

    public function testBillingButtonLabelForTrialEnded()
    {
        $this->assertSame('Subscribe now', resolve_subscription_billing_button_label('trial_ended', null, true));
    }

    public function testPendingCheckoutDoesNotCommitTierId()
    {
        $meta = array(
            'pending_reference' => 'saas_1_test',
            'tier_id' => 3,
            'pending_tier_id' => 3,
        );

        $this->assertSame(0, saas_meta_committed_tier_id($meta));
    }

    public function testPaidTierIdIsCommittedAfterSuccessfulPayment()
    {
        $meta = array(
            'last_reference' => 'saas_1_paid',
            'tier_id' => 3,
        );

        $this->assertSame(3, saas_meta_committed_tier_id($meta));
    }

    public function testActivePlanKeepsCommittedTierDuringPendingUpgradeCheckout()
    {
        $meta = array(
            'last_reference' => 'saas_1_paid',
            'last_paid_at' => gmdate('c'),
            'tier_id' => 2,
            'pending_reference' => 'saas_1_new_checkout',
            'pending_tier_id' => 3,
        );

        $this->assertSame(2, saas_meta_committed_tier_id($meta));
    }

    public function testTierButtonShowsUpgradeAndDowngrade()
    {
        $current = array('id' => 2, 'sort_order' => 20, 'subscription_level' => 1);
        $lower = array('id' => 1, 'sort_order' => 10, 'subscription_level' => 0);
        $higher = array('id' => 3, 'sort_order' => 30, 'subscription_level' => 2);
        $plan = array('days_left' => 15, 'expiration_date' => gmdate('Y-m-d', strtotime('+15 days')));

        $this->assertSame('Extend subscription', resolve_subscription_tier_button_label($current, $current, 'active', $plan));
        $this->assertSame('Upgrade', resolve_subscription_tier_button_label($higher, $current, 'active', $plan));
        $this->assertSame('Downgrade', resolve_subscription_tier_button_label($lower, $current, 'active', $plan));
    }

    public function testPaidPlanGracePeriodContext()
    {
        $company = array(
            'subscription_state' => 'active',
            'expiration_date' => gmdate('Y-m-d', strtotime('-3 days')),
        );

        $ctx = build_paid_plan_expiry_context($company);
        $this->assertNotNull($ctx);
        $this->assertTrue($ctx['is_in_grace']);
        $this->assertFalse($ctx['is_past_grace']);
        $this->assertSame(4, $ctx['grace_days_remaining']);
    }

    public function testPaidPlanPastGraceTriggersLockoutReason()
    {
        putenv('IS_HOSTED_PROD_SERVICE=1');
        $company = array(
            'subscription_state' => 'active',
            'expiration_date' => gmdate('Y-m-d', strtotime('-10 days')),
        );

        $ctx = build_paid_plan_expiry_context($company);
        $this->assertTrue($ctx['is_past_grace']);
        $this->assertSame('plan_past_grace', company_billing_lockout_reason($company));
        putenv('IS_HOSTED_PROD_SERVICE');
    }

    public function testActivePlanNotPastGraceHasNoLockout()
    {
        putenv('IS_HOSTED_PROD_SERVICE=1');
        $company = array(
            'subscription_state' => 'active',
            'expiration_date' => gmdate('Y-m-d', strtotime('+5 days')),
        );

        $this->assertNull(company_billing_lockout_reason($company));
        putenv('IS_HOSTED_PROD_SERVICE');
    }
}
