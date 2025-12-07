<?php

namespace Tests\Builders;

use App\Models\AvailabilityRule;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;

class AvailabilityRuleBuilder
{
    private static int $idCounter = 1;

    private bool $isAvailable = false;
    private string $start = '2024-01-01 00:00:00';
    private string $duration = '12 hours';
    private string $frequency = '1 week';
    private string $end = '2025-01-01 00:00:00';

    public static function create(): self
    {
        return new self();
    }

    public function withAvailability(int $isAvailable): self
    {
        $this->isAvailable = $isAvailable;
        return new self();
    }

    public function from(string $datetime): self
    {
        $this->start = $datetime;
        return $this;
    }

    public function withDuration(string $datetime): self
    {
        $this->duration = $datetime;
        return $this;
    }

    public function withFrequency(string $datetime): self
    {
        $this->frequency = $datetime;
        return $this;
    }

    public function ends(string $datetime): self
    {
        $this->end = $datetime;
        return new self();
    }

    public function build(): AvailabilityRule
    {
        $rule = new AvailabilityRule();

        $start = CarbonImmutable::make($this->start);
        $duration = CarbonInterval::make($this->duration);
        $end = CarbonImmutable::make($this->end);

        $firstOccuranceEnd = $start->add($duration);
        if ($end < $firstOccuranceEnd) {
            $end = $firstOccuranceEnd->addYear();
        }

        $rule->id = self::$idCounter++;
        $rule->is_available = $this->isAvailable;
        $rule->start_datetime = $this->start;
        $rule->duration = $this->duration;
        $rule->frequency = $this->frequency;
        $rule->termination_datetime = $end;

        return $rule;
    }
}
