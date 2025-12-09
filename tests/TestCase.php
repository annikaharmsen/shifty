<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure foreign keys are enabled for SQLite
        if ($this->app['db']->connection()->getDriverName() === 'sqlite') {
            $this->app['db']->statement('PRAGMA foreign_keys=ON');
        }
    }
}
