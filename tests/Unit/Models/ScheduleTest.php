<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Establishment;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_schedule(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown',
        ]);

        $schedule = Schedule::create([
            'establishment_id' => $establishment->id,
            'week_start_date' => Carbon::parse('2024-12-09'),
        ]);

        $this->assertDatabaseHas('schedules', [
            'establishment_id' => $establishment->id,
        ]);
        $this->assertEquals('2024-12-09', $schedule->week_start_date->format('Y-m-d'));
    }

    public function test_belongs_to_establishment(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown',
        ]);
        $schedule = Schedule::create([
            'establishment_id' => $establishment->id,
            'week_start_date' => Carbon::parse('2024-12-09'),
        ]);

        $this->assertInstanceOf(Establishment::class, $schedule->establishment);
        $this->assertEquals($establishment->id, $schedule->establishment->id);
    }

    public function test_unique_week_per_establishment(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown',
        ]);

        Schedule::create([
            'establishment_id' => $establishment->id,
            'week_start_date' => Carbon::parse('2024-12-09'),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Schedule::create([
            'establishment_id' => $establishment->id,
            'week_start_date' => Carbon::parse('2024-12-09'),
        ]);
    }

    public function test_soft_deleting_schedule_soft_deletes_shifts(): void
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

        $schedule->delete();

        $this->assertSoftDeleted('schedules', ['id' => $schedule->id]);
        $this->assertSoftDeleted('shifts', ['id' => $shift->id]);
    }

    public function test_restoring_schedule_restores_shifts(): void
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

        $schedule->delete();
        $schedule->restore();

        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'deleted_at' => null,
        ]);
    }
}
