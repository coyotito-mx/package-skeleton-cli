<?php

use App\Facades\Composer;
use Illuminate\Process\FakeProcessResult;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework as PHPUnit;

use function App\Helpers\rmdir_recursive;
use function Illuminate\Filesystem\join_paths;
use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertFileExists;

function setupTestDirectory(): string
{
    $testDirectory = temp_path('package-init-command-tests').DIRECTORY_SEPARATOR.uniqid();

    ensureFolderExists($testDirectory);

    return $testDirectory;
}

function moveFixture(string|array $fixtureName, string $destinationPath): void
{
    if (is_array($fixtureName)) {
        foreach ($fixtureName as $name) {
            moveFixture($name, $destinationPath);
        }

        return;
    }

    $sourcePath = fixture_path('before'.DIRECTORY_SEPARATOR.$fixtureName);

    copy($sourcePath, $destinationPath.DIRECTORY_SEPARATOR.str_replace('.stub', '', $fixtureName));
}

function assertFixtureEquals(string $fixtureName, string $actualPath): void
{
    $expectedPath = fixture_path('after'.DIRECTORY_SEPARATOR.$fixtureName);

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
    $expectedPath = fixture_path('after'.DIRECTORY_SEPARATOR.$fixtureName);

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

beforeAll(function (): void {
    Carbon::setTestNow('2026-01-01');
});

afterAll(function (): void {
    rmdir_recursive(temp_path('package-init-command-tests'));

    Carbon::setTestNow();
});

it('init package', function (): void {
    $testDirectory = setupTestDirectory();

    Composer::fake();
    moveFixture(['LICENSE.md.stub', 'composer.json.stub', 'package.json.stub'], $testDirectory);

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'namespace' => 'Acme\\Package',
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
        ->expectsConfirmation('Do you want to remove this CLI now?', 'no')
        ->assertSuccessful();

    assertFixtureEquals('LICENSE.md.stub', join_paths($testDirectory, 'LICENSE.md'));
    assertFixtureEquals('composer.json.stub', join_paths($testDirectory, 'composer.json'));
    assertFixtureEquals('package.json.stub', join_paths($testDirectory, 'package.json'));
});

it('init package using namespace', function (): void {
    Composer::fake();
    $testDirectory = setupTestDirectory();

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        'description' => 'A package description',
        '--path' => $testDirectory,
    ])
        ->expectsQuestion('Enter the package namespace', 'Asciito\\Package')
        ->expectsConfirmation('Do you want to proceed with this configuration?', 'yes')
        ->expectsConfirmation('No LICENSE file found. Do you want to create one with the MIT license?', 'yes')
        ->expectsChoice('Which testing framework do you want to use?', 'pest', ['phpunit' => 'PHPUnit', 'pest' => 'Pest'])
        ->expectsPromptsIntro('Initializing package...')
        ->expectsPromptsOutro('Package [Asciito\\Package] initialized successfully!')
        ->expectsConfirmation('Do you want to remove this CLI now?', 'no')
        ->assertSuccessful();

    assertFileExists(join_paths($testDirectory, 'LICENSE.md'));
});

it('proceed without confirmation', function (): void {
    Composer::fake();

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'namespace' => 'Acme\\Package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        'description' => 'A package description',
        '--proceed' => true,
        '--path' => setupTestDirectory(),
    ])
        ->expectsPromptsIntro('Initializing package...')
        ->expectsConfirmation('No LICENSE file found. Do you want to create one with the MIT license?', 'yes')
        ->expectsChoice('Which testing framework do you want to use?', 'pest', ['phpunit' => 'PHPUnit', 'pest' => 'Pest'])
        ->expectsPromptsAlert('Installing composer dependencies...')
        ->expectsPromptsOutro('Package [Acme\\Package] initialized successfully!')
        ->expectsConfirmation('Do you want to remove this CLI now?', 'no')
        ->assertSuccessful();
});

it('skip license creation', function (): void {
    $testDirectory = setupTestDirectory();

    Composer::fake();

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'namespace' => 'Acme\\Package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        'description' => 'A package description',
        '--proceed' => true,
        '--skip-license' => true,
        '--path' => $testDirectory,
    ])
        ->expectsPromptsIntro('Initializing package...')
        ->expectsChoice('Which testing framework do you want to use?', 'pest', ['phpunit' => 'PHPUnit', 'pest' => 'Pest'])
        ->expectsPromptsAlert('Installing composer dependencies...')
        ->expectsPromptsOutro('Package [Acme\\Package] initialized successfully!')
        ->expectsConfirmation('Do you want to remove this CLI now?', 'no')
        ->assertSuccessful();

    assertFileDoesNotExist(join_paths($testDirectory, 'LICENSE.md'), 'License file should not be created');
});

it('install package composer dependencies', function (): void {
    $composer = Composer::fake();

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'namespace' => 'Acme\\Package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        'description' => 'A package description',
        '--proceed' => true,
        '--skip-license' => true,
        '--path' => setupTestDirectory(),
    ])
        ->expectsChoice('Which testing framework do you want to use?', 'pest', ['phpunit' => 'PHPUnit', 'pest' => 'Pest'])
        ->expectsPromptsAlert('Installing composer dependencies...')
        ->expectsPromptsOutro('Package [Acme\\Package] initialized successfully!')
        ->expectsConfirmation('Do you want to remove this CLI now?', 'no')
        ->assertSuccessful();

    $composer->assertPackageInstalled('pestphp/pest', true);
});

it('skip composer dependencies installation', function (): void {
    $composer = Composer::fake();

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'namespace' => 'Acme\\Package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        'description' => 'A package description',
        '--proceed' => true,
        '--no-install' => true,
        '--skip-license' => true,
        '--path' => setupTestDirectory(),
    ])
        ->expectsPromptsWarning('Skip composer dependencies installation.')
        ->expectsOutputToContain('Package [Acme\\Package] initialized successfully!')
        ->expectsConfirmation('Do you want to remove this CLI now?', 'no')
        ->assertSuccessful();

    $composer->assertNothingInstalled();
});

it('ask for confirmation before initializing package', function (): void {
    Composer::fake();

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'namespace' => 'Acme\\Package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        'description' => 'A package description',
        '--no-install' => true,
        '--skip-license' => true,
        '--path' => setupTestDirectory(),
    ])
        ->expectsPromptsTable(
            ['Vendor', 'Package', 'Namespace', 'Description', 'Author', 'Email'],
            [['Acme', 'Package', 'Acme\\Package', 'A package description', 'John Doe', 'john@doe.com']]
        )
        ->expectsConfirmation('Do you want to proceed with this configuration?', 'yes')
        ->expectsOutputToContain('Package [Acme\\Package] initialized successfully!')
        ->expectsConfirmation('Do you want to remove this CLI now?', 'no')
        ->assertSuccessful();
});

it('uses git user/email for author by default', function (): void {
    Composer::fake();

    $testDirectory = setupTestDirectory();

    Process::fake([
        'git config --list' => Process::result(<<<'TXT'
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
        ->expectsConfirmation('Do you want to remove this CLI now?', 'no')
        ->assertSuccessful();

    assertFixtureEquals('LICENSE.md.stub', join_paths($testDirectory, 'LICENSE.md'));
    assertFixtureEquals('composer-with_author.json.stub', join_paths($testDirectory, 'composer-with_author.json'));
});

test('invalid namespace', function (): void {
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

it('excludes custom paths when processing files', function (): void {
    $testDirectory = setupTestDirectory();

    moveFixture(['composer.json.stub', 'package.json.stub', 'LICENSE.md.stub'], $testDirectory);

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
        ->expectsConfirmation('Do you want to remove this CLI now?', 'no')
        ->assertSuccessful();

    assertFixtureNotEquals('composer.json.stub', join_paths($testDirectory, 'composer.json'));
    assertFixtureNotEquals('package.json.stub', join_paths($testDirectory, 'package.json'));
    assertFixtureEquals('LICENSE.md.stub', join_paths($testDirectory, 'LICENSE.md'));
});

it('bootstrap vanilla skeleton', function (): void {
    $testDirectory = setupTestDirectory();

    Process::fake([
        function (PendingProcess $process): FakeProcessResult {
            $command = is_array($process->command) ? $process->command : [(string) $process->command];
            $outputPath = $command[array_search('-o', $command, true) + 1] ?? null;

            if (is_string($outputPath)) {
                createZipWithFile($outputPath, [
                    'package-skeleton-main/composer.json' => json_encode([
                        'name' => '{{namespace|reverse,lower}}',
                        'description' => '{{description}}',
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                ]);
            }

            return Process::result();
        },
    ]);

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'namespace' => 'Acme\\Package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        'description' => 'A package description',
        '--no-install' => true,
        '--proceed' => true,
        '--skip-license' => true,
        '--path' => $testDirectory,
        '--bootstrap' => 'vanilla',
    ])
        ->expectsPromptsAlert('Bootstrapping package using skeleton: vanilla...')
        ->doesntExpectOutput('Proceeding with bootstrapping and overwriting existing files...')
        ->doesntExpectOutput('Some files could not be moved during the bootstrapping process.')
        ->expectsPromptsTable(
            ['Vendor', 'Package', 'Namespace', 'Description', 'Author', 'Email'],
            [['Acme', 'Package', 'Acme\\Package', 'A package description', 'John Doe', 'john@doe.com']]
        )
        ->expectsOutputToContain('Package [Acme\\Package] initialized successfully!')
        ->expectsConfirmation('Do you want to remove this CLI now?', 'no')
        ->assertSuccessful();

    expect(File::files($testDirectory))
        ->not->toBeEmpty()
        ->and(join_paths($testDirectory, 'composer.json'))
        ->toBeFile()
        ->and(file_get_contents(join_paths($testDirectory, 'composer.json')))
        ->toBe(json_encode([
            'name' => 'acme/package',
            'description' => 'A package description',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
});

it('cannot bootstrap skeleton with existing files', function () {
    $testDirectory = setupTestDirectory();

    File::put(join_paths($testDirectory, 'existing.txt'), 'This file already exists');

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'namespace' => 'Acme\\Package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        'description' => 'A package description',
        '--bootstrap' => 'vanilla',
        '--no-install' => true,
        '--proceed' => true,
        '--skip-license' => true,
        '--path' => $testDirectory,
    ])
        ->expectsConfirmation('The target directory is not empty. Do you want to proceed and overwrite existing files?')
        ->expectsPromptsAlert('Bootstrapping package using skeleton: vanilla...')
        ->doesntExpectOutput('Proceeding with bootstrapping and overwriting existing files...')
        ->doesntExpectOutput('Some files could not be moved during the bootstrapping process.')
        ->expectsPromptsError('Package bootstrapping cancelled by user due to non-empty target directory.')
        ->assertFailed();
});

it('bootstraps existing directory when force flag is provided', function (): void {
    $testDirectory = setupTestDirectory();

    File::put(join_paths($testDirectory, 'existing.txt'), 'This file already exists');

    Process::fake([
        function (PendingProcess $process): FakeProcessResult {
            $command = is_array($process->command) ? $process->command : [(string) $process->command];
            $outputPath = $command[array_search('-o', $command, true) + 1] ?? null;

            if (is_string($outputPath)) {
                createZipWithFile($outputPath, [
                    'package-skeleton-main/composer.json' => json_encode([
                        'name' => '{{namespace|reverse,lower}}',
                        'description' => '{{description}}',
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                ]);
            }

            return Process::result();
        },
    ]);

    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'namespace' => 'Acme\\Package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        'description' => 'A package description',
        '--bootstrap' => 'vanilla',
        '--force' => true,
        '--no-install' => true,
        '--proceed' => true,
        '--skip-license' => true,
        '--path' => $testDirectory,
    ])
        ->expectsPromptsAlert('Bootstrapping package using skeleton: vanilla...')
        ->expectsPromptsAlert('Proceeding with bootstrapping and overwriting existing files...')
        ->expectsOutputToContain('Package [Acme\\Package] initialized successfully!')
        ->expectsConfirmation('Do you want to remove this CLI now?', 'no')
        ->assertSuccessful();

    expect(join_paths($testDirectory, 'composer.json'))->toBeFile();
});

it('fails when bootstrap skeleton type is unsupported', function (): void {
    artisan('init', [
        'vendor' => 'acme',
        'package' => 'package',
        'namespace' => 'Acme\\Package',
        'author' => 'John Doe',
        'email' => 'john@doe.com',
        'description' => 'A package description',
        '--bootstrap' => 'unknown',
        '--no-install' => true,
        '--proceed' => true,
        '--skip-license' => true,
        '--path' => setupTestDirectory(),
    ])
        ->expectsPromptsError('Unsupported skeleton type: unknown')
        ->assertFailed();
});
