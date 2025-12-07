<?php

namespace Tests\Builders;

use App\Models\Role;
use App\Models\TemplateShift;

class TemplateShiftBuilder
{
    private static int $idCounter = 1;

    private int $dayOfWeek = 1;
    private string $startTime = '16 hours';
    private string $duration = '8 hours';
    private bool $isOnCall = false;
    private ?Role $role = null;

    public static function create(): self
    {
        return new self();
    }

    public function onDayOfWeek(int $day): self
    {
        $this->dayOfWeek = $day;
        return $this;
    }

    public function startsAt(string $timestring): self
    {
        $this->startTime = $timestring;
        return $this;
    }

    public function withDuration(string $timestring): self
    {
        $this->duration = $timestring;
        return $this;
    }

    public function onCall(): self
    {
        $this->isOnCall = true;
        return $this;
    }

    public function live(): self
    {
        $this->isOnCall = false;
        return $this;
    }

    public function forRole(Role $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function build(): TemplateShift
    {
        $shift = new TemplateShift();

        $shift->id = self::$idCounter++;
        $shift->day_of_week = $this->dayOfWeek;
        $shift->start_time = $this->startTime;
        $shift->duration = $this->duration;
        $shift->is_on_call = $this->isOnCall;

        $shift->setRelation('role', $this->role);

        return $shift;
    }
}
