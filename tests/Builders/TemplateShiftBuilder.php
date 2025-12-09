<?php

namespace Tests\Builders;

use App\Models\Role;
use App\Models\ScheduleTemplate;
use App\Models\TemplateShift;
use Carbon\CarbonInterval;

class TemplateShiftBuilder
{
    private int $dayOfWeek = 1;
    private string $startTime = '16 hours';
    private string $duration = '8 hours';
    private bool $isOnCall = false;
    private ?int $volumeRating = null;
    private ?Role $role = null;
    private ?ScheduleTemplate $scheduleTemplate = null;

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

    public function forScheduleTemplate(ScheduleTemplate $scheduleTemplate): self
    {
        $this->scheduleTemplate = $scheduleTemplate;
        return $this;
    }

    public function withVolumeRating(int $rating): self
    {
        $this->volumeRating = $rating;
        return $this;
    }

    public function build(): TemplateShift
    {
        // Create a default role if none provided
        if ($this->role === null) {
            $this->role = RoleBuilder::create()->withTitle('server')->build();
        }

        // Create a default schedule template if none provided
        if ($this->scheduleTemplate === null) {
            $this->scheduleTemplate = ScheduleTemplateBuilder::create()->build();
        }

        // Convert time string (e.g., "16 hours") to HH:MM:SS format
        $startTimeFormatted = $this->convertToTimeFormat($this->startTime);

        // Convert duration string (e.g., "8 hours") to seconds
        $durationSeconds = (int) CarbonInterval::make($this->duration)->totalSeconds;

        $shift = TemplateShift::create([
            'schedule_template_id' => $this->scheduleTemplate->id,
            'role_id' => $this->role->id,
            'day_of_week' => $this->dayOfWeek,
            'start_time' => $startTimeFormatted,
            'duration' => $durationSeconds,
            'is_on_call' => $this->isOnCall,
            'volume_rating' => $this->volumeRating,
        ]);

        $shift->setRelation('role', $this->role);
        $shift->setRelation('scheduleTemplate', $this->scheduleTemplate);

        return $shift;
    }

    private function convertToTimeFormat(string $timestring): string
    {
        // Parse strings like "16 hours", "09 hours", etc. to HH:MM:SS
        $interval = CarbonInterval::make($timestring);
        $hours = $interval->hours;
        return sprintf('%02d:00:00', $hours);
    }
}
