<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public/application/helpers/platform_admin_helper.php';

class PlatformAdminCommunicationTest extends TestCase
{
    public function testApplyPlatformEmailTemplateVars()
    {
        $result = apply_platform_email_template_vars(
            'Hello {first_name}, welcome to {property_name}.',
            array('first_name' => 'Ada', 'property_name' => 'Sunrise Hotel')
        );

        $this->assertSame('Hello Ada, welcome to Sunrise Hotel.', $result);
    }

    public function testWebhookEventLabels()
    {
        $labels = platform_webhook_event_labels();

        $this->assertArrayHasKey('trial_signup', $labels);
        $this->assertArrayHasKey('payment_received', $labels);
        $this->assertArrayHasKey('debtor_overdue', $labels);
    }

    public function testWebhookEnabledEventsFromStorageDefaultsToAll()
    {
        $events = platform_webhook_enabled_events_from_storage('');

        $this->assertSame(array('trial_signup', 'payment_received', 'debtor_overdue'), $events);
    }

    public function testWebhookEnabledEventsFromJson()
    {
        $events = platform_webhook_enabled_events_from_storage(json_encode(array('payment_received', 'invalid')));

        $this->assertSame(array('payment_received'), $events);
    }

    public function testBuildTrialLeadOutreachEmailFallback()
    {
        $email = build_trial_lead_outreach_email(array(
            'first_name' => 'Ada',
            'property_name' => 'Sunrise Hotel',
            'number_of_rooms' => 12,
            'email' => 'ada@example.com',
        ));

        $this->assertStringContainsString('Sunrise Hotel', $email['subject']);
        $this->assertStringContainsString('Ada', $email['body']);
    }

    public function testBuildWinBackEmailFallback()
    {
        $email = build_win_back_email(array('name' => 'Sunset Inn'));

        $this->assertStringContainsString('Sunset Inn', $email['subject']);
        $this->assertStringContainsString('Sunset Inn', $email['body']);
    }
}
