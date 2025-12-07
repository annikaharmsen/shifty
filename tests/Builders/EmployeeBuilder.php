<?php

namespace Tests\Builders;

use App\Models\AvailabilityRule;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Support\Collection;

class EmployeeBuilder
{
    private static int $idCounter = 1;

    private string $name = 'Unnamed Employee';
    private float $weeklyHours = 30;
    private array $availabilityRules = [];
    private array $roles = [];

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

    public function build(): Employee
    {
        $employee = new Employee();

        $employee->id = self::$idCounter++;
        $employee->name = $this->name;
        $employee->weekly_hours = $this->weeklyHours;

        $employee->setRelation('roles', collect($this->roles));
        $employee->setRelation('availabilityRules', collect($this->availabilityRules));

        return $employee;
    }
}
