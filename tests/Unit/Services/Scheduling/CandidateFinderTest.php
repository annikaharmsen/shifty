<?php

namespace Tests\Unit\Services\Scheduling;

use App\Models\Schedule;
use App\Services\Scheduling\CandidateFinder;
use App\ValueObjects\Timeblock;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Builders\AvailabilityRuleBuilder;
use Tests\Builders\EmployeeBuilder;
use Tests\Builders\RoleBuilder;
use Tests\Builders\ShiftBuilder;
use Tests\TestCase;

/**
 * Tests that CandidateFinder retrieves employees available to work a given shift
 *
 * - evaluates whether a given employee is scheduled during a given timeblock
 * - gets candidates for a given shift based on employee availability
 * - gets schedulable candidates for a given shift based on employee availability and whether they are already scheduled for any overlapping shifts
 */

class CandidateFinderTest extends TestCase
{
    use RefreshDatabase;
    #[Test]
    public function detects_when_employee_is_scheduled_during_timeblock()
    {
        // ARRANGE
        $employee = EmployeeBuilder::create()->withName('Sam')->build();

        // Create a shift from 9am-5pm
        $shift = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(8)
            ->build();
        $shift->assignTo($employee);

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([$shift]));

        $finder = new CandidateFinder(collect([$employee]), $schedule);

        // Create a timeblock that overlaps (10am-2pm)
        $overlappingTimeblock = new Timeblock(
            CarbonImmutable::parse('2024-01-01 10:00:00'),
            CarbonInterval::hours(4)
        );

        // ACT
        $isScheduled = $finder->employeeIsScheduledDuring($employee, $overlappingTimeblock);

        // ASSERT
        $this->assertTrue($isScheduled, 'Employee should be detected as scheduled during overlapping timeblock');
    }

    #[Test]
    public function detects_when_employee_is_not_scheduled_during_timeblock()
    {
        // ARRANGE
        $employee = EmployeeBuilder::create()->withName('Tina')->build();

        // Create a shift from 9am-5pm
        $shift = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(8)
            ->build();
        $shift->assignTo($employee);

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([$shift]));

        $finder = new CandidateFinder(collect([$employee]), $schedule);

        // Create a timeblock that doesn't overlap (6pm-8pm)
        $nonOverlappingTimeblock = new Timeblock(
            CarbonImmutable::parse('2024-01-01 18:00:00'),
            CarbonInterval::hours(2)
        );

        // ACT
        $isScheduled = $finder->employeeIsScheduledDuring($employee, $nonOverlappingTimeblock);

        // ASSERT
        $this->assertFalse($isScheduled, 'Employee should not be detected as scheduled during non-overlapping timeblock');
    }

    #[Test]
    public function gets_candidates_based_on_availability_and_role()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();
        $cookRole = RoleBuilder::create()->withTitle('cook')->build();

        // Employee 1: Has server role, available
        $employee1 = EmployeeBuilder::create()
            ->withName('Uma')
            ->withRole($serverRole)
            ->alwaysAvailable()
            ->build();

        // Employee 2: Has server role, NOT available (unavailable 9am-5pm on Mondays)
        $unavailableRule = AvailabilityRuleBuilder::create()
            ->from('2024-01-01 09:00:00') // Monday
            ->withDuration('8 hours')
            ->withFrequency('1 week')
            ->build();

        $employee2 = EmployeeBuilder::create()
            ->withName('Victor')
            ->withRole($serverRole)
            ->withAvailabilityRule($unavailableRule)
            ->build();

        // Employee 3: Has cook role (wrong role), available
        $employee3 = EmployeeBuilder::create()
            ->withName('Wendy')
            ->withRole($cookRole)
            ->alwaysAvailable()
            ->build();

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([]));

        $employees = collect([$employee1, $employee2, $employee3]);
        $finder = new CandidateFinder($employees, $schedule);

        // Create a server shift on Monday 9am-5pm
        $shift = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00') // Monday
            ->withDuration(8)
            ->forRole($serverRole)
            ->build();

        // ACT
        $candidates = $finder->getCandidatesFor($shift);

        // ASSERT
        $this->assertCount(1, $candidates, 'Should only return employee1 (available + has server role)');
        $this->assertTrue($candidates->contains($employee1), 'Should include employee1');
        $this->assertFalse($candidates->contains($employee2), 'Should not include employee2 (unavailable)');
        $this->assertFalse($candidates->contains($employee3), 'Should not include employee3 (wrong role)');
    }

    #[Test]
    public function gets_all_candidates_when_multiple_employees_match()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();

        // All three employees have server role and are available
        $employee1 = EmployeeBuilder::create()
            ->withName('Xander')
            ->withRole($serverRole)
            ->alwaysAvailable()
            ->build();

        $employee2 = EmployeeBuilder::create()
            ->withName('Yara')
            ->withRole($serverRole)
            ->alwaysAvailable()
            ->build();

        $employee3 = EmployeeBuilder::create()
            ->withName('Zoe')
            ->withRole($serverRole)
            ->alwaysAvailable()
            ->build();

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([]));

        $employees = collect([$employee1, $employee2, $employee3]);
        $finder = new CandidateFinder($employees, $schedule);

        $shift = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(8)
            ->forRole($serverRole)
            ->build();

        // ACT
        $candidates = $finder->getCandidatesFor($shift);

        // ASSERT
        $this->assertCount(3, $candidates, 'Should return all three employees');
        $this->assertTrue($candidates->contains($employee1));
        $this->assertTrue($candidates->contains($employee2));
        $this->assertTrue($candidates->contains($employee3));
    }

    #[Test]
    public function gets_schedulable_candidates_excluding_already_scheduled_employees()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();

        // Three employees, all available and have server role
        $employee1 = EmployeeBuilder::create()
            ->withName('Aaron')
            ->withWeeklyHours(35)
            ->withRole($serverRole)
            ->alwaysAvailable()
            ->build();

        $employee2 = EmployeeBuilder::create()
            ->withName('Bella')
            ->withWeeklyHours(30)
            ->withRole($serverRole)
            ->alwaysAvailable()
            ->build();

        $employee3 = EmployeeBuilder::create()
            ->withName('Carlos')
            ->withWeeklyHours(25)
            ->withRole($serverRole)
            ->alwaysAvailable()
            ->build();

        // Employee1 is already scheduled for an overlapping shift (9am-5pm)
        $existingShift = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(8)
            ->forRole($serverRole)
            ->build();
        $existingShift->assignTo($employee1);

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([$existingShift]));

        $employees = collect([$employee1, $employee2, $employee3]);
        $finder = new CandidateFinder($employees, $schedule);

        // New shift that overlaps with the existing shift (10am-2pm)
        $newShift = ShiftBuilder::create()
            ->from('2024-01-01 10:00:00')
            ->withDuration(4)
            ->forRole($serverRole)
            ->build();

        // ACT
        $schedulableCandidates = $finder->getSchedulableCandidatesFor($newShift);

        // ASSERT
        $this->assertCount(2, $schedulableCandidates, 'Should exclude employee1 who is already scheduled');
        $this->assertFalse($schedulableCandidates->contains($employee1), 'Should not include employee1 (already scheduled)');
        $this->assertTrue($schedulableCandidates->contains($employee2), 'Should include employee2');
        $this->assertTrue($schedulableCandidates->contains($employee3), 'Should include employee3');
    }

    #[Test]
    public function gets_all_candidates_as_schedulable_when_none_are_scheduled()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();

        $employee1 = EmployeeBuilder::create()
            ->withName('Diana')
            ->withRole($serverRole)
            ->alwaysAvailable()
            ->build();

        $employee2 = EmployeeBuilder::create()
            ->withName('Ethan')
            ->withRole($serverRole)
            ->alwaysAvailable()
            ->build();

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([])); // No existing shifts

        $employees = collect([$employee1, $employee2]);
        $finder = new CandidateFinder($employees, $schedule);

        $shift = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(8)
            ->forRole($serverRole)
            ->build();

        // ACT
        $schedulableCandidates = $finder->getSchedulableCandidatesFor($shift);

        // ASSERT
        $this->assertCount(2, $schedulableCandidates, 'Should return all available candidates when none are scheduled');
        $this->assertTrue($schedulableCandidates->contains($employee1));
        $this->assertTrue($schedulableCandidates->contains($employee2));
    }

    #[Test]
    public function employee_scheduled_for_non_overlapping_shift_is_schedulable()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();

        $employee = EmployeeBuilder::create()
            ->withName('Fiona')
            ->withRole($serverRole)
            ->alwaysAvailable()
            ->build();

        // Employee is scheduled for 9am-5pm shift
        $existingShift = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(8)
            ->forRole($serverRole)
            ->build();
        $existingShift->assignTo($employee);

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([$existingShift]));

        $finder = new CandidateFinder(collect([$employee]), $schedule);

        // New shift is 6pm-10pm (doesn't overlap)
        $newShift = ShiftBuilder::create()
            ->from('2024-01-01 18:00:00')
            ->withDuration(4)
            ->forRole($serverRole)
            ->build();

        // ACT
        $schedulableCandidates = $finder->getSchedulableCandidatesFor($newShift);

        // ASSERT
        $this->assertCount(1, $schedulableCandidates, 'Employee should be schedulable for non-overlapping shift');
        $this->assertTrue($schedulableCandidates->contains($employee));
    }

    #[Test]
    public function returns_empty_collection_when_no_candidates_available()
    {
        // ARRANGE
        $serverRole = RoleBuilder::create()->withTitle('server')->build();
        $cookRole = RoleBuilder::create()->withTitle('cook')->build();

        // Employee only has cook role
        $employee = EmployeeBuilder::create()
            ->withName('George')
            ->withRole($cookRole)
            ->alwaysAvailable()
            ->build();

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([]));

        $finder = new CandidateFinder(collect([$employee]), $schedule);

        // Shift requires server role
        $shift = ShiftBuilder::create()
            ->from('2024-01-01 09:00:00')
            ->withDuration(8)
            ->forRole($serverRole)
            ->build();

        // ACT
        $candidates = $finder->getCandidatesFor($shift);

        // ASSERT
        $this->assertCount(0, $candidates, 'Should return empty collection when no one has required role');
        $this->assertTrue($candidates->isEmpty());
    }
}
