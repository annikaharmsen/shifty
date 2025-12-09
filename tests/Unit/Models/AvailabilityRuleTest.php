<?php

namespace Tests\Unit\Models;

use App\Models\AvailabilityRule;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_availability_rule(): void
    {
        $employee = Employee::create([
            'name' => 'John Doe',
            'pin' => '1234',
            'weekly_hours' => 40.0,
        ]);

        $rule = AvailabilityRule::create([
            'employee_id' => $employee->id,
            'is_available' => true,
            'start_datetime' => Carbon::parse('2024-12-09 09:00:00'),
            'duration' => 28800, // 8 hours
            'frequency' => null,
            'termination_datetime' => null,
        ]);

        $this->assertDatabaseHas('availability_rules', [
            'employee_id' => $employee->id,
            'is_available' => true,
            'duration' => 28800,
        ]);
    }

    public function test_belongs_to_employee(): void
    {
        $employee = Employee::create([
            'name' => 'John Doe',
            'pin' => '1234',
            'weekly_hours' => 40.0,
        ]);
        $rule = AvailabilityRule::create([
            'employee_id' => $employee->id,
            'is_available' => true,
            'start_datetime' => Carbon::parse('2024-12-09 09:00:00'),
            'duration' => 28800,
        ]);

        $this->assertInstanceOf(Employee::class, $rule->employee);
        $this->assertEquals($employee->id, $rule->employee->id);
    }

    public function test_timeblock_attribute_for_one_time_rule(): void
    {
        $employee = Employee::create([
            'name' => 'John Doe',
            'pin' => '1234',
            'weekly_hours' => 40.0,
        ]);
        $rule = AvailabilityRule::create([
            'employee_id' => $employee->id,
            'is_available' => true,
            'start_datetime' => Carbon::parse('2024-12-09 09:00:00'),
            'duration' => 28800,
            'frequency' => null,
        ]);

        $this->assertEquals(8.0, $rule->timeblock->getDurationHours());
        $this->assertInstanceOf(\App\ValueObjects\Timeblock::class, $rule->timeblock);
    }

    public function test_timeblock_attribute_for_recurring_rule(): void
    {
        $employee = Employee::create([
            'name' => 'John Doe',
            'pin' => '1234',
            'weekly_hours' => 40.0,
        ]);
        $rule = AvailabilityRule::create([
            'employee_id' => $employee->id,
            'is_available' => false,
            'start_datetime' => Carbon::parse('2024-12-09 09:00:00'),
            'duration' => 28800,
            'frequency' => 604800, // 1 week in seconds
            'termination_datetime' => Carbon::parse('2024-12-30 17:00:00'),
        ]);

        $this->assertInstanceOf(\App\ValueObjects\RecurringTimeblock::class, $rule->timeblock);
    }

    public function test_can_soft_delete_availability_rule(): void
    {
        $employee = Employee::create([
            'name' => 'John Doe',
            'pin' => '1234',
            'weekly_hours' => 40.0,
        ]);
        $rule = AvailabilityRule::create([
            'employee_id' => $employee->id,
            'is_available' => true,
            'start_datetime' => Carbon::parse('2024-12-09 09:00:00'),
            'duration' => 28800,
        ]);

        $rule->delete();

        $this->assertSoftDeleted('availability_rules', ['id' => $rule->id]);
    }
}
