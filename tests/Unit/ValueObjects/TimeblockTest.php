<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\Timeblock;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests that Timeblock utilities work appropriately
 *
 * getEnd() returns the end of the timeblock (start + duration)
 *
 * overlaps($other) checks if timeblock has any overlap with other timeblock
 *
 * contains($other) checks if $other falls entirely within the timeblock
 *
 * getDurationHours() returns the timeblocks duration in hours
 *
 * getHoursOverlappingWith($other) returns the duration in hours of the time the two two timeblocks share (11-5, 2-7 -> 2-5 -> 3 hours)
 */

class TimeblockTest extends TestCase
{
    #[Test]
    public function returns_correct_end_time()
    {
        // ARRANGE
        $timeblock = new Timeblock('11/25/2025 11am', '5 hours');

        // ACT
        $end = $timeblock->getEnd();

        // ASSERT
        $this->assertEquals('11/25/2025 4pm', $end->format('m/d/Y ga'));
    }

    #[Test]
    public function evaluates_overlap_between_timeblocks()
    {
        // ARRANGE
        $tbA = new Timeblock('11/25/2025 11am', '5 hours'); // 11am-4pm
        $tbB = new Timeblock('11/25/2025 2pm', '6 hours');  // 2pm-8pm
        $tbC = new Timeblock('11/26/2025 2pm', '6 hours');  // next day

        // ACT & ASSERT
        // A and B overlap (2pm-4pm)
        $this->assertTrue($tbA->overlaps($tbB));
        $this->assertTrue($tbB->overlaps($tbA));

        // A and C don't overlap (different days)
        $this->assertFalse($tbA->overlaps($tbC));
        $this->assertFalse($tbC->overlaps($tbA));

        // B and C don't overlap (different days)
        $this->assertFalse($tbB->overlaps($tbC));
        $this->assertFalse($tbC->overlaps($tbB));
    }

    #[Test]
    public function detects_adjacent_timeblocks_do_not_overlap()
    {
        // ARRANGE
        $tbA = new Timeblock('11/25/2025 11am', '3 hours'); // 11am-2pm
        $tbB = new Timeblock('11/25/2025 2pm', '3 hours');  // 2pm-5pm

        // ACT & ASSERT
        $this->assertFalse($tbA->overlaps($tbB));
        $this->assertFalse($tbB->overlaps($tbA));
    }

    #[Test]
    public function detects_when_timeblock_contains_another()
    {
        // ARRANGE
        $outer = new Timeblock('11/25/2025 11am', '8 hours'); // 11am-7pm
        $inner = new Timeblock('11/25/2025 2pm', '3 hours');  // 2pm-5pm
        $partial = new Timeblock('11/25/2025 1pm', '7 hours'); // 1pm-8pm (extends beyond)

        // ACT & ASSERT
        $this->assertTrue($outer->contains($inner));
        $this->assertFalse($inner->contains($outer));
        $this->assertFalse($outer->contains($partial));
    }

    #[Test]
    public function returns_duration_in_hours()
    {
        // ARRANGE
        $tb1 = new Timeblock('11/25/2025 11am', '5 hours');
        $tb2 = new Timeblock('11/25/2025 11am', '2.5 hours');

        // ACT & ASSERT
        $this->assertEquals(5.0, $tb1->getDurationHours());
        $this->assertEquals(2.5, $tb2->getDurationHours());
    }

    #[Test]
    public function calculates_overlapping_hours()
    {
        // ARRANGE
        $tbA = new Timeblock('11/25/2025 11am', '5 hours'); // 11am-4pm
        $tbB = new Timeblock('11/25/2025 2pm', '5 hours');  // 2pm-7pm

        // ACT
        $overlap = $tbA->getHoursOverlappingWith($tbB);

        // ASSERT
        // They overlap from 2pm-4pm = 2 hours
        $this->assertEquals(2.0, $overlap);
    }

    #[Test]
    public function returns_zero_hours_for_non_overlapping_timeblocks()
    {
        // ARRANGE
        $tbA = new Timeblock('11/25/2025 11am', '5 hours');
        $tbB = new Timeblock('11/26/2025 11am', '5 hours');

        // ACT
        $overlap = $tbA->getHoursOverlappingWith($tbB);

        // ASSERT
        $this->assertEquals(0.0, $overlap);
    }
}
