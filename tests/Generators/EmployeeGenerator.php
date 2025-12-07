<?php

namespace Tests\Generators;

use App\Models\Role;
use App\ValueObjects\EmployeeRepo;
use Illuminate\Support\Collection;
use Tests\Builders\AvailabilityRuleBuilder;
use Tests\Builders\EmployeeBuilder;

class EmployeeGenerator
{
    public static function generate($serverRole, $bartenderRole): Collection
    {

        $employees = collect([
            self::createSarah($serverRole, $bartenderRole),
            self::createMarcus($serverRole, $bartenderRole),
            self::createJennifer($serverRole),
            self::createJake($serverRole, $bartenderRole),
            self::createRachel($bartenderRole),
            self::createAlex($serverRole, $bartenderRole),
            self::createPriya($serverRole, $bartenderRole),
            self::createTommy($serverRole),
            self::createChris($serverRole, $bartenderRole),
            self::createMaya($bartenderRole),
            self::createDavid($serverRole, $bartenderRole),
            self::createLisa($serverRole, $bartenderRole),
            self::createJordan($serverRole),
            self::createEmma($serverRole),
            self::createRobert($serverRole, $bartenderRole),
        ]);

        return $employees;
    }

    /**
     * creates a full time server/bartender who cannot work Sundays
     */
    public static function createSarah(Role $serverRole, Role $bartenderRole)
    {
        return EmployeeBuilder::create()
            ->withName('Sarah')
            ->withRoles($serverRole, $bartenderRole)
            ->withWeeklyHours(40)
            ->withAvailabilityRules([
                // Cannot work Sundays
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-23 12am') // Sunday
                    ->withDuration('24 hours')
                    ->withFrequency('1 week')
                    ->build()
            ])
            ->build();
    }

    /**
     * creates a full time server/bartender who is always available
     */
    public static function createMarcus(Role $serverRole, Role $bartenderRole)
    {
        return EmployeeBuilder::create()
            ->withName('Marcus')
            ->withRoles($serverRole, $bartenderRole)
            ->withWeeklyHours(40)
            ->alwaysAvailable() // Most flexible employee
            ->build();
    }

    /**
     * creates a full time server who cannot work after 5pm on weekdays and not after 3pm on Saturdays
     */
    public static function createJennifer(Role $serverRole)
    {
        return EmployeeBuilder::create()
            ->withName('Jennifer')
            ->withRole($serverRole)
            ->withWeeklyHours(40)
            ->withAvailabilityRules([
                // Cannot work Mon-Fri after 5 PM
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-24 5pm') // Monday
                    ->withDuration('7 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-25 5pm') // Tuesday
                    ->withDuration('7 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-26 5pm') // Wednesday
                    ->withDuration('7 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-27 5pm') // Thursday
                    ->withDuration('7 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-28 5pm') // Friday
                    ->withDuration('7 hours')
                    ->withFrequency('1 week')
                    ->build(),
                // Cannot work Saturdays after 3 PM
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-29 3pm') // Saturday
                    ->withDuration('9 hours')
                    ->withFrequency('1 week')
                    ->build(),
            ])
            ->build();
    }

    /**
     * creates a full time server/bartender who cannot work before 2pm
     */
    public static function createJake(Role $serverRole, Role $bartenderRole)
    {
        return EmployeeBuilder::create()
            ->withName('Jake')
            ->withRoles($serverRole, $bartenderRole)
            ->withWeeklyHours(40)
            ->withAvailabilityRules([
                // Unavailable before 2 PM any day
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-24 12am') // Monday
                    ->withDuration('14 hours')
                    ->withFrequency('1 day')
                    ->build(),
            ])
            ->build();
    }

    /**
     * creates a full time bartender who cannot work Mondays or Tuesdays
     */
    public static function createRachel(Role $bartenderRole)
    {
        return EmployeeBuilder::create()
            ->withName('Rachel')
            ->withRole($bartenderRole)
            ->withWeeklyHours(40)
            ->withAvailabilityRules([
                // Takes Mondays off
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-24 12am') // Monday
                    ->withDuration('24 hours')
                    ->withFrequency('1 week')
                    ->build(),
                // Takes Tuesdays off
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-25 12am') // Tuesday
                    ->withDuration('24 hours')
                    ->withFrequency('1 week')
                    ->build(),
            ])
            ->build();
    }

    /**
     * creates a part time (20 hr/week) server/bartender who is unavailable Mon-Thu before 5pm
     */
    public static function createAlex(Role $serverRole, Role $bartenderRole)
    {
        return EmployeeBuilder::create()
            ->withName('Alex')
            ->withRoles($serverRole, $bartenderRole)
            ->withWeeklyHours(20)
            ->withAvailabilityRules([
                // Unavailable Mon-Thu before 5 PM (class schedule)
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-24 12am') // Monday
                    ->withDuration('17 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-25 12am') // Tuesday
                    ->withDuration('17 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-26 12am') // Wednesday
                    ->withDuration('17 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-27 12am') // Thursday
                    ->withDuration('17 hours')
                    ->withFrequency('1 week')
                    ->build(),
            ])
            ->build();
    }

    /**
     * creates a part time (25 hr/week) server/bartender who is unavailable before 6pm on weekdays
     */
    public static function createPriya(Role $serverRole, Role $bartenderRole)
    {
        return EmployeeBuilder::create()
            ->withName('Priya')
            ->withRoles($serverRole, $bartenderRole)
            ->withWeeklyHours(25)
            ->withAvailabilityRules([
                // Unavailable Mon-Fri before 6 PM (corporate job)
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-24 12am') // Monday
                    ->withDuration('18 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-25 12am') // Tuesday
                    ->withDuration('18 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-26 12am') // Wednesday
                    ->withDuration('18 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-27 12am') // Thursday
                    ->withDuration('18 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-28 12am') // Friday
                    ->withDuration('18 hours')
                    ->withFrequency('1 week')
                    ->build(),
            ])
            ->build();
    }

    /**
     * creates a part time (20 hr/week) server who is unavailable Mon-Thu
     */
    public static function createTommy(Role $serverRole)
    {
        return EmployeeBuilder::create()
            ->withName('Tommy')
            ->withRole($serverRole)
            ->withWeeklyHours(20)
            ->withAvailabilityRules([
                // Only works Fri-Sun (teacher Mon-Thu)
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-24 12am') // Monday
                    ->withDuration('24 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-25 12am') // Tuesday
                    ->withDuration('24 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-26 12am') // Wednesday
                    ->withDuration('24 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-27 12am') // Thursday
                    ->withDuration('24 hours')
                    ->withFrequency('1 week')
                    ->build(),
            ])
            ->build();
    }

    /**
     * creates a part time (25 hr/week) server/bartender who is unavailable after 8pm and entirely unavailable on weekends
     */
    public static function createChris(Role $serverRole, Role $bartenderRole)
    {
        return EmployeeBuilder::create()
            ->withName('Chris')
            ->withRoles($serverRole, $bartenderRole)
            ->withWeeklyHours(25)
            ->withAvailabilityRules([
                // Unavailable after 8 PM any day
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-24 8pm') // Monday
                    ->withDuration('4 hours')
                    ->withFrequency('1 day')
                    ->build(),
                // Unavailable weekends (Sat-Sun)
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-29 12am') // Saturday
                    ->withDuration('48 hours')
                    ->withFrequency('1 week')
                    ->build(),
            ])
            ->build();
    }

    /**
     * creates a part time (15 hr/week) bartender who only works fridays and saturdays after 6pm
     */
    public static function createMaya(Role $bartenderRole)
    {
        return EmployeeBuilder::create()
            ->withName('Maya')
            ->withRole($bartenderRole)
            ->withWeeklyHours(15)
            ->withAvailabilityRules([
                // Only works Fri/Sat nights - unavailable Sun-Thu
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-23 12am') // Sunday
                    ->withDuration('120 hours') // 5 days
                    ->withFrequency('1 week')
                    ->build(),
                // Unavailable Fri-Sat before 6 PM
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-28 12am') // Friday
                    ->withDuration('18 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-29 12am') // Saturday
                    ->withDuration('18 hours')
                    ->withFrequency('1 week')
                    ->build(),
            ])
            ->build();
    }

    /**
     * creates a part time (30 hr/week) server/bartender who is unavailable before 8pm and from 3-6pm on weekdays
     */
    public static function createDavid(Role $serverRole, Role $bartenderRole)
    {
        return EmployeeBuilder::create()
            ->withName('David')
            ->withRoles($serverRole, $bartenderRole)
            ->withWeeklyHours(30)
            ->withAvailabilityRules([
                // Unavailable before 8 AM (every day)
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-24 12am') // Monday
                    ->withDuration('8 hours')
                    ->withFrequency('1 day')
                    ->build(),
                // Unavailable 3-6 PM for school pickup (Mon-Fri)
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-24 3pm') // Monday
                    ->withDuration('3 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-25 3pm') // Tuesday
                    ->withDuration('3 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-26 3pm') // Wednesday
                    ->withDuration('3 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-27 3pm') // Thursday
                    ->withDuration('3 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-28 3pm') // Friday
                    ->withDuration('3 hours')
                    ->withFrequency('1 week')
                    ->build(),
            ])
            ->build();
    }

    /**
     * creates a part time (15 hr/week) server/bartender who is oly available on weekends before 2pm
     */
    public static function createLisa(Role $serverRole, Role $bartenderRole)
    {
        return EmployeeBuilder::create()
            ->withName('Lisa')
            ->withRoles($serverRole, $bartenderRole)
            ->withWeeklyHours(15)
            ->withAvailabilityRules([
                // Only works Sat/Sun brunches - unavailable Mon-Fri
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-24 12am') // Monday
                    ->withDuration('120 hours') // 5 days
                    ->withFrequency('1 week')
                    ->build(),
                // Unavailable Sat/Sun after 2 PM
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-29 2pm') // Saturday
                    ->withDuration('10 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-30 2pm') // Sunday
                    ->withDuration('10 hours')
                    ->withFrequency('1 week')
                    ->build(),
            ])
            ->build();
    }

    /**
     * creates a part time (12 hr/week) server who is only available on weekdays after 5pm
     */
    public static function createJordan(Role $serverRole)
    {
        return EmployeeBuilder::create()
            ->withName('Jordan')
            ->withRole($serverRole)
            ->withWeeklyHours(12)
            ->withAvailabilityRules([
                // Unavailable before 5 PM any day
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-24 12am') // Monday
                    ->withDuration('17 hours')
                    ->withFrequency('1 day')
                    ->build(),
                // Unavailable weekends (Sat-Sun)
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-29 12am') // Saturday
                    ->withDuration('48 hours')
                    ->withFrequency('1 week')
                    ->build(),
            ])
            ->build();
    }

    /**
     * creates a part time (18 hr/week) server who is unavailable before 3pm on weekdays and after 10pm Mon-Thu
     */
    public static function createEmma(Role $serverRole)
    {
        return EmployeeBuilder::create()
            ->withName('Emma')
            ->withRole($serverRole)
            ->withWeeklyHours(18)
            ->withAvailabilityRules([
                // Unavailable before 3 PM Mon-Fri (school)
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-24 12am') // Monday
                    ->withDuration('15 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-25 12am') // Tuesday
                    ->withDuration('15 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-26 12am') // Wednesday
                    ->withDuration('15 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-27 12am') // Thursday
                    ->withDuration('15 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-28 12am') // Friday
                    ->withDuration('15 hours')
                    ->withFrequency('1 week')
                    ->build(),
                // Cannot work past 10 PM on school nights (Mon-Thu)
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-24 10pm') // Monday
                    ->withDuration('2 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-25 10pm') // Tuesday
                    ->withDuration('2 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-26 10pm') // Wednesday
                    ->withDuration('2 hours')
                    ->withFrequency('1 week')
                    ->build(),
                AvailabilityRuleBuilder::create()
                    ->from('2025-11-27 10pm') // Thursday
                    ->withDuration('2 hours')
                    ->withFrequency('1 week')
                    ->build(),
            ])
            ->build();
    }

    /**
     * creates a part time (10 hr/week) server/bartender who is always available
     */
    public static function createRobert(Role $serverRole, Role $bartenderRole)
    {
        return EmployeeBuilder::create()
            ->withName('Robert')
            ->withRoles($serverRole, $bartenderRole)
            ->withWeeklyHours(10)
            ->alwaysAvailable() // Very flexible, just wants day shifts
            ->build();
    }
}
