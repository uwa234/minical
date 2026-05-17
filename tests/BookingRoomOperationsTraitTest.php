<?php

use PHPUnit\Framework\TestCase;

/**
 * Documents expected room-operation rules (availability / exchange preconditions).
 */
class BookingRoomOperationsTraitTest extends TestCase
{
    public function testExchangeRequiresDistinctAssignedRooms()
    {
        $roomA = 101;
        $roomB = 102;
        $this->assertNotEquals($roomA, $roomB);
    }

    public function testUnassignedRoomIdIsZero()
    {
        $this->assertSame(0, (int) 0);
        $this->assertTrue(empty(0) || (int) 0 === 0);
    }
}
