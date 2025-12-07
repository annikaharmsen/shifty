<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Schedule;
use App\Models\Shift;
use App\Services\Scheduling\CandidateFinder;
use App\Services\Scheduling\EmployeeSorter;
use App\Services\Scheduling\ShiftAssignmentState;
use App\Services\Scheduling\ShiftPrioritizer;
use App\ValueObjects\Timeblock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AutoScheduleService
{
    public const ALL_SHIFTS = true;
    public const UNASSIGNED_SHIFTS = false;
    private Schedule $schedule;
    private Collection $employees;
    private ShiftPrioritizer $shiftPrioritizer;
    private EmployeeSorter $employeeSorter;
    private CandidateFinder $candidateFinder;

    // caching
    private array $employeeAssignments = [];
    private array $employeeHoursAssigned = [];
    private array $baseCandidatesCache = [];
    private array $shiftPriorityCache = [];

    public function __construct(Schedule $schedule, Collection $employees)
    {
        $this->schedule = $schedule;
        $this->employees = $employees->ensure(Employee::class);

        $this->shiftPrioritizer = new ShiftPrioritizer($employees, $schedule);
        $this->employeeSorter = new EmployeeSorter($schedule);
        $this->candidateFinder = new CandidateFinder($employees, $schedule);

        // cache shift priorities
        $this->shiftPriorityCache = [];
        foreach ($schedule->shifts as $shift) {
            $this->shiftPriorityCache[$shift->id] = $this->shiftPrioritizer->getPriority($shift);
        }

    }

    /**
     * The function "schedule" assigns shifts and returns the schedule.
     *
     * @return Schedule The `schedule` property is being returned.
     */
    public function schedule(): Schedule
    {
        $this->assignShifts(true);
        return $this->schedule;
    }

    /**
         * The function "scheduleUnassigned" assigns unassigned shifts and returns the schedule.
         *
         * @return Schedule The `schedule` property is being returned.
         */

    public function scheduleUnassigned(): Schedule
    {
        $this->assignShifts(false);
        return $this->schedule;
    }

    private function assignShift(ShiftAssignmentState &$shift_state, Employee $candidate): void
    {
        $this->unassignShift($shift_state->shift);

        $shift_state->shift->assignTo($candidate);
        $shift_state->currentAssignment = $candidate;

        // Update assignment cache
        $this->employeeAssignments[$candidate->id]->push($shift_state->shift);
        $this->employeeHoursAssigned[$candidate->id] += $shift_state->shift->timeblock->getDurationHours();
    }

    private function unassignShift(Shift $shift): void
    {
        if (!$shift->isAssigned()) {
            return;
        }

        $employee = $shift->assignee;

        // update assignment cache
        $this->employeeAssignments[$employee->id] = $this->employeeAssignments[$employee->id]->reject(
            fn ($s) => $s->id === $shift->id
        );
        $this->employeeHoursAssigned[$employee->id] -= $shift->timeblock->getDurationHours();

        $shift->unassign();
    }

    private function createShiftState(Shift $shift): ShiftAssignmentState
    {
        // Use cached method instead of going through CandidateFinder
        $candidates = $this->getSchedulableCachedCandidatesFor($shift);
        $this->employeeSorter->sort($candidates);
        return new ShiftAssignmentState($shift, $candidates);
    }

    private function unassigningMakesEmployeeSchedulableFor(ShiftAssignmentState $toUnassign, Employee $employee, Shift $shift)
    {
        $this->unassignShift($toUnassign->shift);
        $makesSchedulable = $this->candidateFinder->candidateIsSchedulableFor($employee, $shift);
        $this->assignShift($toUnassign, $employee);

        return $makesSchedulable;
    }

    private function tryResolveConflict(Shift $failed_shift, array &$assignment_state_stack): bool
    {
        $allCandidates = $this->getCachedCandidatesFor($failed_shift);

        $shift_to_reassign = null;
        $shift_state_to_reassign = null;

        /** @var Employee $candidate */
        foreach ($allCandidates as $candidate) {

            // get overlapping shift assigned to employee
            $selected_shift = $this->getCachedOverlappingShiftFor($candidate, $failed_shift->timeblock);
            $selected_state = $selected_shift ? ($assignment_state_stack[$selected_shift->id] ?? null) : null;

            // if employee is scheduled for no overlapping shifts, get lowest priority reassignable shift that would free employee for failed shift
            if ($selected_shift === null) {
                // for each shift assigned to employee
                foreach ($this->getCachedAssignmentsFor($candidate) as $current_shift) {
                    $current_state = $assignment_state_stack[$current_shift->id] ?? null;

                    if ($current_state === null) {
                        continue;
                    }

                    /**
                     * if the current shift
                     *  has more candidates,
                     *  would free the employee for the failed shift and
                     *  has a lower priority than the current selected shift
                     */
                    if (
                        $current_state->hasMoreCandidates() &&
                        $this->unassigningMakesEmployeeSchedulableFor($current_state, $candidate, $failed_shift) &&
                        ($selected_shift === null || $this->getCachedPriorityFor($selected_shift) > $this->getCachedPriorityFor($current_shift))
                    ) {
                        // update selected shift
                        $selected_shift = $current_shift;
                        $selected_state = $current_state;
                    }
                }
            }

            // if unassigning the selected shift would free the employee for the failed shift and the selected shift has a lower priority than the current shift to reassign, update the shift to reassign
            if (
                $selected_shift !== null &&
                $selected_state !== null &&
                $this->unassigningMakesEmployeeSchedulableFor($selected_state, $candidate, $failed_shift) &&
                (
                    $shift_to_reassign === null ||
                    $this->getCachedPriorityFor($shift_to_reassign) > $this->getCachedPriorityFor($selected_shift)
                )
            ) {
                $shift_to_reassign = $selected_shift;
                $shift_state_to_reassign = $selected_state;
            }
        }

        if (isset($shift_to_reassign) && isset($shift_state_to_reassign) && $shift_state_to_reassign->hasMoreCandidates()) {
            $employee = $shift_to_reassign->assignee;

            // reassign the shift_to_reassign to its next candidate
            $next_candidate = $shift_state_to_reassign->getNextCandidate();
            $this->assignShift($shift_state_to_reassign, $next_candidate);

            // assign the failed shift to the freed employee (update cache properly)
            $failed_shift_state = $assignment_state_stack[$failed_shift->id];
            $this->assignShift($failed_shift_state, $employee);

            Log::debug('resolved conflict by reassigning shift ' . $shift_to_reassign->id . ' to ' . $next_candidate . ' and assigning shift ' . $failed_shift->id . ' to ' . $candidate);

            return true;
        }

        Log::debug('failed to resolve conflict for shift ' . $failed_shift->id);
        return false;
    }

    private function assignShifts(Collection|bool $shifts = true): bool
    {
        // SETUP

        // initialize $shifts Collection
        if ($shifts === true) {
            $shifts = $this->schedule->shifts;
        } elseif ($shifts === false) {
            $shifts = $this->schedule->getUnassignedShifts();
        }
        /** @var Collection $shifts */

        // handle empty $shifts collection
        if ($shifts->isEmpty()) {
            return true;
        }

        // sort shifts by priority based on cached values (highest priority first)
        $assignable_shifts = $shifts->sort(function ($shift1, $shift2) {
            $priority1 = $this->shiftPriorityCache[$shift1->id];
            $priority2 = $this->shiftPriorityCache[$shift2->id];
            return $priority1 > $priority2 ? -1 : 1;
        });

        // initialize assignment tracking cache
        foreach ($this->employees as $employee) {
            $this->employeeAssignments[$employee->id] = new Collection();
            $this->employeeHoursAssigned[$employee->id] = 0.0;
        }

        // initialize variables
        $shift_queue = clone $assignable_shifts;
        $assignment_state_stack = [];
        $current_state = null;
        $longest_assignment_stack = [];

        // PROCESS shift_queue
        while ($shift_queue->isNotEmpty()) {

            // get next shift to be assigned
            if (empty($current_state)) {
                /**
                 * @var Shift $next_shift
                 */
                $next_shift = $shift_queue->shift();
                $assignment_state_stack[$next_shift->id] = $this->createShiftState($next_shift);
                $current_state = \end($assignment_state_stack);

                Log::debug('processing shift ' . $next_shift->id);
            }

            // try to assign current shift
            if (
                $current_state->hasMoreCandidates()
            ) {
                // assign to next candidate
                $candidate = $current_state->getNextCandidate();
                $this->assignShift($current_state, $candidate);

                Log::debug('assigned shift ' . $current_state->shift->id . ' to ' . $candidate->name);

                $current_state = null;
                continue;

            } else {
                // try conflict resolution
                $failed_shift = $current_state->shift;

                Log::debug('no more candidates for shift ' . $failed_shift->id);

                $conflictResolved = $this->tryResolveConflict($failed_shift, $assignment_state_stack);

                if ($conflictResolved) {
                    $current_state = null;
                    continue;
                }
            }

            // if backtracked all the way, restore longest assignment stack, and set queue to remaining shifts excluding next up (after lengest assignment stack)
            if (empty($assignment_state_stack)) {
                $assignment_state_stack = $longest_assignment_stack;

                foreach ($assignment_state_stack as $state) {
                    if ($state->currentAssignment !== null) {
                        $this->assignShift($state, $state->currentAssignment);
                    }
                }

                $stack_length = count($assignment_state_stack);

                /**
                 * @var Collection $assignable_shifts
                 */

                if ($stack_length = $assignable_shifts->count() - 1) {
                    break;
                }

                $before_exclusion = $assignable_shifts->slice(0, $stack_length - 1);
                $after_exclusion = $assignable_shifts->slice($stack_length);
                $assignable_shifts = $before_exclusion->concat($after_exclusion);
                $shift_queue = $after_exclusion;

                Log::debug('backtracked fully. removed shift ' . $after_exclusion[0]);
            }

            // backtrack to try next candidate of previous shift
            $this->unassignShift($failed_shift);
            \array_pop($assignment_state_stack);
            $shift_queue->unshift($failed_shift);

            Log::debug('backtracked to previous shift');

            // if backtracked all the way, restore longest assignment stack, and set queue to remaining shifts excluding next up (after lengest assignment stack)
            if (empty($assignment_state_stack)) {
                $assignment_state_stack = $longest_assignment_stack;

                foreach ($assignment_state_stack as $state) {
                    if ($state->currentAssignment !== null) {
                        $this->assignShift($state, $state->currentAssignment);
                    }
                }

                $stack_length = count($assignment_state_stack);

                /**
                 * @var Collection $assignable_shifts
                 */

                if ($stack_length == $assignable_shifts->count() - 1) {
                    break;
                }

                $before_exclusion = $assignable_shifts->slice(0, $stack_length - 1);
                $after_exclusion = $assignable_shifts->slice($stack_length);
                $assignable_shifts = $before_exclusion->concat($after_exclusion);
                $shift_queue = $after_exclusion;

                Log::debug('backtracked fully. removed shift ' . $after_exclusion->first());
            } elseif (
                count($longest_assignment_stack) < count($assignment_state_stack)
            ) {
                // update longest successful assignment stack
                $longest_assignment_stack = $assignment_state_stack;

                Log::debug('updated longest assignment stack to ' . join(', ', array_map(fn (ShiftAssignmentState $state) => $state->shift->id, $longest_assignment_stack)));
            }

            $current_state = \end($assignment_state_stack);

            Log::debug('now processing shift ' . $current_state->shift->id);

        }

        return count($assignment_state_stack) === $shifts->count();

    }

    /**
     * Retrieves blocked shifts. Can be used to request user intervention in the case of auto-schedule failure.
     * @param Collection|null $allShifts (null automatically retrieves all shifts on schedule)
     * @param int $currentDepth
     * @return Collection
     */
    public function getBlockedShifts(
        Collection|null $allShifts = null,
        int $currentDepth = 0
    ): Collection {
        if ($allShifts === null) {
            $allShifts = $this->schedule->shifts;
        }

        // find any unassigned shift that would have zero candidates based on current assignments
        $blocked_shifts = new Collection();

        for ($i = $currentDepth; $i < \count($allShifts); $i++) {
            $future_shift = $allShifts->values()->get($i);

            if ($future_shift->isAssigned()) {
                continue;
            }

            $remainingCandidates = $this->getSchedulableCachedCandidatesFor($future_shift);

            if ($remainingCandidates->isEmpty()) {
                $blocked_shifts->push($future_shift);
            }
        }

        return $blocked_shifts;
    }

    private function wouldBlockFutureShifts(
        Collection $allShifts,
        int $currentDepth
    ): bool {
        for ($i = $currentDepth; $i < \count($allShifts); $i++) {
            $future_shift = $allShifts->values()->get($i);

            if ($future_shift->isAssigned()) {
                continue;
            }

            $remainingCandidates = $this->getSchedulableCachedCandidatesFor($future_shift);

            if ($remainingCandidates->isEmpty()) {
                return true;
            }
        }

        return false;
    }

    /** Cache Management */

    private function getCachedPriorityFor(Shift $shift)
    {
        return isset($this->shiftPriorityCache[$shift->id]) ? $this->shiftPriorityCache[$shift->id] : null;
    }

    private function getCachedAssignmentsFor(Employee $employee): Collection|null
    {
        return isset($this->employeeAssignments[$employee->id]) ? $this->employeeAssignments[$employee->id] : null;
    }

    private function getCachedCandidatesFor(Shift $shift): Collection|null
    {
        return isset($this->baseCandidatesCache[$shift->id]) ? $this->baseCandidatesCache[$shift->id] : null;
    }

    private function getSchedulableCachedCandidatesFor(Shift $shift): Collection
    {
        // initialize candidates chche for shift
        if (empty($this->getCachedCandidatesFor($shift))) {
            $this->baseCandidatesCache[$shift->id] = $this->candidateFinder->getCandidatesFor($shift);
        }

        // filter candidates for schedulability using cached assignment data
        return $this->baseCandidatesCache[$shift->id]->filter(
            fn (Employee $candidate): bool => $this->candidateFinder->candidateIsSchedulableFor($candidate, $shift)
        );
    }

    /**
     * Find the shift assigned to an employee that overlaps with a timeblock
     *
     * @param Employee $employee
     * @param Timeblock $timeblock
     * @return Shift|null
     */
    private function getCachedOverlappingShiftFor(Employee $employee, Timeblock $timeblock): ?Shift
    {
        if (empty($this->getCachedAssignmentsFor($employee))) {
            return null;
        }

        foreach ($this->getCachedAssignmentsFor($employee) as $shift) {
            if ($shift->timeblock->overlaps($timeblock)) {
                return $shift;
            }
        }

        return null;
    }
}
