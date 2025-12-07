<?php

namespace App\ValueObjects;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Generator;
use IteratorAggregate;
use LogicException;
use Traversable;

class RecurringTimeblock implements TimeblockInterface, IteratorAggregate
{
    private Timeblock $template;
    private CarbonInterval $frequency;
    private CarbonImmutable $endDate;

    public function __construct(
        string $start,
        string $duration,
        string $frequency,
        string $end,
    ) {
        $this->template = new Timeblock($start, $duration);
        $this->frequency = CarbonInterval::make($frequency);
        $this->endDate = CarbonImmutable::make($end);

        if ($this->endDate < $this->template->getEnd()) {
            throw new \InvalidArgumentException('A recurring timeblock cannot terminate before its first occurrence');
        }
    }

    public function getFrequency(): CarbonInterval
    {
        return $this->frequency;
    }

    public function getNextOccurrence(Timeblock $lastOccurrence, $step = 1): Timeblock
    {
        $start = $lastOccurrence->getStart()->add($this->getFrequency()->multiply($step));

        return new Timeblock($start, $this->template->getDuration());
    }

    public function getOccurrence($i = 0): Timeblock
    {
        $start = $this->template->getStart()->add($this->getFrequency()->multiply($i));

        return new Timeblock($start, $this->template->getDuration());
    }

    public function overlaps(TimeblockInterface $other): bool
    {
        foreach ($this as $occurrence) {
            if ($other->overlaps($occurrence)) {
                return true;
            }
        }

        return false;
    }

    public function contains(TimeblockInterface $other): bool
    {
        if ($other instanceof RecurringTimeblock) {

            /** @var Timeblock $occurrence */
            foreach ($other as $occurrence) {

                if (! $this->contains($occurrence)) {
                    return false;
                }

            }

            return true;


        }

        /** @var Timeblock $occurrence */
        foreach ($this as $occurrence) {

            if ($occurrence->contains($other)) {
                return true;
            }

        }

        return false;

    }

    public function getIterator(): Traversable
    {
        $current = $this->template;

        while ($current->getEnd() <= $this->endDate) {
            yield $current;
            $current = $this->getNextOccurrence($current);
        }
    }

    public function xrange($start, $limit, $step = 1): Generator
    {
        if ($start <= $limit) {
            if ($step <= 0) {
                throw new LogicException('Step must be positive');
            }

            for ($i = $start; $i <= $limit; $i += $step) {
                yield $this->getOccurrence($i);
            }
        } else {
            if ($step >= 0) {
                throw new LogicException('Step must be negative');
            }

            for ($i = $start; $i >= $limit; $i += $step) {
                yield $this->getOccurrence($i);

            }
        }
    }

    public function __toString()
    {
        return
            'time: ' . $this->template->getStart()->toTimeString('minute') . ' - ' . $this->template->getEnd()->toTimeString('minute') .
            `\nstarting date: ` . $this->template->getStart()->toDateString() .
            `\nend: ` . $this->template->getEnd()->toString();
    }

}
