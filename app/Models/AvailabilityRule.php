<?php

namespace App\Models;

use App\ValueObjects\RecurringTimeblock;
use App\ValueObjects\Timeblock;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AvailabilityRule extends Model
{
    use SoftDeletes;

    public const UNAVAILABLE = false;
    public const AVAILABLE = true;

    protected $fillable = [
        'employee_id',
        'is_available',
        'start_datetime',
        'duration',
        'frequency',
        'termination_datetime',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'termination_datetime' => 'datetime',
        'is_available' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function timeblock(): Attribute
    {
        if (empty($this->frequency)) {
            return Attribute::make(
                fn () => new Timeblock(
                    $this->start_datetime,
                    $this->duration . ' seconds',
                )
            );
        } else {
            return Attribute::make(
                fn () => new RecurringTimeblock(
                    $this->start_datetime->toIso8601String(),
                    $this->duration . ' seconds',
                    $this->frequency . ' seconds',
                    $this->termination_datetime->toIso8601String()
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
