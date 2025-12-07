<?php

namespace App\Services\Scheduling;

use App\Models\Employee;
use App\Models\Schedule;
use App\Models\Shift;
use App\ValueObjects\Timeblock;
use Illuminate\Support\Collection;

class CandidateFinder
{
    private Collection $employees;
    private Schedule $schedule;

    public function __construct(Collection $employees, Schedule $schedule)
    {
        $this->employees = $employees->ensure(Employee::class);
        $this->schedule = $schedule;
    }

    public function employeeIsScheduledDuring(Employee $employee, Timeblock $timeblock): bool
    {
        $shifts = $this->schedule->getShiftsAssignedTo($employee);

        /** @var Shift $shift */
        foreach ($shifts as $shift) {
            if ($shift->timeblock->overlaps($timeblock)) {
                return true;
            }
        }

        return false;
    }

    public function employeeIsCandidateFor(Employee $employee, Shift $shift): bool
    {
        return $employee->isFullyAvailable($shift->timeblock) && $employee->hasRole($shift->role);
    }

    public function candidateIsSchedulableFor(Employee $candidate, Shift $shift): bool
    {
        $hasOverlappingAssignents = $this->employeeIsScheduledDuring($candidate, $shift->timeblock);
        $schedulableHours = $this->schedule->getSchedulableHours($candidate);
        $halfShiftHours = $shift->timeblock->getDurationHours() / 2;

        return !$hasOverlappingAssignents && $schedulableHours >= $halfShiftHours;
    }

    // get all employees that are available and can work the shift's role
    public function getCandidatesFor(Shift $shift): Collection
    {
        return $this->employees->filter(
            fn (Employee $employee): bool => $this->employeeIsCandidateFor($employee, $shift)
        );
    }

    /**
     * This function filters candidates for a shift based on their availability and scheduled hours.
     *
     * @param Shift shift The `getSchedulableCandidatesFor` function takes a `Shift` object as a
     * parameter. The function then filters the candidates for the shift based on certain conditions.
     *
     * @return Collection The `getSchedulableCandidatesFor` function returns a Collection of Employee
     * objects that are available and have no overlapping assignments during the shift's time
     * block and have enough schedulable hours for at least half of the shift's duration.
     */
    public function getSchedulableCandidatesFor(Shift $shift): Collection
    {
        return $this->getCandidatesFor($shift)->filter(
            fn (Employee $candidate) => $this->candidateIsSchedulableFor($candidate, $shift)
        );
    }
}
