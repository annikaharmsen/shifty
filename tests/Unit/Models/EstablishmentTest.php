<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Establishment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstablishmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_establishment(): void
    {
        $company = Company::create(['name' => 'Test Company']);

        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown Location',
            'address' => '123 Main St',
        ]);

        $this->assertDatabaseHas('establishments', [
            'name' => 'Downtown Location',
            'company_id' => $company->id,
        ]);
    }

    public function test_belongs_to_company(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown Location',
        ]);

        $this->assertInstanceOf(Company::class, $establishment->company);
        $this->assertEquals($company->id, $establishment->company->id);
    }

    public function test_can_soft_delete_establishment(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown Location',
        ]);

        $establishment->delete();

        $this->assertSoftDeleted('establishments', ['id' => $establishment->id]);
    }

    public function test_cascade_deletes_when_company_deleted(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown Location',
        ]);

        $company->forceDelete();

        $this->assertDatabaseMissing('establishments', ['id' => $establishment->id]);
    }
}
