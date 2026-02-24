<?php

use App\Facades\Composer;
use Illuminate\Support\Facades\Process;

function setupTestDirectory(): string
{
    $function = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

    $name = sha1($function);

    $path = base_path('tests'.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR.$name);

    ensureFolderExists($path);

    return $path;
}

afterAll(fn () => rmdir_recursive(base_path('tests'.DIRECTORY_SEPARATOR.'temp')));

it('init package', function () {
    Composer::fake();

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        '--description' => 'A package description',
        '--proceed' => true,
    ])
        ->expectsPromptsIntro('Initializing package...')
        ->expectsPromptsTable(
            ['Vendor', 'Package', 'Namespace', 'Description', 'Author', 'Email'],
            [['Acme', 'Package', 'Acme\\Package', 'A package description', 'John Doe', 'john@doe.com']]
        )
        ->expectsChoice('Which testing framework do you want to use?', 'pest', ['phpunit' => 'PHPUnit', 'pest' => 'Pest'])
        ->expectsPromptsAlert('Installing composer dependencies...')
        ->expectsPromptsOutro('Package [Acme\\Package] initialized successfully!')
        ->assertSuccessful();
});

it('ask for confirmation before initializing package', function () {
    Composer::fake();

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        '--description' => 'A package description',
    ])

        ->expectsPromptsTable(
            ['Vendor', 'Package', 'Namespace', 'Description', 'Author', 'Email'],
            [['Acme', 'Package', 'Acme\\Package', 'A package description', 'John Doe', 'john@doe.com']]
        )
        ->expectsConfirmation('Do you want to proceed with this configuration?', 'yes')
        ->expectsChoice('Which testing framework do you want to use?', 'pest', ['phpunit' => 'PHPUnit', 'pest' => 'Pest'])
        ->expectsOutputToContain('Package [Acme\\Package] initialized successfully!')
        ->assertSuccessful();
});

it('init package using namespace', function () {
    Composer::fake();

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        '--namespace' => 'Asciito\\Acme',
        '--proceed' => true,
    ])
        ->expectsChoice('Which testing framework do you want to use?', 'pest', ['phpunit' => 'PHPUnit', 'pest' => 'Pest'])
        ->expectsPromptsOutro('Package [Asciito\\Acme] initialized successfully!')
        ->assertSuccessful();
});

it('install package composer dependencies', function () {
    $composer = Composer::fake();

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        '--proceed' => true,
    ])
        ->expectsChoice('Which testing framework do you want to use?', 'pest', ['phpunit' => 'PHPUnit', 'pest' => 'Pest'])
        ->expectsPromptsAlert('Installing composer dependencies...')
        ->expectsPromptsOutro('Package [Acme\\Package] initialized successfully!')
        ->assertSuccessful();

    $composer->assertDependencyInstalled('pestphp/pest');
});

test('skip composer dependencies installation', function () {
    $composer = Composer::fake();

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        '--proceed' => true,
        '--no-install' => true,
    ])
        ->expectsPromptsWarning('Skip composer dependencies installation.')
        ->expectsOutputToContain('Package [Acme\\Package] initialized successfully!')
        ->assertSuccessful();

    $composer->assertNothingInstalled();
});

it('uses git user/email for author by default', function () {
    Composer::fake();

    Process::fake([
        'git config --list --global *' => Process::result(
            json_encode([
                'user.name' => 'Asciito',
                'user.email' => 'hello@asciito.com',
            ]),
        ),
    ]);

    artisan('init', ['vendor' => 'acme', 'package' => 'package', '--proceed' => true])
        ->expectsPromptsTable(
            ['Vendor', 'Package', 'Namespace', 'Author', 'Email'],
            [['Acme', 'Package', 'Acme\\Package', 'Asciito', 'hello@asciito.com']]
        )
        ->expectsChoice('Which testing framework do you want to use?', 'pest', ['phpunit' => 'PHPUnit', 'pest' => 'Pest'])
        ->expectsOutputToContain('Package [Acme\\Package] initialized successfully!')
        ->assertSuccessful();
});
