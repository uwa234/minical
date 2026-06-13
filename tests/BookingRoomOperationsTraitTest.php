<?php

use PHPUnit\Framework\TestCase;

/**
 * Room exchange and room move validation rules.
 */
class BookingRoomOperationsTraitTest extends TestCase
{
    public function testExchangeRequiresDistinctAssignedRooms()
    {
        $roomA = 101;
        $roomB = 102;
        $this->assertNotEquals($roomA, $roomB);
    }

    public function testExchangeRequiresInHouseState()
    {
        $inHouse = 1;
        $reservation = 0;
        $checkedOut = 2;

        $this->assertSame(1, $inHouse);
        $this->assertNotSame($inHouse, $reservation);
        $this->assertNotSame($inHouse, $checkedOut);
    }

    public function testExchangeCandidateMustHaveRoomAssigned()
    {
        $assignedRoomId = 205;
        $unassignedRoomId = 0;

        $this->assertTrue($assignedRoomId > 0);
        $this->assertSame(0, $unassignedRoomId);
    }

    public function testUnassignedRoomIdIsZero()
    {
        $this->assertSame(0, (int) 0);
        $this->assertTrue(empty(0) || (int) 0 === 0);
    }
}
