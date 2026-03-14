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

use App\Placeholders\BasePlaceholder;
use App\Placeholders\Modifiers\AcronymModifier;
use App\Placeholders\Modifiers\CamelModifier;
use App\Placeholders\Modifiers\KebabModifier;
use App\Placeholders\Modifiers\LowerModifier;
use App\Placeholders\Modifiers\PascalModifier;
use App\Placeholders\Modifiers\SlugModifier;
use App\Placeholders\Modifiers\SnakeModifier;
use App\Placeholders\Modifiers\StudlyModifier;
use App\Placeholders\Modifiers\UCFirstModifier;
use App\Placeholders\Modifiers\UpperModifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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

function getModifierDataset(string|array $modifier): Collection
{
    $modifiers = Arr::wrap($modifier);

    return collect([
        'camel' => [
            CamelModifier::class,
            'john doe',
            'johnDoe',
        ],
        'kebab' => [
            KebabModifier::class,
            'John Doe',
            'john-doe',
        ],
        'lower' => [
            LowerModifier::class,
            'John Doe',
            'john doe',
        ],
        'pascal' => [
            PascalModifier::class,
            'John Doe',
            'JohnDoe',
        ],
        'slug' => [
            SlugModifier::class,
            'John Doe',
            'john-doe',
        ],
        'snake' => [
            SnakeModifier::class,
            'John Doe',
            'john_doe',
        ],
        'studly' => [
            StudlyModifier::class,
            'John doe',
            'JohnDoe',
        ],
        'ucfirst' => [
            UCFirstModifier::class,
            'john Doe',
            'John doe',
        ],
        'upper' => [
            UpperModifier::class,
            'john doe',
            'JOHN DOE',
        ],
        'acronym' => [
            AcronymModifier::class,
            'Hewlett Packard',
            'HP',
        ]
    ])->only($modifiers);
}

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
    return join_paths(base_path('tests'), 'fixtures', $path);
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

/**
 * Create a Testing Placeholder class
 * 
 * @return class-string<BasePlaceholder>
 */
function createPlaceholderClass(string $placeholder, array $modifiers = []): string
{
    return (static function (string $placeholderName, array $modifiers = []): string {
        $classIdentifier = uniqid('TestingPlaceholder');

        $modifiers = implode(',', array_map(fn (string $modifier): string => "$modifier::class", $modifiers));

        $classDefinition = <<<PHP
        class $classIdentifier extends \App\Placeholders\BasePlaceholder
        {
            protected static function getDefaultModifiers(): array
            {
                return [$modifiers];
            }

            public static function getName(): string
            {
                return "$placeholderName";
            }
        }
        PHP;

        eval($classDefinition);

        return $classIdentifier;
    })($placeholder, $modifiers);
}

function createPlaceholder(string $placeholder, array $modifiers = []): BasePlaceholder
{
    $placeholderClass = createPlaceholderClass($placeholder);

    return new $placeholderClass($modifiers);
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
