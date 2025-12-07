<?php

namespace App\Services\Scheduling;

use App\Models\Employee;
use App\Models\Schedule;
use App\Models\Shift;
use App\ValueObjects\Timeblock;
use Illuminate\Support\Collection;

class ShiftPrioritizer
{
    private Collection $employees;
    private Schedule $schedule;
    private CandidateFinder $candidateFinder;

    public function __construct(Collection $employees, Schedule $schedule)
    {
        $employees->ensure(Employee::class);

        $this->employees = $employees;
        $this->schedule = $schedule;
        $this->candidateFinder = new CandidateFinder($employees, $schedule);
    }

    public function sortDesc(Collection &$shifts): Collection
    {
        $shifts->ensure(Shift::class)->sort(fn (Shift $shift1, Shift $shift2): int => $this->getPriority($shift1) < $this->getPriority($shift2) ? -1 : 1);

        return $shifts;
    }

    public function getShiftHoursOverlapping(Timeblock $timeblock): float
    {
        /**
         * @var Collection $this->schedule->shifts
         */
        return $this->schedule->shifts->reduce(
            function (float $prev, Shift $shift) use ($timeblock): float {
                return $prev + $shift->timeblock->getHoursOverlappingWith($timeblock);

            },
            0
        );
    }

    public function getImportance(Shift $shift): float
    {
        $shiftLength = $shift->timeblock->getDurationHours();
        $necessityFactor = $shift->is_on_call ? .5 : 1;

        return $shiftLength * $necessityFactor;
    }

    public function getPriority(Shift $shift): float
    {
        $scarcity = $this->getShiftHoursOverlapping($shift->timeblock);
        $availability = \count($this->candidateFinder->getCandidatesFor($shift)) * $shift->timeblock->getDurationHours();
        $importance = $this->getImportance($shift);

        if ($availability == 0) {
            $availability = 0.01;
        }

        return $scarcity / $availability * $importance;
    }
}
