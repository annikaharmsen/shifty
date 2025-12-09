<?php

namespace Tests\Unit\Models;

use App\Models\Employee;
use App\Models\Establishment;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_employee(): void
    {
        $employee = Employee::create([
            'name' => 'John Doe',
            'pin' => '1234',
            'weekly_hours' => 40.0,
        ]);

        $this->assertDatabaseHas('employees', [
            'name' => 'John Doe',
            'pin' => '1234',
            'weekly_hours' => 40.0,
        ]);
    }

    public function test_can_attach_to_establishments(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown',
        ]);
        $employee = Employee::create([
            'name' => 'John Doe',
            'pin' => '1234',
            'weekly_hours' => 40.0,
        ]);

        $employee->establishments()->attach($establishment);

        $this->assertDatabaseHas('employee_establishment', [
            'employee_id' => $employee->id,
            'establishment_id' => $establishment->id,
        ]);
    }

    public function test_can_soft_delete_employee(): void
    {
        $employee = Employee::create([
            'name' => 'John Doe',
            'pin' => '1234',
            'weekly_hours' => 40.0,
        ]);

        $employee->delete();

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_get_weekly_hours(): void
    {
        $employee = Employee::create([
            'name' => 'John Doe',
            'pin' => '1234',
            'weekly_hours' => 25.5,
        ]);

        $this->assertEquals(25.5, $employee->getWeeklyHours());
    }
}
