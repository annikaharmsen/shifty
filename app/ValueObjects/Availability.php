<?php

namespace App\ValueObjects;

use App\Models\AvailabilityRule;
use Illuminate\Support\Collection;

class Availability
{
    public const UNAVAILABLE = false;
    public const AVAILABLE = true;
    private const DEFAULT = self::AVAILABLE;

    private Collection $availabilityRules;

    public function __construct(Collection $availabilityRules)
    {
        $this->availabilityRules = $availabilityRules->ensure(AvailabilityRule::class);
    }

    public function isFullyAvailable(Timeblock $timeblock): bool
    {

        return $this->availabilityRules->reduce(
            fn (bool $prev, AvailabilityRule $rule): bool
                => $rule->applyRule($prev, $timeblock),
            self::DEFAULT
        );
    }

}
