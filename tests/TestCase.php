<?php

namespace Tests;

use LaravelZero\Framework\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public ?string $oldPath = null;

    protected function setUp(): void
    {
        parent::setUp();

        \Laravel\Prompts\Prompt::fallbackWhen(true);
    }
}
