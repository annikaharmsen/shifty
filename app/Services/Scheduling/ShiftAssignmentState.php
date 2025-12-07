<?php

namespace App\Services\Scheduling;

use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Support\Collection;

class ShiftAssignmentState
{
    public Shift $shift;
    public Collection $candidatesRemaining;
    public ?Employee $currentAssignment;

    public function __construct(Shift &$shift, Collection $candidates)
    {
        $this->shift = $shift;
        $this->candidatesRemaining = $candidates->ensure(Employee::class);
        $this->currentAssignment = null;
    }

    public function hasMoreCandidates(): bool
    {
        return $this->candidatesRemaining->isNotEmpty();
    }

    public function getNextCandidate(): Employee
    {
        return $this->candidatesRemaining->shift();
    }
}
