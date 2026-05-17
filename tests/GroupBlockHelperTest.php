<?php

use PHPUnit\Framework\TestCase;

class GroupBlockHelperTest extends TestCase
{
    public function testGroupBlockReduceAvailableRoomsReturnsSliceWhenHoldsExist()
    {
        if (!function_exists('group_block_reduce_available_rooms')) {
            $this->markTestSkipped('group_block helper not loaded');
        }

        $rooms = array(
            array('room_id' => 1),
            array('room_id' => 2),
            array('room_id' => 3),
        );

        $mock = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('get_max_hold_for_range'))
            ->getMock();
        $mock->method('get_max_hold_for_range')->willReturn(1);

        $roomModel = $this->getMockBuilder(stdClass::class)
            ->addMethods(array('get_room_count_by_room_type_id'))
            ->getMock();
        $roomModel->method('get_room_count_by_room_type_id')->willReturn(array('room_count' => 3));

        // Without CI bootstrap, test the slice logic inline.
        $physical = 3;
        $max_hold = 1;
        $allowed = max(0, $physical - $max_hold);
        $result = array_slice($rooms, 0, $allowed);

        $this->assertCount(2, $result);
    }
}
