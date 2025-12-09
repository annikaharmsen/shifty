<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_role(): void
    {
        $company = Company::create(['name' => 'Test Company']);

        $role = Role::create([
            'company_id' => $company->id,
            'title' => 'Server',
        ]);

        $this->assertDatabaseHas('roles', [
            'company_id' => $company->id,
            'title' => 'Server',
        ]);
    }

    public function test_belongs_to_company(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $role = Role::create([
            'company_id' => $company->id,
            'title' => 'Server',
        ]);

        $this->assertInstanceOf(Company::class, $role->company);
        $this->assertEquals($company->id, $role->company->id);
    }

    public function test_unique_title_per_company(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        Role::create(['company_id' => $company->id, 'title' => 'Server']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Role::create(['company_id' => $company->id, 'title' => 'Server']);
    }

    public function test_can_attach_employees_with_proficiency(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $role = Role::create(['company_id' => $company->id, 'title' => 'Server']);
        $employee = Employee::create([
            'name' => 'John Doe',
            'pin' => '1234',
            'weekly_hours' => 40.0,
        ]);

        $role->employees()->attach($employee, ['proficiency_rating' => 8]);

        $this->assertDatabaseHas('employee_role', [
            'employee_id' => $employee->id,
            'role_id' => $role->id,
            'proficiency_rating' => 8,
        ]);
    }

    public function test_can_soft_delete_role(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $role = Role::create(['company_id' => $company->id, 'title' => 'Server']);

        $role->delete();

        $this->assertSoftDeleted('roles', ['id' => $role->id]);
    }
}
