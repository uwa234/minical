<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'models/permission_model.php';

/**
 * Tests permission rules that do not require a database connection.
 */
class PermissionModelTest extends TestCase
{
    public function testCronControllerIsPublic()
    {
        $model = $this->createPermissionModel();

        $this->assertTrue($model->is_public('cron', 'delete_channex_credit_cards'));
        $this->assertTrue($model->is_public('channels', 'ical_export'));
        $this->assertTrue($model->is_public('cron', 'import_booking_com_ical'));
        $this->assertTrue($model->is_public('cron', 'hourly'));
    }

    public function testInvoiceReadOnlyEndpointsArePublic()
    {
        $model = $this->createPermissionModel();

        $this->assertTrue($model->is_public('invoice', 'show_invoice_read_only'));
        $this->assertFalse($model->is_public('invoice', 'index'));
    }

    public function testBookingEndpointsAreNotPublicByDefault()
    {
        $model = $this->createPermissionModel();

        $this->assertFalse($model->is_public('booking', 'index'));
        $this->assertFalse($model->is_public('booking', 'create_booking'));
    }

    private function createPermissionModel()
    {
        return new PermissionModelTestDouble();
    }
}

class PermissionModelTestDouble extends Permission_model
{
    public $session;

    public function __construct()
    {
        $this->session = new stdClass();
        $this->session->userdata = array();
    }
}
