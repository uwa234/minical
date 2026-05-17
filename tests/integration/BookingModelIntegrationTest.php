<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/support/CiIntegrationBootstrap.php';

class BookingModelIntegrationTest extends TestCase
{
    private static $bootstrapped = false;

    protected function setUp(): void
    {
        if (!CiIntegrationBootstrap::isConfigured()) {
            $this->markTestSkipped('Set TEST_DATABASE_HOST, TEST_DATABASE_USER, TEST_DATABASE_PASS, and TEST_DATABASE_NAME to run integration tests.');
        }

        if (!self::$bootstrapped) {
            $this->loadFixture();
            self::$bootstrapped = true;
        }

        $db = CiIntegrationBootstrap::database();
        $db->empty_table('booking');
    }

    public function testBookingBelongsToCompanyReturnsTrueForMatchingRow()
    {
        $db = CiIntegrationBootstrap::database();
        $db->insert('booking', array(
            'company_id' => 42,
            'state' => 0,
            'children_count' => 0,
            'is_deleted' => 0,
        ));
        $bookingId = $db->insert_id();

        $model = CiIntegrationBootstrap::bookingModel();

        $this->assertTrue($model->booking_belongs_to_company($bookingId, 42));
        $this->assertFalse($model->booking_belongs_to_company($bookingId, 99));
    }

    public function testInsertOtaBookingPersistsRow()
    {
        $model = CiIntegrationBootstrap::bookingModel();

        $this->markTestSkippedIfOtaTableMissing();

        $model->insert_ota_booking(array(
            'ota_booking_id' => 'TEST-123',
            'company_id' => 7,
        ));

        $db = CiIntegrationBootstrap::database();
        $query = $db->get_where('ota_bookings', array('ota_booking_id' => 'TEST-123'));

        $this->assertSame(1, $query->num_rows());
    }

    public function testCustomerTotalBookingsCountsActiveRows()
    {
        $db = CiIntegrationBootstrap::database();
        $db->insert('booking', array(
            'company_id' => 1,
            'booking_customer_id' => 500,
            'state' => 0,
            'children_count' => 0,
            'is_deleted' => 0,
        ));
        $db->insert('booking', array(
            'company_id' => 1,
            'booking_customer_id' => 500,
            'state' => 0,
            'children_count' => 0,
            'is_deleted' => 1,
        ));

        $model = CiIntegrationBootstrap::bookingModel();

        $this->assertSame(2, $model->customer_total_bookings(500));
        $this->assertSame(1, $model->customer_total_bookings(500, true));
    }

    private function loadFixture(): void
    {
        $db = CiIntegrationBootstrap::database();
        $sql = file_get_contents(dirname(__DIR__) . '/fixtures/integration_booking.sql');
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            if ($statement !== '') {
                $db->query($statement);
            }
        }
    }

    private function markTestSkippedIfOtaTableMissing(): void
    {
        $db = CiIntegrationBootstrap::database();
        if (!$db->table_exists('ota_bookings')) {
            $this->markTestSkipped('ota_bookings table not present in test database.');
        }
    }
}
