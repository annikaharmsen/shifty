<?php

namespace App\Models;

use App\ValueObjects\Timeblock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Schedule extends Model
{
    protected $fillable = ['shifts'];
    protected $with = ['shifts'];

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    // TODO: future use
    public function getAssignedShifts(): Collection
    {
        return $this->shifts->filter(
            fn (Shift $shift): bool => $shift->isAssigned()
        );
    }

    public function getUnassignedShifts(): Collection
    {
        return $this->shifts->filter(
            fn (Shift $shift): bool => ! $shift->isAssigned()
        );
    }

    /** gets weekly hours left to schedule */
    public function getSchedulableHours(Employee $employee): float
    {
        return $employee->getWeeklyHours() - $this->getHoursAssignedTo($employee);
    }

    public function getShiftsAssignedTo(Employee $employee): Collection
    {
        return $this->shifts->filter(
            fn (Shift $shift): bool => $shift->isAssignedTo($employee)
        );
    }

    public function getHoursAssignedTo(Employee $employee): float
    {
        return $this->getShiftsAssignedTo($employee)->reduce(
            fn (float $prev, Shift $shift): float
            => $prev + $shift->timeblock->getDurationHours(),
            0
        );
    }

    public function __toString()
    {
        $shiftNum = 1;
        /** @var Collection $this->shifts */
        return 'Schedule:' . $this->shifts ?
            $this->shifts->reduce(fn ($prev, $shift, $key) => $prev . PHP_EOL . 'Shift ' . $key + 1 . ': ' . $shift->toString()) :
            PHP_EOL . 'no shifts found';
    }
}
