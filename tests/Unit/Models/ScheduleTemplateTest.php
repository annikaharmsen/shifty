<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Establishment;
use App\Models\Role;
use App\Models\ScheduleTemplate;
use App\Models\TemplateShift;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_schedule_template(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown',
        ]);

        $template = ScheduleTemplate::create([
            'establishment_id' => $establishment->id,
            'name' => 'Standard Week',
        ]);

        $this->assertDatabaseHas('schedule_templates', [
            'establishment_id' => $establishment->id,
            'name' => 'Standard Week',
        ]);
    }

    public function test_belongs_to_establishment(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown',
        ]);
        $template = ScheduleTemplate::create([
            'establishment_id' => $establishment->id,
            'name' => 'Standard Week',
        ]);

        $this->assertInstanceOf(Establishment::class, $template->establishment);
        $this->assertEquals($establishment->id, $template->establishment->id);
    }

    public function test_can_add_template_shifts(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown',
        ]);
        $role = Role::create(['company_id' => $company->id, 'title' => 'Server']);
        $template = ScheduleTemplate::create([
            'establishment_id' => $establishment->id,
            'name' => 'Standard Week',
        ]);

        $templateShift = TemplateShift::create([
            'schedule_template_id' => $template->id,
            'role_id' => $role->id,
            'day_of_week' => 1, // Monday
            'start_time' => '09:00:00',
            'duration' => 28800, // 8 hours
            'is_on_call' => false,
        ]);

        $this->assertDatabaseHas('template_shifts', [
            'schedule_template_id' => $template->id,
            'role_id' => $role->id,
            'day_of_week' => 1,
        ]);
    }

    public function test_soft_deleting_template_soft_deletes_template_shifts(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown',
        ]);
        $role = Role::create(['company_id' => $company->id, 'title' => 'Server']);
        $template = ScheduleTemplate::create([
            'establishment_id' => $establishment->id,
            'name' => 'Standard Week',
        ]);
        $templateShift = TemplateShift::create([
            'schedule_template_id' => $template->id,
            'role_id' => $role->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'duration' => 28800,
            'is_on_call' => false,
        ]);

        $template->delete();

        $this->assertSoftDeleted('schedule_templates', ['id' => $template->id]);
        $this->assertSoftDeleted('template_shifts', ['id' => $templateShift->id]);
    }

    public function test_get_instantiated_shifts(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown',
        ]);
        $role = Role::create(['company_id' => $company->id, 'title' => 'Server']);
        $template = ScheduleTemplate::create([
            'establishment_id' => $establishment->id,
            'name' => 'Standard Week',
        ]);
        TemplateShift::create([
            'schedule_template_id' => $template->id,
            'role_id' => $role->id,
            'day_of_week' => 1, // Monday
            'start_time' => '09:00:00',
            'duration' => 28800,
            'is_on_call' => false,
        ]);

        $weekStart = CarbonImmutable::parse('2024-12-09'); // Monday
        $shifts = $template->getInstantiatedShifts($weekStart);

        $this->assertCount(1, $shifts);
        $this->assertEquals('2024-12-09 09:00:00', $shifts->first()->start_datetime->format('Y-m-d H:i:s'));
        $this->assertEquals(28800, $shifts->first()->duration);
    }

    public function test_instantiated_shifts_copy_volume_rating_from_template(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown',
        ]);
        $role = Role::create(['company_id' => $company->id, 'title' => 'Server']);
        $template = ScheduleTemplate::create([
            'establishment_id' => $establishment->id,
            'name' => 'Standard Week',
        ]);
        TemplateShift::create([
            'schedule_template_id' => $template->id,
            'role_id' => $role->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'duration' => 28800,
            'is_on_call' => false,
            'volume_rating' => 9,
        ]);

        $weekStart = CarbonImmutable::parse('2024-12-09'); // Monday
        $shifts = $template->getInstantiatedShifts($weekStart);

        $this->assertCount(1, $shifts);
        $this->assertEquals(9, $shifts->first()->volume_rating);
    }

    public function test_instantiated_shifts_copy_null_volume_rating_from_template(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown',
        ]);
        $role = Role::create(['company_id' => $company->id, 'title' => 'Server']);
        $template = ScheduleTemplate::create([
            'establishment_id' => $establishment->id,
            'name' => 'Standard Week',
        ]);
        TemplateShift::create([
            'schedule_template_id' => $template->id,
            'role_id' => $role->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'duration' => 28800,
            'is_on_call' => false,
            'volume_rating' => null,
        ]);

        $weekStart = CarbonImmutable::parse('2024-12-09'); // Monday
        $shifts = $template->getInstantiatedShifts($weekStart);

        $this->assertCount(1, $shifts);
        $this->assertNull($shifts->first()->volume_rating);
    }
}
