<?php

namespace App\Services\Scheduling;

use App\Models\Employee;
use App\Models\Schedule;
use Illuminate\Support\Collection;

class EmployeeSorter
{
    private Schedule $schedule;

    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;
    }

    public function sort(Collection $employees)
    {
        $employees->ensure(Employee::class);

        return $employees->sort(
            fn (Employee $employee1, Employee $employee2) =>
            $this->schedule->getSchedulableHours($employee2) - $this->schedule->getSchedulableHours($employee1)
        );
    }
}
