<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_company(): void
    {
        $company = Company::create([
            'name' => 'Test Restaurant Group',
        ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'Test Restaurant Group',
        ]);
        $this->assertInstanceOf(Company::class, $company);
    }

    public function test_can_soft_delete_company(): void
    {
        $company = Company::create(['name' => 'Test Company']);

        $company->delete();

        $this->assertSoftDeleted('companies', ['id' => $company->id]);
    }

    public function test_can_restore_soft_deleted_company(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $company->delete();

        $company->restore();

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'deleted_at' => null,
        ]);
    }
}
