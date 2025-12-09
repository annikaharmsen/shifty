<?php

namespace Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Builders\TemplateShiftBuilder;
use Tests\TestCase;

class TemplateShiftTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_template_shift_with_volume_rating(): void
    {
        $templateShift = TemplateShiftBuilder::create()
            ->withVolumeRating(7)
            ->build();

        $this->assertDatabaseHas('template_shifts', [
            'id' => $templateShift->id,
            'volume_rating' => 7,
        ]);
    }

    public function test_can_create_template_shift_without_volume_rating(): void
    {
        $templateShift = TemplateShiftBuilder::create()
            ->build();

        $this->assertDatabaseHas('template_shifts', [
            'id' => $templateShift->id,
            'volume_rating' => null,
        ]);
    }

    public function test_volume_rating_persists_correctly_to_database(): void
    {
        // Test minimum value (1)
        $templateShift1 = TemplateShiftBuilder::create()
            ->withVolumeRating(1)
            ->onDayOfWeek(1)
            ->build();

        // Test maximum value (10)
        $templateShift2 = TemplateShiftBuilder::create()
            ->withVolumeRating(10)
            ->onDayOfWeek(2)
            ->build();

        // Test middle value (5)
        $templateShift3 = TemplateShiftBuilder::create()
            ->withVolumeRating(5)
            ->onDayOfWeek(3)
            ->build();

        // Refresh from database to ensure values are persisted
        $templateShift1->refresh();
        $templateShift2->refresh();
        $templateShift3->refresh();

        $this->assertEquals(1, $templateShift1->volume_rating);
        $this->assertEquals(10, $templateShift2->volume_rating);
        $this->assertEquals(5, $templateShift3->volume_rating);
    }
}
