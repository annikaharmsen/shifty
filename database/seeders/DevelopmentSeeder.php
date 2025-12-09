<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Establishment;
use App\Models\Employee;
use App\Models\Role;
use App\Models\ScheduleTemplate;
use App\Models\TemplateShift;
use App\Models\AvailabilityRule;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DevelopmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create company
        $company = Company::create([
            'name' => 'Demo Restaurant Group',
        ]);

        // Create establishment
        $establishment = Establishment::create([
            'company_id' => $company->id,
            'name' => 'Downtown Bistro',
            'address' => '123 Main Street',
        ]);

        // Create roles
        $server = Role::create(['company_id' => $company->id, 'title' => 'Server']);
        $cook = Role::create(['company_id' => $company->id, 'title' => 'Cook']);
        $bartender = Role::create(['company_id' => $company->id, 'title' => 'Bartender']);
        $host = Role::create(['company_id' => $company->id, 'title' => 'Host']);

        // Create employees
        $employees = [
            ['name' => 'Alice Smith', 'pin' => '1111', 'weekly_hours' => 40.0, 'roles' => [$server, $bartender]],
            ['name' => 'Bob Johnson', 'pin' => '2222', 'weekly_hours' => 35.0, 'roles' => [$cook]],
            ['name' => 'Carol White', 'pin' => '3333', 'weekly_hours' => 30.0, 'roles' => [$server]],
            ['name' => 'David Brown', 'pin' => '4444', 'weekly_hours' => 25.0, 'roles' => [$host, $server]],
        ];

        foreach ($employees as $empData) {
            $employee = Employee::create([
                'name' => $empData['name'],
                'pin' => $empData['pin'],
                'weekly_hours' => $empData['weekly_hours'],
            ]);

            $employee->establishments()->attach($establishment);

            foreach ($empData['roles'] as $role) {
                $employee->roles()->attach($role);
            }

            // Add availability rule (available weekdays 9am-5pm)
            AvailabilityRule::create([
                'employee_id' => $employee->id,
                'is_available' => true,
                'start_datetime' => Carbon::parse('2024-12-09 09:00:00'),
                'duration' => 28800, // 8 hours in seconds
                'frequency' => 86400, // 1 day in seconds
                'termination_datetime' => Carbon::parse('2025-12-31 17:00:00'),
            ]);
        }

        // Create schedule template
        $template = ScheduleTemplate::create([
            'establishment_id' => $establishment->id,
            'name' => 'Standard Week',
        ]);

        // Add template shifts (Monday-Friday lunch and dinner)
        for ($day = 1; $day <= 5; $day++) {
            // Lunch shift
            TemplateShift::create([
                'schedule_template_id' => $template->id,
                'role_id' => $server->id,
                'day_of_week' => $day,
                'start_time' => '11:00:00',
                'duration' => 14400, // 4 hours in seconds
                'is_on_call' => false,
            ]);

            // Dinner shift
            TemplateShift::create([
                'schedule_template_id' => $template->id,
                'role_id' => $server->id,
                'day_of_week' => $day,
                'start_time' => '17:00:00',
                'duration' => 18000, // 5 hours in seconds
                'is_on_call' => false,
            ]);

            // Cook shift (full day)
            TemplateShift::create([
                'schedule_template_id' => $template->id,
                'role_id' => $cook->id,
                'day_of_week' => $day,
                'start_time' => '10:00:00',
                'duration' => 28800, // 8 hours in seconds
                'is_on_call' => false,
            ]);
        }

        $this->command->info('Development data seeded successfully!');
    }
}
