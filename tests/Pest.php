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

use function Illuminate\Filesystem\join_paths;

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

function temp_path(string $suffix = ''): string
{
    $path = join_paths(base_path('tests'), 'temp', $suffix);

    ensureFolderExists($path);

    return $path;
}

function fixture_path(string $path): string
{
    return join_paths(base_path('fixtures'), $path);
}

function createZipWithFile(string $zipPath, string|array $entries): void
{
    $zip = new ZipArchive;

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException("Unable to create zip file at {$zipPath}");
    }

    if (is_array($entries)) {
        foreach ($entries as $entry => $contents) {
            if (is_int($entry)) {
                $entry = $contents;

                $contents = '';
            }

            $zip->addFromString($entry, $contents);
        }
    } else {
        $zip->addFromString($entries, '');
    }

    $zip->close();
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
