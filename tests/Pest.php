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

function test_path(?string $path = null): string
{
    return base_path('tests'.($path ? DIRECTORY_SEPARATOR.$path : ''));
}

function sandbox_path(?string $path = null): string
{
    return test_path('sandbox'.($path ? DIRECTORY_SEPARATOR.$path : ''));
}

/**
 * @throw InvalidArgumentException if the given path is a file.
 */
function rmdir_recursive(string $path): void
{
    if (! file_exists($path)) {
        return;
    }

    if (is_file($path)) {
        throw new InvalidArgumentException("The given path is a file: $path");
    }

    foreach (scandir($path) as $file) {
        if (in_array($file, ['.', '..'])) {
            continue;
        }

        $file = $path.DIRECTORY_SEPARATOR.$file;

        if (is_dir($file)) {
            rmdir_recursive($file);
        } else {
            unlink($file);
        }
    }

    rmdir($path);
}
