<?php

use App\Facades\Composer;
use Illuminate\Support\Facades\Process;

use function Illuminate\Filesystem\join_paths;

function setupTestDirectory(): string
{
    $function = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

    $name = sha1($function);

    $path = join_paths(base_path('tests'), 'temp', $name);

    ensureFolderExists($path);

    return $path;
}

beforeAll(function () {
    Carbon::setTestNow('2026-01-01');
});

afterAll(function () {
    rmdir_recursive(base_path('tests'.DIRECTORY_SEPARATOR.'temp'));

    Carbon::setTestNow();
});

it('init package', function () {
    Composer::fake();
    $testDirectory = setupTestDirectory();

    moveFixture(['LICENSE.md.stub', 'composer.json.stub'], $testDirectory);

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        'description' => 'A package description',
        '--path' => $testDirectory,
    ])
        ->expectsPromptsIntro('Initializing package...')
        ->expectsPromptsTable(
            ['Vendor', 'Package', 'Namespace', 'Description', 'Author', 'Email'],
            [['Acme', 'Package', 'Acme\\Package', 'A package description', 'John Doe', 'john@doe.com']]
        )
        ->expectsConfirmation('Do you want to proceed with this configuration?', 'yes')
        ->expectsChoice('Which testing framework do you want to use?', 'pest', ['phpunit' => 'PHPUnit', 'pest' => 'Pest'])
        ->expectsPromptsAlert('Installing composer dependencies...')
        ->expectsPromptsOutro('Package [Acme\\Package] initialized successfully!')
        ->assertSuccessful();

    assertFixtureEquals('LICENSE.md.stub', join_paths($testDirectory, 'LICENSE.md'));
    assertFixtureEquals('composer.json.stub', join_paths($testDirectory, 'composer.json'));
});

it('proceed without confirmation', function () {
    Composer::fake();

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        '--proceed' => true,
        '--path' => setupTestDirectory(),
    ])
        ->expectsPromptsIntro('Initializing package...')
        ->expectsChoice('Which testing framework do you want to use?', 'pest', ['phpunit' => 'PHPUnit', 'pest' => 'Pest'])
        ->expectsPromptsAlert('Installing composer dependencies...')
        ->expectsPromptsOutro('Package [Acme\\Package] initialized successfully!')
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
        '--path' => setupTestDirectory(),
    ])
        ->expectsChoice('Which testing framework do you want to use?', 'pest', ['phpunit' => 'PHPUnit', 'pest' => 'Pest'])
        ->expectsPromptsAlert('Installing composer dependencies...')
        ->expectsPromptsOutro('Package [Acme\\Package] initialized successfully!')
        ->assertSuccessful();

    $composer->assertDependencyInstalled('pestphp/pest');
});

it('skip composer dependencies installation', function () {
    $composer = Composer::fake();

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        '--proceed' => true,
        '--no-install' => true,
        '--path' => setupTestDirectory(),
    ])
        ->expectsPromptsWarning('Skip composer dependencies installation.')
        ->expectsOutputToContain('Package [Acme\\Package] initialized successfully!')
        ->assertSuccessful();

    $composer->assertNothingInstalled();
});

it('ask for confirmation before initializing package', function () {
    Composer::fake();

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        'description' => 'A package description',
        '--no-install' => true,
        '--path' => setupTestDirectory(),
    ])

        ->expectsPromptsTable(
            ['Vendor', 'Package', 'Namespace', 'Description', 'Author', 'Email'],
            [['Acme', 'Package', 'Acme\\Package', 'A package description', 'John Doe', 'john@doe.com']]
        )
        ->expectsConfirmation('Do you want to proceed with this configuration?', 'yes')
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
        'namespace' => 'Asciito\\Acme',
        '--proceed' => true,
        '--no-install' => true,
        '--path' => setupTestDirectory(),
    ])
        ->expectsPromptsOutro('Package [Asciito\\Acme] initialized successfully!')
        ->assertSuccessful();
});

it('uses git user/email for author by default', function () {
    Composer::fake();

    $testDirectory = setupTestDirectory();

    Process::fake([
        'git config --list --global *' => Process::result(
            json_encode([
                'user.name' => 'John Doe',
                'user.email' => 'john@doe.com',
            ]),
        ),
    ]);

    moveFixture(['LICENSE.md.stub', 'composer-with_author.json.stub'], $testDirectory);

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'description' => 'A package description',
        '--no-install' => true,
        '--path' => $testDirectory,
    ])
        ->expectsPromptsTable(
            ['Vendor', 'Package', 'Namespace', 'Description', 'Author', 'Email'],
            [['Acme', 'Package', 'Acme\\Package', 'A package description', 'John Doe', 'john@doe.com']]
        )
        ->expectsConfirmation('Do you want to proceed with this configuration?', 'yes')
        ->expectsOutputToContain('Package [Acme\\Package] initialized successfully!')
        ->assertSuccessful();

    assertFixtureEquals('LICENSE.md.stub', join_paths($testDirectory, 'LICENSE.md'));
    assertFixtureEquals('composer-with_author.json.stub', join_paths($testDirectory, 'composer-with_author.json'));
});

test('invalid namespace', function () {
    artisan('init', [
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        'namespace' => 'An\\Invalid Namespace',
        '--no-install' => true,
        '--proceed' => true,
        '--path' => setupTestDirectory(),
    ])
        ->expectsQuestion('Enter the package vendor name', 'acme')
        ->expectsQuestion('Enter the package name', 'package')
        ->expectsPromptsError('Invalid namespace provided')
        ->assertFailed();
});
