<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TemplateShift extends Model
{
    use SoftDeletes;

    private const MONDAY = 1;
    private const TUESDAY = 2;
    private const WEDNESDAY = 3;
    private const THURSDAY = 4;
    private const FRIDAY = 5;
    private const SATURDAY = 6;
    private const SUNDAY = 7;

    protected $fillable = [
        'schedule_template_id',
        'role_id',
        'day_of_week',
        'start_time',
        'duration',
        'is_on_call',
        'volume_rating',
    ];

    protected $casts = [
        'is_on_call' => 'boolean',
        'volume_rating' => 'integer',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function scheduleTemplate(): BelongsTo
    {
        return $this->belongsTo(ScheduleTemplate::class);
    }
}
