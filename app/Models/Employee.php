<?php

namespace App\Models;

use App\ValueObjects\Timeblock;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\ValueObjects\Availability;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'user',
        'weekly_hours'
    ];
    protected $with = [
        'roles',
        'availabilityRules'
    ];

    public string $name;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function availabilityRules(): HasMany
    {
        return $this->hasMany(AvailabilityRule::class);
    }

    protected function availability(): Attribute
    {
        return Attribute::make(
            fn () => new Availability(
                $this->availabilityRules
            )
        );
    }

    public function isFullyAvailable(Timeblock $timeblock): bool
    {
        return $this->availability->isFullyAvailable($timeblock);
    }

    public function getWeeklyHours(): float
    {
        return $this->weekly_hours;
    }

    public function hasRole(Role $role): bool
    {
        return $this->roles->contains(fn (Role $r) => $r->id == $role->id);
    }
}
