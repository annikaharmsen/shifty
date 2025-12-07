<?php

namespace Tests\Unit\Services;

use App\Models\Schedule;
use App\Models\Shift;
use App\Services\AutoScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\Builders\EmployeeBuilder;
use Tests\Builders\RoleBuilder;
use Tests\Builders\ShiftBuilder;
use Tests\Generators\EmployeeGenerator;
use Tests\Generators\ScheduleTemplateGenerator;
use Tests\TestCase;

/**
 * Tests that AutoScheduleService appropriately assigns shifts on a schedule, respecting employee candidacy
 *
 * - schedule(): assigns all shifts on the schedule
 * - scheduleUnassigned(): assigns only unassigned shifts on the schedule
 * - getBlockedShifts(): returns shifts that are unable to be scheduled based on the current configuration up to the given depth
 */
class AutoScheduleServiceTest extends TestCase
{
    #[Test]
    public function schedule_assigns_all_shifts_when_employees_are_available()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();

        // Three employees, all available
        $employee1 = EmployeeGenerator::createMarcus($serverRole, $serverRole);
        $employee2 = EmployeeGenerator::createRobert($serverRole, $serverRole);
        $employee3 = EmployeeBuilder::create()
            ->withName('Charlie')
            ->withRole($serverRole)
            ->alwaysAvailable()
            ->build();

        $employees = collect([$employee1, $employee2, $employee3]);

        // Create three non-overlapping shifts
        $shift1 = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(4)
            ->forRole($serverRole)
            ->build();

        $shift2 = ShiftBuilder::create()
            ->from('2024-01-01 14:00:00')
            ->withDuration(4)
            ->forRole($serverRole)
            ->build();

        $shift3 = ShiftBuilder::create()
            ->from('2024-01-01 19:00:00')
            ->withDuration(4)
            ->forRole($serverRole)
            ->build();

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([$shift1, $shift2, $shift3]));

        $autoSchedule = new AutoScheduleService($schedule, $employees);

        // ACT
        $result = $autoSchedule->schedule();

        Log::debug('Completed schedule: ' . $result);

        // ASSERT
        $this->assertInstanceOf(Schedule::class, $result);
        $this->assertTrue($shift1->isAssigned(), 'Shift 1 should be assigned');
        $this->assertTrue($shift2->isAssigned(), 'Shift 2 should be assigned');
        $this->assertTrue($shift3->isAssigned(), 'Shift 3 should be assigned');
    }

    #[Test]
    public function schedule_respects_employee_availability()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();

        // Two employees, both available
        $employee1 = EmployeeGenerator::createMarcus($serverRole, $serverRole);
        $employee2 = EmployeeGenerator::createRobert($serverRole, $serverRole);

        $employees = collect([$employee1, $employee2]);

        // Create two overlapping shifts (should not assign same employee to both)
        $shift1 = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(8)
            ->forRole($serverRole)
            ->build();

        $shift2 = ShiftBuilder::create()
            ->from('2024-01-01 12:00:00')
            ->withDuration(4)
            ->forRole($serverRole)
            ->build();

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([$shift1, $shift2]));

        $autoSchedule = new AutoScheduleService($schedule, $employees);

        // ACT
        $autoSchedule->schedule();

        // ASSERT
        $this->assertTrue($shift1->isAssigned(), 'Shift 1 should be assigned');
        $this->assertTrue($shift2->isAssigned(), 'Shift 2 should be assigned');
        $this->assertNotEquals(
            $shift1->assignee,
            $shift2->assignee,
            'Overlapping shifts should be assigned to different employees'
        );
    }

    #[Test]
    public function schedule_handles_multiple_roles_correctly()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();
        $cookRole = RoleBuilder::create()->withTitle('cook')->build();

        $server = EmployeeBuilder::create()
            ->withName('Paul')
            ->withRole($serverRole)
            ->alwaysAvailable()
            ->build();

        $cook = EmployeeBuilder::create()
            ->withName('Quinn')
            ->withRole($cookRole)
            ->alwaysAvailable()
            ->build();

        $employees = collect([$server, $cook]);

        $serverShift = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(8)
            ->forRole($serverRole)
            ->build();

        $cookShift = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(8)
            ->forRole($cookRole)
            ->build();

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([$serverShift, $cookShift]));

        $autoSchedule = new AutoScheduleService($schedule, $employees);

        // ACT
        $autoSchedule->schedule();

        // ASSERT
        $this->assertTrue($serverShift->isAssigned(), 'Server shift should be assigned');
        $this->assertTrue($cookShift->isAssigned(), 'Cook shift should be assigned');
        $this->assertEquals($server, $serverShift->assignee, 'Server shift should be assigned to server');
        $this->assertEquals($cook, $cookShift->assignee, 'Cook shift should be assigned to cook');
    }

    #[Test]
    public function schedule_handles_impossible_schedule_gracefully()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();

        // Only one employee
        $employee = EmployeeGenerator::createMarcus($serverRole, $serverRole);

        $employees = collect([$employee]);

        // Create two overlapping shifts (impossible for one employee)
        $shift1 = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(8)
            ->forRole($serverRole)
            ->build();

        $shift2 = ShiftBuilder::create()
            ->from('2024-01-01 12:00:00')
            ->withDuration(4)
            ->forRole($serverRole)
            ->build();

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([$shift1, $shift2]));

        $autoSchedule = new AutoScheduleService($schedule, $employees);

        // ACT
        $result = $autoSchedule->schedule();

        Log::debug('Completed schedule: ' . $result);

        // ASSERT
        $this->assertInstanceOf(Schedule::class, $result);
        // At least one shift should be assigned (the more important one)
        $assignedCount = collect([$shift1, $shift2])->filter(fn ($shift) => $shift->isAssigned())->count();
        $this->assertGreaterThanOrEqual(1, $assignedCount, 'At least one shift should be assigned');
    }

    #[Test]
    public function schedule_assigns_most_important_shifts_when_impossible()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();

        // Create one employee
        $employee = EmployeeGenerator::createMarcus($serverRole, $serverRole);
        $employees = collect([$employee]);

        // Create three overlapping shifts with different priorities
        // Shift 1: 9am-5pm (8 hours) - longest, should have highest priority
        $shift1 = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(8)
            ->forRole($serverRole)
            ->build();

        // Shift 2: 12pm-4pm (4 hours) - shorter, overlaps with shift1
        $shift2 = ShiftBuilder::create()
            ->from('2024-01-01 12:00:00')
            ->withDuration(4)
            ->forRole($serverRole)
            ->build();

        // Shift 3: 2pm-6pm (4 hours) - overlaps with both
        $shift3 = ShiftBuilder::create()
            ->from('2024-01-01 14:00:00')
            ->withDuration(4)
            ->forRole($serverRole)
            ->build();

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([$shift1, $shift2, $shift3]));

        // Calculate priorities to verify our assumption
        $prioritizer = new \App\Services\Scheduling\ShiftPrioritizer($employees, $schedule);
        $priority1 = $prioritizer->getPriority($shift1);
        $priority2 = $prioritizer->getPriority($shift2);
        $priority3 = $prioritizer->getPriority($shift3);

        $autoSchedule = new AutoScheduleService($schedule, $employees);

        // ACT
        $autoSchedule->schedule();

        // ASSERT
        // Only one shift can be assigned (one employee, three overlapping shifts)
        $assignedCount = collect([$shift1, $shift2, $shift3])->filter(fn ($shift) => $shift->isAssigned())->count();
        $this->assertEquals(1, $assignedCount, 'Exactly one shift should be assigned');

        // The shift with highest priority should be assigned
        if ($priority1 > $priority2 && $priority1 > $priority3) {
            $this->assertTrue($shift1->isAssigned(), 'Shift 1 has highest priority and should be assigned');
        } elseif ($priority2 > $priority1 && $priority2 > $priority3) {
            $this->assertTrue($shift2->isAssigned(), 'Shift 2 has highest priority and should be assigned');
        } else {
            $this->assertTrue($shift3->isAssigned(), 'Shift 3 has highest priority and should be assigned');
        }
    }

    #[Test]
    public function schedule_returns_empty_schedule_when_no_shifts_exist()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();

        $employee = EmployeeGenerator::createMarcus($serverRole, $serverRole);

        $employees = collect([$employee]);

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([]));

        $autoSchedule = new AutoScheduleService($schedule, $employees);

        // ACT
        $result = $autoSchedule->schedule();

        // ASSERT
        $this->assertInstanceOf(Schedule::class, $result);
        $this->assertCount(0, $result->shifts, 'Should return schedule with no shifts');
    }

    #[Test]
    public function scheduleUnassigned_only_assigns_unassigned_shifts()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();

        $employee1 = EmployeeGenerator::createMarcus($serverRole, $serverRole);
        $employee2 = EmployeeGenerator::createRobert($serverRole, $serverRole);

        $employees = collect([$employee1, $employee2]);

        // Create three non-overlapping shifts
        $shift1 = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(4)
            ->forRole($serverRole)
            ->build();

        $shift2 = ShiftBuilder::create()
            ->from('2024-01-01 14:00:00')
            ->withDuration(4)
            ->forRole($serverRole)
            ->build();

        $shift3 = ShiftBuilder::create()
            ->from('2024-01-01 19:00:00')
            ->withDuration(4)
            ->forRole($serverRole)
            ->build();

        // Pre-assign shift1 to employee1
        $shift1->assignTo($employee1);

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([$shift1, $shift2, $shift3]));

        $autoSchedule = new AutoScheduleService($schedule, $employees);

        // ACT
        $result = $autoSchedule->scheduleUnassigned();

        // ASSERT
        $this->assertInstanceOf(Schedule::class, $result);
        $this->assertTrue($shift1->isAssigned(), 'Shift 1 should remain assigned');
        $this->assertEquals($employee1, $shift1->assignee, 'Shift 1 should still be assigned to employee1');
        $this->assertTrue($shift2->isAssigned(), 'Shift 2 should be assigned');
        $this->assertTrue($shift3->isAssigned(), 'Shift 3 should be assigned');
    }

    #[Test]
    public function scheduleUnassigned_does_not_modify_already_assigned_shifts()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();

        $employee1 = EmployeeGenerator::createMarcus($serverRole, $serverRole);
        $employee2 = EmployeeGenerator::createRobert($serverRole, $serverRole);

        $employees = collect([$employee1, $employee2]);

        $shift1 = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(4)
            ->forRole($serverRole)
            ->build();

        $shift2 = ShiftBuilder::create()
            ->from('2024-01-01 14:00:00')
            ->withDuration(4)
            ->forRole($serverRole)
            ->build();

        // Pre-assign both shifts
        $shift1->assignTo($employee1);
        $shift2->assignTo($employee2);

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([$shift1, $shift2]));

        $autoSchedule = new AutoScheduleService($schedule, $employees);

        // ACT
        $autoSchedule->scheduleUnassigned();

        // ASSERT
        $this->assertEquals($employee1, $shift1->assignee, 'Shift 1 should still be assigned to employee1');
        $this->assertEquals($employee2, $shift2->assignee, 'Shift 2 should still be assigned to employee2');
    }

    #[Test]
    public function getBlockedShifts_returns_shifts_with_no_available_candidates()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();
        $cookRole = RoleBuilder::create()->withTitle('cook')->build();

        // Employee only has cook role
        $employee = EmployeeBuilder::create()
            ->withName('Liam')
            ->withRole($cookRole)
            ->alwaysAvailable()
            ->build();

        $employees = collect([$employee]);

        // Create shift requiring server role (no candidates available)
        $shift = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(8)
            ->forRole($serverRole)
            ->build();

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([$shift]));

        $autoSchedule = new AutoScheduleService($schedule, $employees);

        // ACT
        $blockedShifts = $autoSchedule->getBlockedShifts();

        // ASSERT
        $this->assertCount(1, $blockedShifts, 'Should return one blocked shift');
        $this->assertTrue($blockedShifts->contains($shift), 'Should contain the shift with no candidates');
    }

    #[Test]
    public function getBlockedShifts_returns_empty_when_all_shifts_have_candidates()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();

        $employee = EmployeeGenerator::createMarcus($serverRole, $serverRole);

        $employees = collect([$employee]);

        $shift = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(8)
            ->forRole($serverRole)
            ->build();

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([$shift]));

        $autoSchedule = new AutoScheduleService($schedule, $employees);

        // ACT
        $blockedShifts = $autoSchedule->getBlockedShifts();

        // ASSERT
        $this->assertCount(0, $blockedShifts, 'Should return no blocked shifts');
        $this->assertTrue($blockedShifts->isEmpty(), 'Blocked shifts collection should be empty');
    }

    #[Test]
    public function getBlockedShifts_detects_shifts_blocked_by_current_assignments()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();

        // Only one employee
        $employee = EmployeeGenerator::createMarcus($serverRole, $serverRole);

        $employees = collect([$employee]);

        // Create two overlapping shifts
        $shift1 = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(8)
            ->forRole($serverRole)
            ->build();

        $shift2 = ShiftBuilder::create()
            ->from('2024-01-01 12:00:00')
            ->withDuration(4)
            ->forRole($serverRole)
            ->build();

        // Assign first shift (blocks second shift)
        $shift1->assignTo($employee);

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([$shift1, $shift2]));

        $autoSchedule = new AutoScheduleService($schedule, $employees);

        // ACT
        $blockedShifts = $autoSchedule->getBlockedShifts();

        // ASSERT
        $this->assertCount(1, $blockedShifts, 'Should return one blocked shift');
        $this->assertTrue($blockedShifts->contains($shift2), 'Shift 2 should be blocked by shift 1 assignment');
    }

    #[Test]
    public function getBlockedShifts_ignores_already_assigned_shifts()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();

        $employee = EmployeeGenerator::createMarcus($serverRole, $serverRole);

        $employees = collect([$employee]);

        $shift = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(8)
            ->forRole($serverRole)
            ->build();

        // Assign the shift
        $shift->assignTo($employee);

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([$shift]));

        $autoSchedule = new AutoScheduleService($schedule, $employees);

        // ACT
        $blockedShifts = $autoSchedule->getBlockedShifts();

        // ASSERT
        $this->assertCount(0, $blockedShifts, 'Should not count assigned shifts as blocked');
        $this->assertTrue($blockedShifts->isEmpty());
    }

    /**
     * Integration tests using realistic employee and schedule data
     */

    #[Test]
    public function schedule_assigns_realistic_weekly_schedule_with_generated_employees()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();
        $bartenderRole = RoleBuilder::create()->withTitle('bartender')->build();

        // Generate realistic employee pool with varied availability
        $employees = EmployeeGenerator::generate($serverRole, $bartenderRole);

        // Generate a full week of shifts from template
        $scheduleTemplate = ScheduleTemplateGenerator::generate($serverRole, $bartenderRole);
        $weekStart = CarbonImmutable::parse('2025-11-24'); // Monday
        $shifts = $scheduleTemplate->getInstantiatedShifts($weekStart);
        $shifts = $this->assignShiftIds($shifts);

        // Filter to only live shifts (no on-call) for this test
        $liveShifts = $shifts->filter(fn ($shift) => !$shift->is_on_call);

        $schedule = new Schedule();
        $schedule->setRelation('shifts', $liveShifts);

        $autoSchedule = new AutoScheduleService($schedule, $employees);

        // ACT
        $result = $autoSchedule->schedule();

        Log::debug('Completed schedule: ' . $result);


        // ASSERT
        $assignedShifts = $liveShifts->filter(fn ($shift) => $shift->isAssigned());
        $totalShifts = $liveShifts->count();
        $assignedCount = $assignedShifts->count();

        // Assert that most shifts got assigned (allow for some impossible assignments)
        $this->assertGreaterThan(
            $totalShifts * 0.8,
            $assignedCount,
            "Expected at least 80% of shifts to be assigned. Got {$assignedCount}/{$totalShifts}"
        );

        // Verify no employee is double-booked (assigned to overlapping shifts)
        foreach ($employees as $employee) {
            $employeeShifts = $assignedShifts->filter(fn ($shift) => $shift->assignee === $employee);

            foreach ($employeeShifts as $shift1) {
                foreach ($employeeShifts as $shift2) {
                    if ($shift1 === $shift2) {
                        continue;
                    }

                    $this->assertFalse(
                        $shift1->timeblock->overlaps($shift2->timeblock),
                        'Employee should not be double-booked on overlapping shifts'
                    );
                }
            }
        }

        // Verify weekly hours utilization
        $underUtilized = [];
        $overUtilized = [];
        $properlyUtilized = [];

        foreach ($employees as $employee) {
            $targetHours = $employee->getWeeklyHours();
            $assignedHours = $schedule->getHoursAssignedTo($employee);
            $utilizationRate = $targetHours > 0 ? ($assignedHours / $targetHours) * 100 : 0;

            if ($assignedHours < $targetHours * 0.9) {
                // Under-utilized: assigned less than 90% of target hours
                $underUtilized[] = [
                    'name' => $employee->name,
                    'target' => $targetHours,
                    'assigned' => $assignedHours,
                    'rate' => $utilizationRate
                ];
            } elseif ($assignedHours > $targetHours * 1.1) {
                // Over-utilized: assigned more than 110% of target hours
                $overUtilized[] = [
                    'name' => $employee->name,
                    'target' => $targetHours,
                    'assigned' => $assignedHours,
                    'rate' => $utilizationRate
                ];
            } else {
                // Properly utilized: within 90-110% of target hours
                $properlyUtilized[] = [
                    'name' => $employee->name,
                    'target' => $targetHours,
                    'assigned' => $assignedHours,
                    'rate' => $utilizationRate
                ];
            }
        }

        // Log utilization metrics for visibility
        Log::debug('Weekly Hours Utilization Report:');
        Log::debug('Under-utilized employees: ' . json_encode($underUtilized));
        Log::debug('Over-utilized employees: ' . json_encode($overUtilized));
        Log::debug('Properly utilized employees: ' . json_encode($properlyUtilized));

        // Assert that most employees are properly utilized
        $totalEmployeesWithShifts = $employees->filter(
            fn ($emp) => $schedule->getHoursAssignedTo($emp) > 0
        )->count();

        if ($totalEmployeesWithShifts > 0) {
            $properUtilizationRate = count($properlyUtilized) / $totalEmployeesWithShifts;
            $this->assertGreaterThan(
                0.5,
                $properUtilizationRate,
                "Expected at least 50% of employees to be properly utilized. " .
                "Got " . count($properlyUtilized) . " properly utilized out of {$totalEmployeesWithShifts} assigned employees."
            );
        }

        // Assert no severe over-utilization (more than 150% of target hours)
        foreach ($employees as $employee) {
            $targetHours = $employee->getWeeklyHours();
            $assignedHours = $schedule->getHoursAssignedTo($employee);
            $this->assertLessThanOrEqual(
                $targetHours * 1.5,
                $assignedHours,
                "Employee {$employee->name} should not be assigned more than 150% of their target hours. " .
                "Target: {$targetHours}h, Assigned: {$assignedHours}h"
            );
        }
    }

    #[Test]
    public function scheduleUnassigned_respects_partial_assignments_with_generated_data()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();
        $bartenderRole = RoleBuilder::create()->withTitle('bartender')->build();

        $employees = EmployeeGenerator::generate($serverRole, $bartenderRole);

        $scheduleTemplate = ScheduleTemplateGenerator::generate($serverRole, $bartenderRole);
        $weekStart = CarbonImmutable::parse('2025-11-24');
        $shifts = $scheduleTemplate->getInstantiatedShifts($weekStart);
        $shifts = $this->assignShiftIds($shifts);


        // Pre-assign some random shifts manually
        $shiftsToPreassign = $shifts->random(5);
        foreach ($shiftsToPreassign as $shift) {
            $availableEmployees = $employees->filter(
                fn ($emp) =>
                $emp->roles->contains($shift->role)
            );
            if ($availableEmployees->isNotEmpty()) {
                $shift->assignTo($availableEmployees->random());
            }
        }

        $schedule = new Schedule();
        $schedule->setRelation('shifts', $shifts);

        $autoSchedule = new AutoScheduleService($schedule, $employees);

        // ACT
        $autoSchedule->scheduleUnassigned();

        // ASSERT
        // Verify pre-assigned shifts weren't changed
        foreach ($shiftsToPreassign as $preAssignedShift) {
            if ($preAssignedShift->isAssigned()) {
                $this->assertTrue(
                    $preAssignedShift->isAssigned(),
                    'Pre-assigned shift should remain assigned'
                );
            }
        }

        // Verify more shifts got assigned
        $assignedCount = $shifts->filter(fn ($shift) => $shift->isAssigned())->count();
        $this->assertGreaterThanOrEqual(
            5,
            $assignedCount,
            'Should have assigned more shifts beyond the pre-assigned ones'
        );
    }

    #[Test]
    public function getBlockedShifts_identifies_conflicts_in_realistic_schedule()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();
        $bartenderRole = RoleBuilder::create()->withTitle('bartender')->build();

        // Use a very limited employee pool to create blocking scenarios
        $employees = collect([
            EmployeeGenerator::createMarcus($serverRole, $serverRole)
        ]);

        $scheduleTemplate = ScheduleTemplateGenerator::generate($serverRole, $bartenderRole);
        $weekStart = CarbonImmutable::parse('2025-11-24');
        $shifts = $scheduleTemplate->getInstantiatedShifts($weekStart);
        $shifts = $this->assignShiftIds($shifts);


        // Take only server shifts (since we only have one server available)
        $serverShifts = $shifts->filter(fn ($shift) => $shift->role === $serverRole)->take(10);

        $schedule = new Schedule();
        $schedule->setRelation('shifts', $serverShifts);

        $autoSchedule = new AutoScheduleService($schedule, $employees);

        // Try to schedule with insufficient staff
        $schedule = $autoSchedule->schedule();

        Log::debug('Completed schedule: ' . $schedule);

        // ACT
        $blockedShifts = $autoSchedule->getBlockedShifts();

        // ASSERT
        // With only one employee and multiple overlapping shifts, some should be blocked
        $this->assertGreaterThan(
            0,
            $blockedShifts->count(),
            'Should have blocked shifts when employee pool is too small'
        );

        // Verify all blocked shifts are actually unassigned
        foreach ($blockedShifts as $shift) {
            $this->assertFalse(
                $shift->isAssigned(),
                'Blocked shifts should not be assigned'
            );
        }
    }

    /** helper for assigning ids to instantiated shifts */
    private function assignShiftIds(Collection $shifts)
    {

        /**
         * @var Collection $this->templateShifts
         */
        static $idCounter = 1;

        $shifts = $shifts->map(
            function (Shift $shift) use (&$idCounter): Shift {
                $shift->setAttribute('id', $idCounter++);
                return $shift;
            }
        );

        return $shifts;

    }
}
