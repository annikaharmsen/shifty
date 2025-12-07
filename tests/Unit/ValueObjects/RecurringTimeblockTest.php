<?php

namespace Tests\Unit\ValueObjects;

use PHPUnit\Framework\Attributes\Test;
use App\ValueObjects\RecurringTimeblock;
use App\ValueObjects\Timeblock;
use Tests\TestCase;

/**
 * Tests that RecurringTimeblock utilities work appropriately
 *
 * overlaps($other) checks if any occurrence has any overlap with other timeblock/recurringtimeblock
 *
 * contains($other) checks if $other falls entirely within an occurrence of the timeblock
 */

class RecurringTimeblockTest extends TestCase
{
    #[Test]
    public function detects_overlap_with_simple_timeblock()
    {
        // ARRANGE - Weekly recurring on Mondays 11am-1pm
        $recurring = new RecurringTimeblock(
            '11/24/2025 11am',    // Monday
            '2 hours',
            '1 week',
            '12/15/2025'
        );

        $overlapping = new Timeblock('11/24/2025 12pm', '2 hours');     // Monday 12pm-2pm (overlaps first occurrence)
        $nonOverlapping = new Timeblock('11/25/2025 11am', '2 hours'); // Tuesday (doesn't overlap any Monday)

        // ACT & ASSERT
        $this->assertTrue($recurring->overlaps($overlapping));
        $this->assertFalse($recurring->overlaps($nonOverlapping));
    }

    #[Test]
    public function detects_overlap_with_another_recurring_timeblock()
    {
        // ARRANGE
        $recurringA = new RecurringTimeblock(
            '11/24/2025 11am',    // Monday 11am-1pm weekly
            '2 hours',
            '1 week',
            '12/15/2025'
        );

        $recurringB = new RecurringTimeblock(
            '11/24/2025 12pm',    // Monday 12pm-2pm weekly (overlaps)
            '2 hours',
            '1 week',
            '12/15/2025'
        );

        $recurringC = new RecurringTimeblock(
            '11/25/2025 11am',    // Tuesday 11am-1pm weekly (no overlap)
            '2 hours',
            '1 week',
            '12/15/2025'
        );

        // ACT & ASSERT
        $this->assertTrue($recurringA->overlaps($recurringB));
        $this->assertFalse($recurringA->overlaps($recurringC));
    }

    #[Test]
    public function detects_when_occurrence_contains_timeblock()
    {
        // ARRANGE - Daily recurring 9am-5pm
        $recurring = new RecurringTimeblock(
            '11/24/2025 9am',
            '8 hours',
            '1 day',
            '11/30/2025'
        );

        $contained = new Timeblock('11/25/2025 10am', '2 hours');      // 10am-12pm on Tuesday (contained in 9am-5pm)
        $notContained = new Timeblock('11/25/2025 4pm', '3 hours');    // 4pm-7pm on Tuesday (extends beyond 5pm)
        $wrongDay = new Timeblock('12/05/2025 10am', '2 hours');       // After end date

        // ACT & ASSERT
        $this->assertTrue($recurring->contains($contained));
        $this->assertFalse($recurring->contains($notContained));
        $this->assertFalse($recurring->contains($wrongDay));
    }
}
