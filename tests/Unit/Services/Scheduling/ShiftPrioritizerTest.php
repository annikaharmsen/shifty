<?php

namespace Tests\Unit\Services\Scheduling;

use App\Models\Schedule;
use App\Models\Shift;
use App\Services\Scheduling\ShiftPrioritizer;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Builders\RoleBuilder;
use Tests\Generators\EmployeeGenerator;
use Tests\Generators\ScheduleTemplateGenerator;
use Tests\TestCase;

/**
 * Tests that ShiftPrioritizer prioritizes shifts based on the formula
 *
 * priority = (scarcity / availability) * importance
 *
 * where
 * - scarcity = total competing shift hours,
 * - availability = candidate count * shift duration, and
 * - necessity = .75 for on call / 1 for live shifts
 */

class ShiftPrioritizerTest extends TestCase
{
    use RefreshDatabase;

    private ShiftPrioritizer $prioritizer;
    private Shift $sampleShift;

    protected function setUp(): void
    {
        parent::setUp();

        $serverRole = RoleBuilder::create()->withTitle('server')->build();
        $bartenderRole = RoleBuilder::create()->withTitle('bartender')->build();

        $employees = EmployeeGenerator::generate($serverRole, $bartenderRole);
        $scheduleTemplate = ScheduleTemplateGenerator::generate($serverRole, $bartenderRole);
        $instanceShifts = $scheduleTemplate->getInstantiatedShifts(CarbonImmutable::now()->startOfWeek());

        // Create and persist schedule with shifts
        $schedule = Schedule::create([
            'establishment_id' => $scheduleTemplate->establishment_id,
            'week_start_date' => CarbonImmutable::now()->startOfWeek(),
        ]);

        // Persist all shifts to the database
        foreach ($instanceShifts as $shift) {
            Shift::create([
                'schedule_id' => $schedule->id,
                'role_id' => $shift->role->id,
                'start_datetime' => $shift->start_datetime,
                'duration' => $shift->duration,
                'is_on_call' => $shift->is_on_call,
            ]);
        }

        // Refresh to load the shifts relationship
        $schedule->refresh();

        $this->sampleShift = $schedule->shifts[5];

        $this->prioritizer = new ShiftPrioritizer($employees, $schedule);
    }

    #[Test]
    public function calculates_scarcity_for_standard_shift()
    {
        // ARRANGE

        $shift = $this->sampleShift;

        // ACT

        $scarcity = $this->prioritizer->getShiftHoursOverlapping($shift->timeblock);

        // ASSERT

        $expectedScarcity = 10;

        $this->assertEquals(
            $expectedScarcity,
            $scarcity,
            "Scarcity should be {$expectedScarcity}"
        );

    }

    #[Test]
    public function calculates_importance_for_standard_shift()
    {
        // ARRANGE

        $shift = $this->sampleShift;

        // ACT

        $importance = $this->prioritizer->getImportance($shift);

        // ASSERT

        $expectedImportance = 5; // 5 hour duration * 1 (live, not on call)

        $this->assertEquals(
            $expectedImportance,
            $importance,
            "Priority should be {$expectedImportance}"
        );

    }

    #[Test]
    public function calculates_priority_for_standard_shift()
    {
        // ARRANGE

        $shift = $this->sampleShift;

        // ACT

        $scarcity = $this->prioritizer->getShiftHoursOverlapping($shift->timeblock);
        $importance = $this->prioritizer->getImportance($shift);
        $priority = $this->prioritizer->getPriority($shift);

        // ASSERT

        $scarcity = 10;
        $availability = 25;
        $importance = 5;

        $expectedPriority = $scarcity / $availability * $importance /* 2.0 */ ;

        $this->assertEquals(
            $expectedPriority,
            $priority,
            "Priority should be {$expectedPriority} for shift with scarcity={$scarcity}, availability={$availability}, and importance={$importance}"
        );

    }
}
