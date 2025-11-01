<?php

use App\Commands\Exceptions\CliNotBuiltException;
use App\Facades\Composer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

use function App\Helpers\mkdir;
use function App\Helpers\rmdir_recursive;

beforeEach(function () {
    Process::fake([
        "'git' '--version'" => Process::result(),
        "'git' 'config' 'user.name'" => 'asciito',
        "'git' 'config' 'user.email'" => 'test@test.com',
    ]);

    rmdir_recursive(sandbox_path());
    mkdir(sandbox_path());

    $this->oldPath = getcwd();

    chdir(sandbox_path());
});

afterEach(function () {
    chdir($this->oldPath);

    rmdir_recursive(sandbox_path());
});

it('change command context', function () {
    expect(getcwd())
        ->toBe(sandbox_path())
        ->and($this->oldPath)
        ->toBe(base_path());
});

it('can init the package', function () {
    File::put(
        sandbox_path('composer.json'),
        <<<'EOF'
        {
            "name": "{{namespace|slug,lower,reverse}}",
            "description": "{{description|ucfirst}}",
            "type": "{{type}}",
            "version": "{{version}}",
            "minimum-stability": "{{minimum-stability}}",
            "license": "{{license}}",
            "authors": [
                {
                    "name": "{{author}}",
                    "email": "{{email}}"
                }
            ],
            "require": {
                "php": "^7.3",
                "{{vendor}}/support": "dev-main",
                "{{vendor}}/console": "dev-main",
                "{{vendor}}/sso-connector": "dev-main",
                "spatie/laravel-permission": "^5.0"
            }
        }
        EOF
    );

    $this->artisan('init', ['--no-self-delete' => true, '--skip-license-generation' => true])
        ->expectsQuestion('What is the vendor name?', 'Acme')
        ->expectsQuestion('What is the package name?', 'Package')
        ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.')
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsConfirmation('Do you want to install the dependencies?')
        ->expectsOutput('Self-deleting skipped')
        ->assertSuccessful();

    expect(sandbox_path('composer.json'))
        ->toBeFileContent(
            <<<'EOF'
            {
                "name": "acme/package",
                "description": "Lorem ipsum dolor sit amet consectetur adipisicing elit.",
                "type": "library",
                "version": "0.0.1",
                "minimum-stability": "dev",
                "license": "MIT",
                "authors": [
                    {
                        "name": "Acme",
                        "email": "test@test.com"
                    }
                ],
                "require": {
                    "php": "^7.3",
                    "acme/support": "dev-main",
                    "acme/console": "dev-main",
                    "acme/sso-connector": "dev-main",
                    "spatie/laravel-permission": "^5.0"
                }
            }
            EOF
        );
});

it('failed to install dependencies', function () {
    Composer::partialMock()
        ->expects('findComposerFile')
        ->andThrow(RuntimeException::class);

    $this->artisan('init', ['--no-self-delete' => true, '--skip-license-generation' => true])
        ->expectsQuestion('What is the vendor name?', 'Acme')
        ->expectsQuestion('What is the package name?', 'Package')
        ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.')
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsQuestion('Do you want to install the dependencies?', 'yes');
})->throws(RuntimeException::class);

it('can restart configure', function () {
    File::put(
        sandbox_path('README.MD'),
        <<<'README'
        # {{package|ucfirst}}

        {{description|ucfirst}}

        ## Installation

        You can install the package via composer:

        ```bash
        composer require {{namespace|lower,reverse}}
        ```

        ## Usage

        ```php
        $package = new {{namespace}}\SomeClass();

        echo $package->echoPhrase('Hello, World!');
        ```

        ## Testing

        ```bash
        composer test
        ```

        ## Changelog

        Please see CHANGELOG for more information on what has changed recently.

        ## Contributing

        Please see CONTRIBUTING for details.

        ## Security

        If you discover any security related issues, please email {{author}} instead of using the issue tracker.

        ## Credits

        - {{author}} - Initial work
        - All Contributors
        README
    );

    $this->artisan('init', ['--no-self-delete' => true, '--skip-license-generation' => true])
        ->expectsQuestion('What is the vendor name?', 'Acme')
        ->expectsQuestion('What is the package name?', 'Package')
        ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.')
        ->expectsConfirmation('Do you want to use this configuration?')
        ->expectsQuestion('What is the vendor name?', 'Asciito')
        ->expectsQuestion('What is the package name?', 'Package')
        ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor it set adisicing elit.')
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsConfirmation('Do you want to install the dependencies?')
        ->expectsOutput('Self-deleting skipped')
        ->doesntExpectOutput('Self-deleting the CLI...')
        ->doesntExpectOutput('Bye bye 👋')
        ->assertSuccessful();

    expect(sandbox_path('README.MD'))
        ->toBeFileContent(
            <<<'README'
            # Package

            Lorem ipsum dolor it set adisicing elit.

            ## Installation

            You can install the package via composer:

            ```bash
            composer require asciito/package
            ```

            ## Usage

            ```php
            $package = new Asciito\Package\SomeClass();

            echo $package->echoPhrase('Hello, World!');
            ```

            ## Testing

            ```bash
            composer test
            ```

            ## Changelog

            Please see CHANGELOG for more information on what has changed recently.

            ## Contributing

            Please see CONTRIBUTING for details.

            ## Security

            If you discover any security related issues, please email Asciito instead of using the issue tracker.

            ## Credits

            - Asciito - Initial work
            - All Contributors
            README
        );

    File::delete(sandbox_path('README.MD'));
});

it('does not init package', function () {
    $vendor = 'Acme';
    $package = 'Package';
    $description = 'Lorem ipsum dolor sit amet consectetur adipisicing elit.';

    $this->artisan('init', ['--no-self-delete', '--skip-license-generation'])
        ->expectsQuestion('What is the vendor name?', $vendor)
        ->expectsQuestion('What is the package name?', $package)
        ->expectsQuestion('What is the package description?', $description)
        ->expectsConfirmation('Do you want to use this configuration?')
        ->expectsQuestion('What is the vendor name?', $vendor)
        ->expectsQuestion('What is the package name?', $package)
        ->expectsQuestion('What is the package description?', $description)
        ->expectsConfirmation('Do you want to use this configuration?')
        ->expectsQuestion('What is the vendor name?', $vendor)
        ->expectsQuestion('What is the package name?', $package)
        ->expectsQuestion('What is the package description?', $description)
        ->expectsConfirmation('Do you want to use this configuration?')
        ->expectsOutput('You did not confirm the package initialization.')
        ->assertFailed();
});

it('can init the package with custom values', function () {
    mkdir(sandbox_path('src'));

    File::put(
        sandbox_path('src/SomeClass.php'),
        <<<'PHP'
        <?php

        declare(strict_types=1);

        namespace {{namespace}};

        /**
        * This is the SomeClass class for testing
        *
        * @package {{namespace}}
        * @author {{author}}
        * @email {{email}}
        * @version {{version}}
        * @license {{license}}
        */
        class SomeClass
        {
            public function echoPhrase(string $phrase): string
            {
                return $phrase;
            }

            public function echoHello(): string
            {
                return 'Hi, I\'m the author {{author|title}}!';
            }
        }
        PHP
    );

    $this->artisan('init', [
        '--author' => 'John Doe',
        '--package-version' => '1.0.0',
        '--minimum-stability' => 'stable',
        '--type' => 'project',
        '--no-self-delete' => true,
        '--skip-license-generation' => true,
    ])
        ->expectsQuestion('What is the vendor name?', 'Acme')
        ->expectsQuestion('What is the package name?', 'Package')
        ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.')
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsConfirmation('Do you want to install the dependencies?')
        ->expectsOutput('Self-deleting skipped')
        ->assertSuccessful();

    expect(sandbox_path('src/SomeClass.php'))
        ->toBeFileContent(
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Acme\Package;

            /**
            * This is the SomeClass class for testing
            *
            * @package Acme\Package
            * @author John Doe
            * @email test@test.com
            * @version 1.0.0
            * @license MIT
            */
            class SomeClass
            {
                public function echoPhrase(string $phrase): string
                {
                    return $phrase;
                }

                public function echoHello(): string
                {
                    return 'Hi, I\'m the author John Doe!';
                }
            }
            PHP
        );

    File::deleteDirectory(sandbox_path('src'));
});

it('exclude directory and avoid replacements', function () {
    mkdir(sandbox_path('src'));

    File::put(sandbox_path('src/SomeClass.php'), '');

    $this->artisan('init', [
        '--dir' => 'src',
        '--no-self-delete' => true,
        '--skip-license-generation' => true,
    ])
        ->expectsQuestion('What is the vendor name?', 'Acme')
        ->expectsQuestion('What is the package name?', 'Package')
        ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.')
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsConfirmation('Do you want to install the dependencies?')
        ->expectsOutput('Self-deleting skipped')
        ->assertSuccessful();

    expect(sandbox_path())
        ->not->toHaveFiles(dot: true)
        ->and(sandbox_path('src/SomeClass.php'))
        ->toBeFile()
        ->toBeFileContent('');
});

it('exclude files from being processed', function () {
    $ignore = <<<'EOF'
    ./{{vendor}}/{{package}}
    ./{{namespace|reverse,lower}}/vendor/bin/
    ./{{author|lower,slug}}.txt
    EOF;

    $editor = <<<'EOF'
    # Maintained by {{author|title}}

    root = true

    # {{namespace|ucfirst}} EditorConfig File
    [*]
    indent_style = space
    indent_size = 4
    charset = utf-8
    trim_trailing_whitespace = true
    insert_final_newline = true
    EOF;

    $acmeClass = <<<'PHP'
    <?php

    declare(strict_types=1);

    namespace {{namespace}};

    class AcmeClass
    {
        public function __construct()
        {
            echo 'Hello, {{author}}!';
        }
    }
    PHP;

    $node = <<<'JSON'
    {
        "name": "{{namespace}}",
        "version": "1.0.0",
        "description": "{{description}}",
        "main": "index.js",
        "scripts": {
            "test": "echo \"Error: no test specified\" && exit 1"
        },
        "keywords": [],
        "author": "{{author}}",
        "license": "MIT"
    }
    JSON;

    File::put(sandbox_path('.gitignore'), $ignore);
    File::put(sandbox_path('.editorconfig'), $editor);
    File::put('AcmeClass.php', $acmeClass);
    File::put('package.json', $node);

    $this->artisan('init', [
        'vendor' => 'Acme',
        'package' => 'Package',
        'description' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit.',
        '--author' => 'John Doe',
        '--file' => ['package.json'],
        '--no-self-delete' => true,
        '--skip-license-generation' => true,
    ])
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsConfirmation('Do you want to install the dependencies?')
        ->expectsOutput('Self-deleting skipped')
        ->assertSuccessful();

    expect(sandbox_path('.gitignore'))
        ->toBeFileContent($ignore)
        ->not->toBeFileContent(<<<'EOF'
        ./acme/package
        ./acme/package/vendor/bin/
        ./john-doe.txt
        EOF)
        ->and(sandbox_path('.editorconfig'))->toBeFileContent($editor)
        ->and(sandbox_path('AcmeClass.php'))->toBeFileContent(<<<'PHP'
        <?php

        declare(strict_types=1);

        namespace Acme\Package;

        class AcmeClass
        {
            public function __construct()
            {
                echo 'Hello, John Doe!';
            }
        }
        PHP)
        ->and(sandbox_path('package.json'))->toBeFileContent($node);
});

it('replaces placeholders in file name', function (string $file, string $expected) {
    mkdir(sandbox_path('src'));

    File::put(sandbox_path("src/$file"), '');

    $this->artisan('init', [
        '--author' => 'John Doe',
        '--type' => 'library',
        '--no-self-delete' => true,
        '--skip-license-generation' => true,
    ])
        ->expectsQuestion('What is the vendor name?', 'Acme')
        ->expectsQuestion('What is the package name?', 'Package')
        ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.')
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsConfirmation('Do you want to install the dependencies?')
        ->expectsOutput('Self-deleting skipped')
        ->assertSuccessful();

    expect(sandbox_path("src/$expected"))->toBeFile();
})->with([
    'with author' => [
        '{{author|studly}}{{license|upper}}Class.php', 'JohnDoeMITClass.php',
    ],
    'without author' => [
        '{{license|upper}}Class.php', 'MITClass.php',
    ],
    'with author and package' => [
        '{{author|studly}}{{package|studly}}Class.php', 'JohnDoePackageClass.php',
    ],
    'without author and package' => [
        '{{package|studly}}Class.php', 'PackageClass.php',
    ],
]);

it('can\'t self delete because it\'s not a Phar file', function () {
    expect(function () {
        $this->artisan('init', ['--no-self-delete' => false, '--skip-license-generation' => true])
            ->expectsQuestion('What is the vendor name?', 'Acme')
            ->expectsQuestion('What is the package name?', 'Package')
            ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.')
            ->expectsConfirmation('Do you want to use this configuration?', 'yes')
            ->expectsConfirmation('Do you want to install the dependencies?')
            ->assertSuccessful();
    })->toThrow(CliNotBuiltException::class);
});

it('skips self delete', function () {
    $this->artisan('init', ['--no-self-delete' => true, '--skip-license-generation' => true])
        ->expectsQuestion('What is the vendor name?', 'Acme')
        ->expectsQuestion('What is the package name?', 'Package')
        ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.')
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsConfirmation('Do you want to install the dependencies?')
        ->expectsOutput('Self-deleting skipped')
        ->assertSuccessful();
});

describe('Build CLI and test self-delete functionality', function () {
    it('can self delete when built', function () {
        rmdir_recursive(base_path('builds'));

        $command = Process::command([
            PHP_BINARY,
            'skeleton',
            'app:build',
            '--build-version=unreleased',
        ])
            ->path(base_path());

        expect($command->run())
            ->failed()
            ->toBeFalse()
            ->successful()
            ->toBeTrue()
            ->and(base_path('builds/skeleton'))
            ->toBeFile();

        $command = Process::command([
            './builds/skeleton',
            'init',
            'asciito',
            'package',
            'Lorem ipsum dolor sit amet consectetur adipisicing elit.',
            '--confirm',
            '--do-not-install-dependencies',
            '--skip-license-generation',
            '--path',
            sandbox_path(),
        ])
            ->path(base_path())
            ->run();

        expect($command)
            ->errorOutput()
            ->toBeEmpty()
            ->errorOutput()
            ->not->toContain('We could not self-delete the CLI')
            ->output()
            ->toContain('Self-deleting the CLI...')
            ->toContain('Bye bye')
            ->successful()
            ->toBeTrue()
            ->and(base_path('builds/skeleton'))
            ->not->toBeFile();
    })->skipOnCI();
});
