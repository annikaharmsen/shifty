<?php

namespace App\Models;

use App\ValueObjects\Timeblock;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    protected $fillable = [
        'start_datetime',
        'duration',
        'is_on_call',
        'role_id',
        'assignee_id',
        'schedule_id'
    ];

    public function timeblock(): Attribute
    {
        return Attribute::make(
            fn () => new Timeblock(
                $this->start_datetime,
                $this->duration
            )
        );
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assignee_id');
    }

    public function isAssigned(): bool
    {
        return isset($this->assignee);
    }

    public function unassign(): void
    {
        $this->setRelation('assignee', null);
    }

    public function assignTo(Employee $employee): void
    {
        $this->setRelation('assignee', $employee);
    }

    public function isAssignedTo(Employee $employee): bool
    {
        return $this->assignee && $this->assignee->id === $employee->id;
    }

    public function toString()
    {
        return $this->role->title .  ($this->is_on_call ? ' on call ' : ' ') . PHP_EOL .
            $this->timeblock . PHP_EOL .
            ($this->assignee ? 'assigned to: ' . $this->assignee->name : ' (unassigned)');
    }
}
