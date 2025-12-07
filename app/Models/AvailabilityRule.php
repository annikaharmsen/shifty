<?php

namespace App\Models;

use App\ValueObjects\RecurringTimeblock;
use App\ValueObjects\Timeblock;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class AvailabilityRule extends Model
{
    public const UNAVAILABLE = false;
    public const AVAILABLE = true;

    protected $fillable = [
        'is_available',
        'start_datetime',
        'duration',
        'frequency',
        'termination_datetime',
        'employee_id'
    ];

    public function timeblock(): Attribute
    {
        if (empty($this->frequency)) {
            return Attribute::make(
                fn () => new Timeblock(
                    $this->start_datetime,
                    $this->duration,
                )
            ) ;
        } else {
            return Attribute::make(
                fn () => new RecurringTimeblock(
                    $this->start_datetime,
                    $this->duration,
                    $this->frequency,
                    $this->termination_datetime
                )
            );
        }
    }

    public function isAvailable(): bool
    {
        return $this->is_available;
    }

    public function applyRule(bool $currentAvailability, Timeblock $timeblock): bool
    {
        if ($this->timeblock->contains($timeblock)) {
            return $this->is_available;
        }
        // if rule creates unavailability during some of the time -> unavailable during the timeblock
        elseif ($this->timeblock->overlaps($timeblock) && $this->is_available === self::UNAVAILABLE) {
            return self::UNAVAILABLE;
        } else {
            return $currentAvailability;
        }
    }
}
