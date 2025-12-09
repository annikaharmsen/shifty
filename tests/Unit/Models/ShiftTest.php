<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Establishment;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Builders\ShiftBuilder;
use Tests\TestCase;

class ShiftTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_shift(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown',
        ]);
        $role = Role::create(['company_id' => $company->id, 'title' => 'Server']);
        $schedule = Schedule::create([
            'establishment_id' => $establishment->id,
            'week_start_date' => Carbon::parse('2024-12-09'),
        ]);

        $shift = Shift::create([
            'schedule_id' => $schedule->id,
            'role_id' => $role->id,
            'start_datetime' => Carbon::parse('2024-12-09 09:00:00'),
            'duration' => 28800, // 8 hours in seconds
            'is_on_call' => false,
        ]);

        $this->assertDatabaseHas('shifts', [
            'schedule_id' => $schedule->id,
            'role_id' => $role->id,
            'duration' => 28800,
        ]);
    }

    public function test_timeblock_attribute_reconstructs_from_database(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown',
        ]);
        $role = Role::create(['company_id' => $company->id, 'title' => 'Server']);
        $schedule = Schedule::create([
            'establishment_id' => $establishment->id,
            'week_start_date' => Carbon::parse('2024-12-09'),
        ]);

        $shift = Shift::create([
            'schedule_id' => $schedule->id,
            'role_id' => $role->id,
            'start_datetime' => Carbon::parse('2024-12-09 09:00:00'),
            'duration' => 28800,
            'is_on_call' => false,
        ]);

        $this->assertEquals(8.0, $shift->timeblock->getDurationHours());
        $this->assertEquals('2024-12-09 09:00:00', $shift->timeblock->getStart()->format('Y-m-d H:i:s'));
    }

    public function test_can_assign_employee_to_shift(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown',
        ]);
        $role = Role::create(['company_id' => $company->id, 'title' => 'Server']);
        $schedule = Schedule::create([
            'establishment_id' => $establishment->id,
            'week_start_date' => Carbon::parse('2024-12-09'),
        ]);
        $employee = Employee::create([
            'name' => 'John Doe',
            'pin' => '1234',
            'weekly_hours' => 40.0,
        ]);

        $shift = Shift::create([
            'schedule_id' => $schedule->id,
            'role_id' => $role->id,
            'assignee_id' => $employee->id,
            'start_datetime' => Carbon::parse('2024-12-09 09:00:00'),
            'duration' => 28800,
            'is_on_call' => false,
        ]);

        $this->assertTrue($shift->isAssigned());
        $this->assertTrue($shift->isAssignedTo($employee));
    }

    public function test_can_soft_delete_shift(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown',
        ]);
        $role = Role::create(['company_id' => $company->id, 'title' => 'Server']);
        $schedule = Schedule::create([
            'establishment_id' => $establishment->id,
            'week_start_date' => Carbon::parse('2024-12-09'),
        ]);
        $shift = Shift::create([
            'schedule_id' => $schedule->id,
            'role_id' => $role->id,
            'start_datetime' => Carbon::parse('2024-12-09 09:00:00'),
            'duration' => 28800,
            'is_on_call' => false,
        ]);

        $shift->delete();

        $this->assertSoftDeleted('shifts', ['id' => $shift->id]);
    }

    public function test_cannot_hard_delete_role_with_shifts(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown',
        ]);
        $role = Role::create(['company_id' => $company->id, 'title' => 'Server']);
        $schedule = Schedule::create([
            'establishment_id' => $establishment->id,
            'week_start_date' => Carbon::parse('2024-12-09'),
        ]);
        Shift::create([
            'schedule_id' => $schedule->id,
            'role_id' => $role->id,
            'start_datetime' => Carbon::parse('2024-12-09 09:00:00'),
            'duration' => 28800,
            'is_on_call' => false,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $role->forceDelete();
    }

    public function test_can_create_shift_with_volume_rating(): void
    {
        $shift = ShiftBuilder::create()
            ->withVolumeRating(7)
            ->build();

        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'volume_rating' => 7,
        ]);
    }

    public function test_can_create_shift_without_volume_rating(): void
    {
        $shift = ShiftBuilder::create()
            ->build();

        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'volume_rating' => null,
        ]);
    }

    public function test_volume_rating_persists_correctly_to_database(): void
    {
        // Test minimum value (1)
        $shift1 = ShiftBuilder::create()
            ->withVolumeRating(1)
            ->from('2024-01-01 09:00:00')
            ->build();

        // Test maximum value (10)
        $shift2 = ShiftBuilder::create()
            ->withVolumeRating(10)
            ->from('2024-01-02 09:00:00')
            ->build();

        // Test middle value (5)
        $shift3 = ShiftBuilder::create()
            ->withVolumeRating(5)
            ->from('2024-01-03 09:00:00')
            ->build();

        // Refresh from database to ensure values are persisted
        $shift1->refresh();
        $shift2->refresh();
        $shift3->refresh();

        $this->assertEquals(1, $shift1->volume_rating);
        $this->assertEquals(10, $shift2->volume_rating);
        $this->assertEquals(5, $shift3->volume_rating);
    }
}
