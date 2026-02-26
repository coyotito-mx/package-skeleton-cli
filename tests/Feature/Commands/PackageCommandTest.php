<?php

use App\Facades\Composer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework as PHPUnit;

use function Illuminate\Filesystem\join_paths;

function setupTestDirectory(): string
{
    $function = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

    $name = sha1($function);

    $path = join_paths(base_path('tests'), 'temp', $name);

    ensureFolderExists($path);

    return $path;
}

function moveFixture(string|array $fixtureName, string $destinationPath): void
{
    if (is_array($fixtureName)) {
        foreach ($fixtureName as $name) {
            moveFixture($name, $destinationPath);
        }

        return;
    }

    $sourcePath = join_paths(__DIR__, '..', '..', 'Fixtures', 'before', $fixtureName);

    copy($sourcePath, $destinationPath.DIRECTORY_SEPARATOR.str_replace('.stub', '', $fixtureName));
}

function assertFixtureEquals(string $fixtureName, string $actualPath): void
{
    $expectedPath = join_paths(__DIR__, '..', '..', 'Fixtures', 'after', $fixtureName);

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
    $expectedPath = join_paths(__DIR__, '..', '..', 'Fixtures', 'after', $fixtureName);

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

beforeAll(function () {
    Carbon::setTestNow('2026-01-01');
});

afterAll(function () {
    rmdir_recursive(base_path('tests'.DIRECTORY_SEPARATOR.'temp'));

    Carbon::setTestNow();
});

it('init package', function () {
    $testDirectory = setupTestDirectory();

    Composer::fake();
    moveFixture(['LICENSE.md.stub', 'composer.json.stub', 'package.json.stub'], $testDirectory);

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'namespace' => 'Acme\\Package',
        'description' => 'A package description',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
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
    assertFixtureEquals('package.json.stub', join_paths($testDirectory, 'package.json'));
});

it('init package using namespace', function () {
    Composer::fake();

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'description' => 'A package description',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        '--path' => setupTestDirectory(),
    ])
        ->expectsQuestion('Enter the package namespace', 'Asciito\\Package')
        ->expectsConfirmation('Do you want to proceed with this configuration?', 'yes')
        ->expectsChoice('Which testing framework do you want to use?', 'pest', ['phpunit' => 'PHPUnit', 'pest' => 'Pest'])
        ->expectsPromptsIntro('Initializing package...')
        ->expectsPromptsOutro('Package [Asciito\\Package] initialized successfully!')
        ->assertSuccessful();
});

it('proceed without confirmation', function () {
    Composer::fake();

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'namespace' => 'Acme\\Package',
        'description' => 'A package description',
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
        'namespace' => 'Acme\\Package',
        'description' => 'A package description',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        '--proceed' => true,
        '--path' => setupTestDirectory(),
    ])
        ->expectsChoice('Which testing framework do you want to use?', 'pest', ['phpunit' => 'PHPUnit', 'pest' => 'Pest'])
        ->expectsPromptsAlert('Installing composer dependencies...')
        ->expectsPromptsOutro('Package [Acme\\Package] initialized successfully!')
        ->assertSuccessful();

    $composer->assertPackageInstalled('pestphp/pest', true);
});

it('skip composer dependencies installation', function () {
    $composer = Composer::fake();

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'namespace' => 'Acme\\Package',
        'description' => 'A package description',
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
        'namespace' => 'Acme\\Package',
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

it('uses git user/email for author by default', function () {
    Composer::fake();

    $testDirectory = setupTestDirectory();

    Process::fake([
        'git config --list' => Process::result(<<<TXT
        user.name=John Doe
        user.email=john@doe.com
        TXT),
    ]);

    moveFixture(['LICENSE.md.stub', 'composer-with_author.json.stub'], $testDirectory);

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'namespace' => 'Acme\\Package',
        'description' => 'A package description',
        '--no-install' => true,
        '--proceed' => true,
        '--path' => $testDirectory,
    ])
        ->expectsPromptsTable(
            ['Vendor', 'Package', 'Namespace', 'Description', 'Author', 'Email'],
            [['Acme', 'Package', 'Acme\\Package', 'A package description', 'John Doe', 'john@doe.com']]
        )
        ->expectsOutputToContain('Package [Acme\\Package] initialized successfully!')
        ->assertSuccessful();

    assertFixtureEquals('LICENSE.md.stub', join_paths($testDirectory, 'LICENSE.md'));
    assertFixtureEquals('composer-with_author.json.stub', join_paths($testDirectory, 'composer-with_author.json'));
});

test('invalid namespace', function () {
    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        'namespace' => 'An\\Invalid Namespace',
        'description' => 'A package description',
        '--no-install' => true,
        '--proceed' => true,
        '--path' => setupTestDirectory(),
    ])
        ->expectsPromptsError('Invalid namespace provided')
        ->assertFailed();
});

it('excludes custom paths when processing files', function () {
    $testDirectory = setupTestDirectory();

    moveFixture(['composer.json.stub', 'package.json.stub'], $testDirectory);

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'namespace' => 'Acme\\Package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        'description' => 'A package description',
        '--proceed' => true,
        '--no-install' => true,
        '--path' => $testDirectory,
        '--exclude' => ['composer.json', $excluded = join_paths($testDirectory, 'package.json')],
    ])
        ->expectsPromptsTable(
            ['Vendor', 'Package', 'Namespace', 'Description', 'Author', 'Email'],
            [['Acme', 'Package', 'Acme\\Package', 'A package description', 'John Doe', 'john@doe.com']]
        )
        ->expectsPromptsTable(
            ['Excluded Paths'],
            [[
                implode(PHP_EOL, [
                    '.git',
                    '.DS_Store',
                    'vendor',
                    'node_modules',
                    'composer.json',
                    $excluded,
                ]),
            ]]
        )
        ->expectsOutputToContain('Package [Acme\\Package] initialized successfully!')
        ->assertSuccessful();

    assertFixtureNotEquals('composer.json.stub', join_paths($testDirectory, 'composer.json'));
    assertFixtureNotEquals('package.json.stub', join_paths($testDirectory, 'package.json'));
});
