<?php

namespace Tests\Builders;

use App\Models\Role;
use App\Models\Schedule;
use App\Models\Shift;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;

class ShiftBuilder
{
    private string $start = '2024-01-01 09:00:00';
    private float $durationHours = 8;
    private bool $isOnCall = false;
    private ?int $volumeRating = null;
    private ?Role $role = null;
    private ?Schedule $schedule = null;

    private function __construct()
    {
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

    public function withVolumeRating(int $rating): self
    {
        $this->volumeRating = $rating;
        return $this;
    }

    public function forRole(Role $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function forSchedule(Schedule $schedule): self
    {
        $this->schedule = $schedule;
        return $this;
    }

    public function build(): Shift
    {
        // Create a default role if none provided
        if ($this->role === null) {
            $this->role = RoleBuilder::create()->withTitle('no role')->build();
        }

        // Create a default schedule if none provided
        if ($this->schedule === null) {
            $this->schedule = ScheduleBuilder::create()->build();
        }

        // Convert hours to seconds for database storage
        $durationSeconds = (int) ($this->durationHours * 3600);

        $shift = Shift::create([
            'schedule_id' => $this->schedule->id,
            'role_id' => $this->role->id,
            'start_datetime' => $this->start,
            'duration' => $durationSeconds,
            'is_on_call' => $this->isOnCall,
            'volume_rating' => $this->volumeRating,
        ]);

        $shift->setRelation('role', $this->role);
        $shift->setRelation('schedule', $this->schedule);

        return $shift;
    }
}
