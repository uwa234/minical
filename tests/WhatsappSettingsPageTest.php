<?php

use PHPUnit\Framework\TestCase;

/**
 * Smoke-test WhatsApp settings dependencies (no full CI bootstrap).
 */
class WhatsappSettingsPageTest extends TestCase
{
    public function testExtensionFilesExist()
    {
        $base = __DIR__ . '/../public/application/extensions/whatsapp-booking-management';
        $this->assertFileExists($base . '/controllers/whatsapp_booking.php');
        $this->assertFileExists($base . '/models/whatsapp_model.php');
        $this->assertFileExists($base . '/views/settings.php');
        $this->assertFileExists($base . '/libraries/Whatsapp_paystack_service.php');
    }

    public function testSettingsViewHasNoInvalidClosingTags()
    {
        $html = file_get_contents(
            __DIR__ . '/../public/application/extensions/whatsapp-booking-management/views/settings.php'
        );
        $this->assertStringNotContainsString('</motion.div>', $html);
        $this->assertStringContainsString('</form>', $html);
    }
}
