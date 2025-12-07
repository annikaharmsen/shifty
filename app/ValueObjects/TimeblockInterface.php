<?php

namespace App\ValueObjects;

use Carbon\CarbonImmutable;

interface TimeblockInterface
{
    public function overlaps(TimeblockInterface $other): bool;
    public function contains(TimeblockInterface $other): bool;
}
