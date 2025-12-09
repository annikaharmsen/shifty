<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class ScheduleTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'establishment_id',
        'name',
    ];

    protected $with = ['templateShifts'];

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }

    public function templateShifts(): HasMany
    {
        return $this->hasMany(TemplateShift::class);
    }

    public function getInstantiatedShifts(CarbonImmutable $start): Collection
    {
        /**
         * @var Collection $this->templateShifts
         */
        $instantiatedShifts = $this->templateShifts->map(
            function (TemplateShift $temp) use ($start): Shift {
                // Parse the time string (HH:MM:SS) to get hours and minutes
                [$hours, $minutes, $seconds] = explode(':', $temp->start_time);
                $startDatetime = $start->addDays($temp->day_of_week - 1)
                    ->setTime((int) $hours, (int) $minutes, (int) $seconds);

                $shift = new Shift([
                    'start_datetime' => $startDatetime,
                    'duration' => $temp->duration,
                    'is_on_call' => $temp->is_on_call,
                    'volume_rating' => $temp->volume_rating,
                ]);
                $shift->setRelation('role', $temp->role);

                return $shift;
            }
        );

        return $instantiatedShifts;
    }

    protected static function booted(): void
    {
        static::deleting(function (ScheduleTemplate $template) {
            if ($template->isForceDeleting()) {
                return;
            }
            // Soft delete all template shifts when template is soft deleted
            $template->templateShifts()->delete();
        });

        static::restoring(function (ScheduleTemplate $template) {
            // Restore all template shifts when template is restored
            $template->templateShifts()->withTrashed()->restore();
        });
    }
}
