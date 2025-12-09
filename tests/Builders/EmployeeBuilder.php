<?php

namespace Tests\Builders;

use App\Models\AvailabilityRule;
use App\Models\Employee;
use App\Models\Establishment;
use App\Models\Role;
use Illuminate\Support\Collection;

class EmployeeBuilder
{
    private static int $pinCounter = 1000;

    private string $name = 'Unnamed Employee';
    private float $weeklyHours = 30;
    private array $availabilityRules = [];
    private array $roles = [];
    private ?Establishment $establishment = null;

    public static function create(): self
    {
        return new self();
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function withWeeklyHours(int $hours): self
    {
        $this->weeklyHours = $hours;
        return $this;
    }

    public function withAvailabilityRules(array $rules): self
    {
        $this->availabilityRules = $rules;
        return $this;
    }

    public function withAvailabilityRule(AvailabilityRule $rule): self
    {
        $this->availabilityRules = [$rule];
        return $this;
    }

    public function alwaysAvailable(): self
    {
        $this->availabilityRules = [];
        return $this;
    }

    public function withRoles(Role ...$roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function withRole(Role $role): self
    {
        $this->roles = [$role];
        return $this;
    }

    public function atEstablishment(Establishment $establishment): self
    {
        $this->establishment = $establishment;
        return $this;
    }

    public function build(): Employee
    {
        // Create employee
        $employee = Employee::create([
            'name' => $this->name,
            'pin' => (string) self::$pinCounter++,
            'weekly_hours' => $this->weeklyHours,
        ]);

        // Attach roles (deduplicate by ID to avoid constraint violations)
        if (!empty($this->roles)) {
            $roleIds = collect($this->roles)->pluck('id')->unique()->all();
            $employee->roles()->attach($roleIds);
            $employee->setRelation('roles', collect($this->roles)->unique('id')->values());
        }

        // Attach to establishment if provided
        if ($this->establishment !== null) {
            $employee->establishments()->attach($this->establishment->id);
        }

        // Create availability rules
        foreach ($this->availabilityRules as $rule) {
            $rule->employee_id = $employee->id;
            $rule->save();
        }
        $employee->setRelation('availabilityRules', collect($this->availabilityRules));

        return $employee->fresh(['roles', 'availabilityRules']);
    }
}
