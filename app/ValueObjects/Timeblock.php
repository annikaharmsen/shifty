<?php

namespace App\ValueObjects;

use Carbon\CarbonInterval;
use Carbon\CarbonImmutable;

class Timeblock implements TimeblockInterface
{
    protected CarbonImmutable $start;
    protected CarbonInterval $duration;

    public function __construct(string|CarbonImmutable $start, string|CarbonInterval $duration)
    {
        $this->start = CarbonImmutable::make($start);
        $this->duration = CarbonInterval::make($duration);
    }

    public function getStart(): CarbonImmutable
    {
        return $this->start;
    }

    public function getEnd(): CarbonImmutable
    {
        return $this->start->add($this->duration);
    }

    public function getDuration(): CarbonInterval
    {
        return $this->duration;
    }

    public function overlaps(TimeblockInterface $other): bool
    {
        if ($other instanceof RecurringTimeblock) {

            foreach ($other as $occurrence) {
                if (! $this->isBefore($occurrence) && ! $this->isAfter($occurrence)) {
                    return true;
                }
            }

            return false;

        }

        // for Timeblock
        return ! $this->isBefore($other) && ! $this->isAfter($other);

    }

    public function contains(TimeblockInterface $other): bool
    {
        if ($other instanceof RecurringTimeblock) {

            // for RecurringTimeblock
            foreach ($other as $occurrence) {
                if ($this->startsBefore($occurrence) && $this->endsAfter($occurrence)) {
                    return true;
                }
            }

            return false;
        }

        // for Timeblock
        return $this->startsBefore($other) && $this->endsAfter($other);

    }

    private function startsBefore(Timeblock $other): bool
    {
        return $this->start <= $other->getStart();
    }

    private function endsBefore(Timeblock $other): bool
    {
        return $this->getEnd() <= $other->getEnd();
    }

    private function isBefore(Timeblock $other): bool
    {
        return $this->getEnd() <= $other->getStart();
    }
    private function isAfter(Timeblock $other): bool
    {
        return $other->isBefore($this);
    }

    private function startsAfter(Timeblock $other): bool
    {
        return $this->start >= $other->getStart();
    }

    private function endsAfter(Timeblock $other): bool
    {
        return $this->getEnd() >= $other->getEnd();
    }
    public function getDurationHours(): float
    {
        return $this->duration->totalHours;
    }

    public function getHoursOverlappingWith(Timeblock $other): float
    {

        if (!$this->overlaps($other)) {
            return 0.0;
        }
        $overlapStart = $this->start->max($other->start);
        $overlapEnd = $this->getEnd()->min($other->getEnd());

        return $overlapStart->diffInHours($overlapEnd, true);

    }

    public function __toString()
    {
        return $this->start->toFormattedDayDateString() . ' ' . $this->start->format('ga-') . $this->getEnd()->format('ga');
    }
}
