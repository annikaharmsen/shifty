<?php

namespace Tests\Builders;

use App\Models\Role;
use App\Models\Shift;
use Carbon\CarbonImmutable;

class ShiftBuilder
{
    private static int $idCounter = 1;

    private string $start = '2024-01-01 09:00:00';
    private float $durationHours = 8;
    private bool $isOnCall = false;
    private Role $role;

    private function __construct()
    {
        $this->role = RoleBuilder::create()->withTitle('no role')->build();
    }

    public static function create(): self
    {
        return new self();
    }

    public function from(string $datetime): self
    {
        $this->start = new CarbonImmutable($datetime);
        return $this;
    }

    public function withDuration(float $hours): self
    {
        $this->durationHours = $hours;
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

    public function build(): Shift
    {
        $shift = new Shift();

        $shift->setAttribute('id', self::$idCounter++);
        $shift->exists = true; // Mark as existing so Laravel treats id as valid
        $shift->start_datetime = $this->start;
        $shift->duration = $this->durationHours . 'hours';
        $shift->is_on_call = $this->isOnCall;

        $shift->setRelation('role', $this->role);

        return $shift;
    }
}
