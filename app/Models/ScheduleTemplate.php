<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ScheduleTemplate extends Model
{
    // scedule_template_shifts pivot table

    protected $with = ['templateShifts'];

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
                $shift = new Shift([
                    'start_datetime' => $start->addDays($temp->day_of_week - 1)->add(CarbonInterval::make($temp->start_time)),
                    'duration' => $temp->duration,
                    'is_on_call' => $temp->is_on_call
                ]);
                $shift->setRelation('role', $temp->role);

                return $shift;
            }
        );

        return $instantiatedShifts;
    }
}
