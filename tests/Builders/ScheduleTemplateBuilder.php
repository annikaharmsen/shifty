<?php

namespace Tests\Builders;

use App\Models\Company;
use App\Models\Establishment;
use App\Models\ScheduleTemplate;

class ScheduleTemplateBuilder
{
    private ?Establishment $establishment = null;
    private string $name = 'Test Template';

    public static function create(): self
    {
        return new self();
    }

    public function forEstablishment(Establishment $establishment): self
    {
        $this->establishment = $establishment;
        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function build(): ScheduleTemplate
    {
        // Create a default establishment if none provided
        if ($this->establishment === null) {
            $company = Company::create(['name' => 'Test Company']);
            $this->establishment = Establishment::create([
                'company_id' => $company->id,
                'name' => 'Test Establishment',
            ]);
        }

        return ScheduleTemplate::create([
            'establishment_id' => $this->establishment->id,
            'name' => $this->name,
        ]);
    }
}
