<?php

namespace Tests\Generators;

use App\Models\ScheduleTemplate;
use Tests\Builders\TemplateShiftBuilder;

class ScheduleTemplateGenerator
{
    // Day of week constants (ISO-8601: 1 = Monday, 7 = Sunday)
    private const MONDAY = 1;
    private const TUESDAY = 2;
    private const WEDNESDAY = 3;
    private const THURSDAY = 4;
    private const FRIDAY = 5;
    private const SATURDAY = 6;
    private const SUNDAY = 7;

    public static function generate($serverRole, $bartenderRole): ScheduleTemplate
    {
        $template = new ScheduleTemplate();

        $shifts = [
            // Monday
            ...self::createMondayShifts($serverRole, $bartenderRole),

            // Tuesday
            ...self::createTuesdayShifts($serverRole, $bartenderRole),

            // Wednesday
            ...self::createWednesdayShifts($serverRole, $bartenderRole),

            // Thursday
            ...self::createThursdayShifts($serverRole, $bartenderRole),

            // Friday
            ...self::createFridayShifts($serverRole, $bartenderRole),

            // Saturday
            ...self::createSaturdayShifts($serverRole, $bartenderRole),

            // Sunday
            ...self::createSundayShifts($serverRole, $bartenderRole),

            // On-Call Shifts
            ...self::createOnCallShifts($serverRole, $bartenderRole),
        ];

        // Set the relationship in memory without persisting to database
        $template->setRelation('templateShifts', collect($shifts));

        return $template;
    }

    private static function createMondayShifts($serverRole, $bartenderRole): array
    {
        return [
            // Lunch Service
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::MONDAY)
                ->startsAt('11 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::MONDAY)
                ->startsAt('11 hours')
                ->withDuration('5 hours')
                ->forRole($bartenderRole)
                ->live()
                ->build(),

            // Dinner Service
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::MONDAY)
                ->startsAt('16 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::MONDAY)
                ->startsAt('17 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::MONDAY)
                ->startsAt('16 hours')
                ->withDuration('7 hours')
                ->forRole($bartenderRole)
                ->live()
                ->build(),
        ];
    }

    private static function createTuesdayShifts($serverRole, $bartenderRole): array
    {
        return [
            // Lunch Service
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::TUESDAY)
                ->startsAt('11 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::TUESDAY)
                ->startsAt('11 hours')
                ->withDuration('5 hours')
                ->forRole($bartenderRole)
                ->live()
                ->build(),

            // Dinner Service
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::TUESDAY)
                ->startsAt('16 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::TUESDAY)
                ->startsAt('17 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::TUESDAY)
                ->startsAt('16 hours')
                ->withDuration('7 hours')
                ->forRole($bartenderRole)
                ->live()
                ->build(),
        ];
    }

    private static function createWednesdayShifts($serverRole, $bartenderRole): array
    {
        return [
            // Lunch Service
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::WEDNESDAY)
                ->startsAt('11 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::WEDNESDAY)
                ->startsAt('12 hours')
                ->withDuration('4 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::WEDNESDAY)
                ->startsAt('11 hours')
                ->withDuration('5 hours')
                ->forRole($bartenderRole)
                ->live()
                ->build(),

            // Dinner Service
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::WEDNESDAY)
                ->startsAt('16 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::WEDNESDAY)
                ->startsAt('17 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::WEDNESDAY)
                ->startsAt('18 hours')
                ->withDuration('4 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::WEDNESDAY)
                ->startsAt('16 hours')
                ->withDuration('8 hours')
                ->forRole($bartenderRole)
                ->live()
                ->build(),
        ];
    }

    private static function createThursdayShifts($serverRole, $bartenderRole): array
    {
        return [
            // Lunch Service
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::THURSDAY)
                ->startsAt('11 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::THURSDAY)
                ->startsAt('12 hours')
                ->withDuration('4 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::THURSDAY)
                ->startsAt('11 hours')
                ->withDuration('5 hours')
                ->forRole($bartenderRole)
                ->live()
                ->build(),

            // Dinner Service
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::THURSDAY)
                ->startsAt('16 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::THURSDAY)
                ->startsAt('17 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::THURSDAY)
                ->startsAt('17 hours')
                ->withDuration('6 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::THURSDAY)
                ->startsAt('16 hours')
                ->withDuration('8 hours')
                ->forRole($bartenderRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::THURSDAY)
                ->startsAt('18 hours')
                ->withDuration('7 hours')
                ->forRole($bartenderRole)
                ->live()
                ->build(),
        ];
    }

    private static function createFridayShifts($serverRole, $bartenderRole): array
    {
        return [
            // Lunch Service
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::FRIDAY)
                ->startsAt('11 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::FRIDAY)
                ->startsAt('12 hours')
                ->withDuration('4 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::FRIDAY)
                ->startsAt('11 hours')
                ->withDuration('5 hours')
                ->forRole($bartenderRole)
                ->live()
                ->build(),

            // Dinner Service
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::FRIDAY)
                ->startsAt('16 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::FRIDAY)
                ->startsAt('16 hours')
                ->withDuration('6 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::FRIDAY)
                ->startsAt('17 hours')
                ->withDuration('6 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::FRIDAY)
                ->startsAt('18 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::FRIDAY)
                ->startsAt('18 hours')
                ->withDuration('6 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::FRIDAY)
                ->startsAt('16 hours')
                ->withDuration('9 hours')
                ->forRole($bartenderRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::FRIDAY)
                ->startsAt('17 hours')
                ->withDuration('9 hours')
                ->forRole($bartenderRole)
                ->live()
                ->build(),
        ];
    }

    private static function createSaturdayShifts($serverRole, $bartenderRole): array
    {
        return [
            // Brunch Service
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SATURDAY)
                ->startsAt('09 hours')
                ->withDuration('7 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SATURDAY)
                ->startsAt('10 hours')
                ->withDuration('6 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SATURDAY)
                ->startsAt('11 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SATURDAY)
                ->startsAt('09 hours')
                ->withDuration('7 hours')
                ->forRole($bartenderRole)
                ->live()
                ->build(),

            // Dinner Service
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SATURDAY)
                ->startsAt('16 hours')
                ->withDuration('6 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SATURDAY)
                ->startsAt('16 hours')
                ->withDuration('7 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SATURDAY)
                ->startsAt('17 hours')
                ->withDuration('6 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SATURDAY)
                ->startsAt('17 hours')
                ->withDuration('6 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SATURDAY)
                ->startsAt('18 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SATURDAY)
                ->startsAt('16 hours')
                ->withDuration('9 hours')
                ->forRole($bartenderRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SATURDAY)
                ->startsAt('17 hours')
                ->withDuration('9 hours')
                ->forRole($bartenderRole)
                ->live()
                ->build(),
        ];
    }

    private static function createSundayShifts($serverRole, $bartenderRole): array
    {
        return [
            // Brunch Service
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SUNDAY)
                ->startsAt('09 hours')
                ->withDuration('7 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SUNDAY)
                ->startsAt('10 hours')
                ->withDuration('6 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SUNDAY)
                ->startsAt('11 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SUNDAY)
                ->startsAt('12 hours')
                ->withDuration('4 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SUNDAY)
                ->startsAt('09 hours')
                ->withDuration('7 hours')
                ->forRole($bartenderRole)
                ->live()
                ->build(),

            // Dinner Service
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SUNDAY)
                ->startsAt('16 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SUNDAY)
                ->startsAt('17 hours')
                ->withDuration('4 hours')
                ->forRole($serverRole)
                ->live()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SUNDAY)
                ->startsAt('16 hours')
                ->withDuration('6 hours')
                ->forRole($bartenderRole)
                ->live()
                ->build(),
        ];
    }

    private static function createOnCallShifts($serverRole, $bartenderRole): array
    {
        return [
            // Weekday On-Call
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::MONDAY)
                ->startsAt('16 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->onCall()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::TUESDAY)
                ->startsAt('16 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->onCall()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::WEDNESDAY)
                ->startsAt('17 hours')
                ->withDuration('5 hours')
                ->forRole($serverRole)
                ->onCall()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::THURSDAY)
                ->startsAt('17 hours')
                ->withDuration('5 hours')
                ->forRole($bartenderRole)
                ->onCall()
                ->build(),

            // Weekend On-Call
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::FRIDAY)
                ->startsAt('17 hours')
                ->withDuration('6 hours')
                ->forRole($serverRole)
                ->onCall()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::FRIDAY)
                ->startsAt('17 hours')
                ->withDuration('9 hours')
                ->forRole($bartenderRole)
                ->onCall()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SATURDAY)
                ->startsAt('10 hours')
                ->withDuration('6 hours')
                ->forRole($serverRole)
                ->onCall()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SATURDAY)
                ->startsAt('17 hours')
                ->withDuration('6 hours')
                ->forRole($serverRole)
                ->onCall()
                ->build(),
            TemplateShiftBuilder::create()
                ->onDayOfWeek(self::SUNDAY)
                ->startsAt('10 hours')
                ->withDuration('6 hours')
                ->forRole($serverRole)
                ->onCall()
                ->build(),
        ];
    }
}
