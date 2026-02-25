<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use Illuminate\Testing\PendingCommand;
use PHPUnit\Framework as PHPUnit;

uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function ensureFolderExists(string $folder): void
{
    if (! file_exists($folder)) {
        mkdir($folder, 0755, true);
    }
}

if (! function_exists('artisan')) {
    /**
     * Helper function to interact with the Artisan console for testing
     *
     * @param  string  $command  The command to execute
     * @param  array<string, mixed>  $parameters  Parameters to add to the command
     */
    function artisan(string $command, array $parameters = []): PendingCommand
    {
        return test()->artisan($command, $parameters);
    }
}

function moveFixture(string|array $fixtureName, string $destinationPath): void
{
    if (is_array($fixtureName)) {
        foreach ($fixtureName as $name) {
            moveFixture($name, $destinationPath);
        }

        return;
    }

    $sourcePath = __DIR__.'/Fixtures/before'.DIRECTORY_SEPARATOR.$fixtureName;

    copy($sourcePath, $destinationPath.DIRECTORY_SEPARATOR.str_replace('.stub', '', $fixtureName));
}

function assertFixtureEquals(string $fixtureName, string $actualPath): void
{
    $expectedPath = __DIR__.'/Fixtures/after'.DIRECTORY_SEPARATOR.$fixtureName;

    if (! file_exists($expectedPath)) {
        throw new InvalidArgumentException("Expected fixture file does not exist: {$expectedPath}");
    }

    if (! file_exists($actualPath)) {
        throw new InvalidArgumentException("Actual file does not exist: {$actualPath}");
    }

    $expectedContent = file_get_contents($expectedPath);
    $actualContent = file_get_contents($actualPath);

    PHPUnit\Assert::assertSame($expectedContent, $actualContent, "Fixture content does not match actual content for: $actualPath");
}

function assertFixtureNotEquals(string $fixtureName, string $actualPath): void
{
    $expectedPath = __DIR__.'/Fixtures/after'.DIRECTORY_SEPARATOR.$fixtureName;

    if (! file_exists($expectedPath)) {
        throw new InvalidArgumentException("Expected fixture file does not exist: {$expectedPath}");
    }

    if (! file_exists($actualPath)) {
        throw new InvalidArgumentException("Actual file does not exist: {$actualPath}");
    }

    $expectedContent = file_get_contents($expectedPath);
    $actualContent = file_get_contents($actualPath);

    PHPUnit\Assert::assertNotSame($expectedContent, $actualContent, "Fixture content should not match actual content for: $actualPath");
}
