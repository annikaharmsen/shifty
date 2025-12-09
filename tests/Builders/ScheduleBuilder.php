<?php

namespace Tests\Builders;

use App\Models\Company;
use App\Models\Establishment;
use App\Models\Schedule;
use Carbon\Carbon;

class ScheduleBuilder
{
    private ?Establishment $establishment = null;
    private ?Carbon $weekStartDate = null;

    public static function create(): self
    {
        return new self();
    }

    public function forEstablishment(Establishment $establishment): self
    {
        $this->establishment = $establishment;
        return $this;
    }

    public function startingOn(string|Carbon $date): self
    {
        $this->weekStartDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        return $this;
    }

    public function build(): Schedule
    {
        // Create a default establishment if none provided
        if ($this->establishment === null) {
            $company = Company::create(['name' => 'Test Company']);
            $this->establishment = Establishment::create([
                'company_id' => $company->id,
                'name' => 'Test Establishment',
            ]);
        }

        // Use a default week start date if none provided
        if ($this->weekStartDate === null) {
            $this->weekStartDate = Carbon::parse('2024-01-01');
        }

        return Schedule::create([
            'establishment_id' => $this->establishment->id,
            'week_start_date' => $this->weekStartDate,
        ]);
    }
}
