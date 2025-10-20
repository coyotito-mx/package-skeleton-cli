<?php

use App\Commands\Exceptions\CliNotBuiltException;
use App\Facades\Composer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Sleep;

beforeEach(function () {
    rmdir_recursive(sandbox_path());
    mkdir(sandbox_path());

    $this->oldPath = getcwd();

    chdir(sandbox_path());
});

afterEach(function () {
    chdir($this->oldPath);

    rmdir_recursive(sandbox_path());
    rmdir_recursive(base_path('builds'));
});

it('change command context', function () {
    expect(getcwd())
        ->toBe(sandbox_path())
        ->and($this->oldPath)
        ->toBe(base_path());
});

it('can init the package', function () {
    $partial = Composer::partialMock();
    $partial->expects('installDependencies')->andReturn(true);
    $partial->expects('findComposer')->andReturn(['composer']);

    File::put(
        sandbox_path('composer.json'),
        <<<'EOF'
        {
            "name": "{{namespace|lower,reverse}}",
            "description": "{{description|ucfirst}}",
            "type": "{{type}}",
            "version": "{{version}}",
            "minimum-stability": "{{minimum-stability}}",
            "license": "{{license}}",
            "authors": [
                {
                    "name": "{{author}}"
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

    $this->artisan('init', ['--no-self-delete' => true])
        ->expectsQuestion('What is the vendor name?', 'Acme')
        ->expectsQuestion('What is the package name?', 'Package')
        ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.')
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsQuestion('Do you want to install the dependencies?', 'yes')
        ->expectsOutput('Self-deleting skipped')
        ->assertSuccessful();

    expect(File::get(sandbox_path('composer.json')))
        ->toBeString()
        ->toBe(
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
                        "name": "Acme"
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

    File::delete(sandbox_path('composer.json'));
});

it('failed to install dependencies', function () {
    Composer::partialMock()
        ->expects('findComposerFile')
        ->andThrow(\RuntimeException::class);

    $this->artisan('init', ['--no-self-delete' => true])
        ->expectsQuestion('What is the vendor name?', 'Acme')
        ->expectsQuestion('What is the package name?', 'Package')
        ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.')
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsQuestion('Do you want to install the dependencies?', 'yes');
})->throws(\RuntimeException::class);

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

    $this->artisan('init', ['--no-self-delete' => true])
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

    expect(File::get(sandbox_path('README.MD')))
        ->toBeString()
        ->toBe(
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
        '--license' => 'Apache-2.0',
        '--no-self-delete' => true,
    ])
        ->expectsQuestion('What is the vendor name?', 'Acme')
        ->expectsQuestion('What is the package name?', 'Package')
        ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.')
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsConfirmation('Do you want to install the dependencies?')
        ->expectsOutput('Self-deleting skipped')
        ->assertSuccessful();

    expect(File::get(sandbox_path('src/SomeClass.php')))
        ->toBeString()
        ->toBe(
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Acme\Package;

            /**
            * This is the SomeClass class for testing
            *
            * @package Acme\Package
            * @author John Doe
            * @version 1.0.0
            * @license Apache-2.0
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

it('can init the package with custom values and restart configure', function () {
    mkdir(sandbox_path('config'));

    File::put(
        sandbox_path('config/app.php'),
        <<<'EOF'
        <?php

        /*
        |--------------------------------------------------------------------------
        | Package Roles configuration
        |--------------------------------------------------------------------------
        |
        | This is a configuration file for the package `{{package|ucfirst}}`.
        |
        */

        return [
            'roles' => [
                'admin' => 'Administrator',
                'user' => 'User',
            ],
            'permissions' => [
                'create' => 'Create',
                'read' => 'Read',
                'update' => 'Update',
                'delete' => 'Delete',
            ],
            'models' => [
                'role' => {{namespace}}\Role::class,
                'permission' => {{namespace}}\Permission::class,
            ],
            'tables' => [
                'roles' => 'roles',
                'permissions' => 'permissions',
                'role_user' => 'role_user',
                'prefix_tables' => '{{package|snake}}',
            ],
        ];
        EOF
    );

    $this->artisan('init', [
        'vendor' => 'Acme',
        'package' => 'Package',
        'description' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit.',
        '--author' => 'John Doe',
        '--package-version' => '1.0.0',
        '--minimum-stability' => 'stable',
        '--type' => 'project',
        '--license' => 'Apache-2.0',
        '--no-self-delete' => true,
    ])
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsConfirmation('Do you want to install the dependencies?')
        ->expectsOutput('Self-deleting skipped')
        ->assertSuccessful();

    expect(File::get(sandbox_path('config/app.php')))
        ->toBeString()
        ->toBe(
            <<<'PHP'
            <?php

            /*
            |--------------------------------------------------------------------------
            | Package Roles configuration
            |--------------------------------------------------------------------------
            |
            | This is a configuration file for the package `Package`.
            |
            */

            return [
                'roles' => [
                    'admin' => 'Administrator',
                    'user' => 'User',
                ],
                'permissions' => [
                    'create' => 'Create',
                    'read' => 'Read',
                    'update' => 'Update',
                    'delete' => 'Delete',
                ],
                'models' => [
                    'role' => Acme\Package\Role::class,
                    'permission' => Acme\Package\Permission::class,
                ],
                'tables' => [
                    'roles' => 'roles',
                    'permissions' => 'permissions',
                    'role_user' => 'role_user',
                    'prefix_tables' => 'package',
                ],
            ];
            PHP
        );

    File::deleteDirectory(sandbox_path('config'));
});

it('exclude directory and avoid replacements', function () {
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
        '--dir' => 'src',
        '--no-self-delete' => true,
    ])
        ->expectsQuestion('What is the vendor name?', 'Acme')
        ->expectsQuestion('What is the package name?', 'Package')
        ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.')
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsConfirmation('Do you want to install the dependencies?')
        ->expectsOutput('Self-deleting skipped')
        ->assertSuccessful();

    expect(File::get(sandbox_path('src/SomeClass.php')))
        ->toBeString()
        ->toBe(
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace {{namespace}};

            /**
            * This is the SomeClass class for testing
            *
            * @package {{namespace}}
            * @author {{author}}
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

    File::deleteDirectory(sandbox_path('src'));
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
    ])
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsConfirmation('Do you want to install the dependencies?')
        ->expectsOutput('Self-deleting skipped')
        ->assertSuccessful();

    expect(File::get(sandbox_path('.gitignore')))->toBe($ignore)
        ->not->toBe(<<<'EOF'
        ./acme/package
        ./acme/package/vendor/bin/
        ./john-doe.txt
        EOF)
        ->and(File::get(sandbox_path('.editorconfig')))->toBe($editor)
        ->and(File::get(sandbox_path('AcmeClass.php')))->toBe(<<<'PHP'
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
        ->and(File::get(sandbox_path('package.json')))->toBe($node);
});

it('replaces placeholders in file name', function (string $file, string $expected) {
    mkdir(sandbox_path('src'));

    File::put(sandbox_path("src/$file"), '');

    $this->artisan('init', [
        '--author' => 'John Doe',
        '--license' => 'MIT',
        '--type' => 'library',
        '--no-self-delete' => true,
    ])
        ->expectsQuestion('What is the vendor name?', 'Acme')
        ->expectsQuestion('What is the package name?', 'Package')
        ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.')
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsConfirmation('Do you want to install the dependencies?')
        ->expectsOutput('Self-deleting skipped')
        ->assertSuccessful();

    expect(File::exists(sandbox_path("src/$expected")))->toBeTrue();
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
        $this->artisan('init', ['--no-self-delete' => false])
            ->expectsQuestion('What is the vendor name?', 'Acme')
            ->expectsQuestion('What is the package name?', 'Package')
            ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.')
            ->expectsConfirmation('Do you want to use this configuration?', 'yes')
            ->expectsConfirmation('Do you want to install the dependencies?')
            ->assertSuccessful();
    })->toThrow(CliNotBuiltException::class);
});

it('skips self delete', function () {
    $this->artisan('init', ['--no-self-delete' => true])
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
        $command = \Illuminate\Support\Facades\Process::command([
            PHP_BINARY,
            'skeleton',
            'app:build',
        ])
            ->path(base_path());

        expect($command->run())
            ->failed()
            ->toBeFalse()
            ->successful()
            ->toBeTrue();

        $command = \Illuminate\Support\Facades\Process::command([
            './builds/skeleton',
            'init',
            'asciito',
            'package',
            'Lorem ipsum dolor sit amet consectetur adipisicing elit.',
            '--confirm',
            '--dont-install-dependencies',
        ])
            ->path(base_path());

        $process = $command->run();

        Sleep::for(1)->seconds();

        expect($process)
            ->errorOutput()
            ->toBeEmpty()
            ->successful()
            ->toBeTrue()
            ->and(File::exists(base_path('builds/skeleton')))
            ->toBeFalse('The CLI file still exists');
    });
})->skip(fn () => config('app.env') !== 'development');
