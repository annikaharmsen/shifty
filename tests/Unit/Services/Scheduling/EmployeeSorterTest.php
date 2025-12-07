<?php

namespace Tests\Unit\Services\Scheduling;

use App\Models\Schedule;
use App\Services\Scheduling\EmployeeSorter;
use PHPUnit\Framework\Attributes\Test;
use Tests\Builders\EmployeeBuilder;
use Tests\Builders\ShiftBuilder;
use Tests\TestCase;

/**
 * Tests that EmployeeSorter sorts employees based on unscheduled weekly hours
 *
 * eg --------------------------------------------------
 * part time employee: Sam
 * - works 30h/week
 * - is scheduled for a 5 hour shift and a 7 hour shift this week
 * -> schedulable hours: 18
 *
 * full time employee: Karen
 * - works 40h/week
 * - is scheduled for three 8 hour shifts this week
 * -> schedulable hours: 16
 *
 * Sam should be sorted before Karen because he still has more hours to be schedule for the week
 *
 * -----------------------------------------------------
 *
 * class should
 *
 * - correctly calculate an employee's schedulable hours
 * - sort a collection of employees by schedulable hours descending
 */
class EmployeeSorterTest extends TestCase
{
    #[Test]
    public function calculates_schedulable_hours_correctly()
    {
        // ARRANGE - Employee with 30 weekly hours
        $employee = EmployeeBuilder::create()
            ->withName('Hannah')
            ->withWeeklyHours(30)
            ->build();

        // Create shifts totaling 12 hours and assign to employee
        $shift1 = ShiftBuilder::create()->withDuration(5)->build();
        $shift1->assignTo($employee);

        $shift2 = ShiftBuilder::create()->withDuration(7)->build();
        $shift2->assignTo($employee);

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([$shift1, $shift2]));

        $sorter = new EmployeeSorter($schedule);

        // ACT
        $schedulableHours = $schedule->getSchedulableHours($employee);

        // ASSERT
        $this->assertEquals(18, $schedulableHours, 'Schedulable hours should be 30 (weekly) - 12 (assigned) = 18');
    }

    #[Test]
    public function sorts_employees_by_schedulable_hours_descending()
    {
        // ARRANGE
        // Sam: 30 weekly, 12 assigned = 18 schedulable
        $sam = EmployeeBuilder::create()->withName('Sam')->withWeeklyHours(30)->build();
        $samShift1 = ShiftBuilder::create()->withDuration(5)->build();
        $samShift2 = ShiftBuilder::create()->withDuration(7)->build();
        $samShift1->assignTo($sam);
        $samShift2->assignTo($sam);

        // Karen: 40 weekly, 24 assigned = 16 schedulable
        $karen = EmployeeBuilder::create()->withName('Karen')->withWeeklyHours(40)->build();
        $karenShift1 = ShiftBuilder::create()->withDuration(8)->build();
        $karenShift2 = ShiftBuilder::create()->withDuration(8)->build();
        $karenShift3 = ShiftBuilder::create()->withDuration(8)->build();
        $karenShift1->assignTo($karen);
        $karenShift2->assignTo($karen);
        $karenShift3->assignTo($karen);

        // Alex: 20 weekly, 0 assigned = 20 schedulable (should be first)
        $alex = EmployeeBuilder::create()->withName('Alex')->withWeeklyHours(20)->build();

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([
            $samShift1, $samShift2,
            $karenShift1, $karenShift2, $karenShift3
        ]));

        $employees = collect([$karen, $sam, $alex]); // Intentionally unsorted
        $sorter = new EmployeeSorter($schedule);

        // ACT
        $employees = $sorter->sort($employees);

        // ASSERT
        $this->assertSame($alex, $employees->values()->get(0), 'Alex (20 schedulable) should be first');
        $this->assertSame($sam, $employees->values()->get(1), 'Sam (18 schedulable) should be second');
        $this->assertSame($karen, $employees->values()->get(2), 'Karen (16 schedulable) should be third');
    }

    #[Test]
    public function handles_employee_with_no_assigned_shifts()
    {
        // ARRANGE
        $employee = EmployeeBuilder::create()->withName('Ian')->withWeeklyHours(40)->build();

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([])); // No shifts

        $sorter = new EmployeeSorter($schedule);

        // ACT
        $schedulableHours = $schedule->getSchedulableHours($employee);

        // ASSERT
        $this->assertEquals(40, $schedulableHours, 'All weekly hours should be schedulable');
    }

    #[Test]
    public function handles_employee_with_all_hours_assigned()
    {
        // ARRANGE
        $employee = EmployeeBuilder::create()->withName('Julia')->withWeeklyHours(40)->build();

        $shift1 = ShiftBuilder::create()->withDuration(20)->build();
        $shift2 = ShiftBuilder::create()->withDuration(20)->build();
        $shift1->assignTo($employee);
        $shift2->assignTo($employee);

        $schedule = new Schedule();
        $schedule->setRelation('shifts', collect([$shift1, $shift2]));

        $sorter = new EmployeeSorter($schedule);

        // ACT
        $schedulableHours = $schedule->getSchedulableHours($employee);

        // ASSERT
        $this->assertEquals(0, $schedulableHours, 'No hours should be schedulable');
    }
}
