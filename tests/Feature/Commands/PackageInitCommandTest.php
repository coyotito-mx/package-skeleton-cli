<?php

use Illuminate\Support\Facades\File;
use function Pest\Laravel\artisan;

beforeEach(function () {
    $this->oldDir = getcwd();

    chdir(test_path('sandbox'));
});

afterEach(function () {
    chdir($this->oldDir);

    unset($this->oldDir);
});

it('change command context', function () {
    expect(getcwd())
        ->toBe(sandbox_path())
        ->and($this->oldDir)
        ->toBe(base_path());
});

it('can init the package', function () {
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
                    "name": "{{author}}",
                }
            ]
        }
        EOF
    );

    artisan('package:init')
        ->expectsQuestion('What is the vendor name?', 'Acme')
        ->expectsQuestion('What is the package name?', 'Package')
        ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.')
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsOutputToContain('Replacing vendor [acme]...')
        ->expectsOutputToContain('Replacing package [package]...')
        ->expectsOutputToContain('Replacing author [Acme]...')
        ->expectsOutputToContain('Replacing description [Lorem ipsum dolor sit amet consectetur adipisicing elit.]...')
        ->expectsOutputToContain('Replacing namespace [Acme\Package]...')
        ->expectsOutputToContain('Replacing package version [v0.0.1]...')
        ->expectsOutputToContain('Replacing minimum stability [dev]...')
        ->expectsOutputToContain('Replacing type [library]...')
        ->expectsOutputToContain('Replacing license [MIT]...')
        ->expectsOutputToContain('Package initialized successfully.')
        ->assertSuccessful();

    expect(File::get(sandbox_path('composer.json')))
        ->toBeString()
        ->toBe(
            <<<'EOF'
            {
                "name": "acme/package",
                "description": "Lorem ipsum dolor sit amet consectetur adipisicing elit.",
                "type": "library",
                "version": "v0.0.1",
                "minimum-stability": "dev",
                "license": "MIT",
                "authors": [
                    {
                        "name": "Acme",
                    }
                ]
            }
            EOF
        );

    File::delete(sandbox_path('composer.json'));
});

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

        Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

        ## Contributing

        Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

        ## Security

        If you discover any security related issues, please email {{author}} instead of using the issue tracker.

        ## Credits

        - {{author}} - Initial work
        - [All Contributors](../../contributors)
        README
    );

    artisan('package:init')
        ->expectsQuestion('What is the vendor name?', 'Acme')
        ->expectsQuestion('What is the package name?', 'Package')
        ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.')
        ->expectsConfirmation('Do you want to use this configuration?')
        ->expectsQuestion('What is the vendor name?', 'Asciito')
        ->expectsQuestion('What is the package name?', 'Package')
        ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor it set adisicing elit.')
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsOutputToContain('Replacing vendor [asciito]...')
        ->expectsOutputToContain('Replacing package [package]...')
        ->expectsOutputToContain('Replacing author [Asciito]...')
        ->expectsOutputToContain('Replacing description [Lorem ipsum dolor it set adisicing elit.]...')
        ->expectsOutputToContain('Replacing namespace [Asciito\Package]...')
        ->expectsOutputToContain('Replacing package version [v0.0.1]...')
        ->expectsOutputToContain('Replacing minimum stability [dev]...')
        ->expectsOutputToContain('Replacing type [library]...')
        ->expectsOutputToContain('Replacing license [MIT]...')
        ->expectsOutputToContain('Package initialized successfully.')
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

            Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

            ## Contributing

            Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

            ## Security

            If you discover any security related issues, please email Asciito instead of using the issue tracker.

            ## Credits

            - Asciito - Initial work
            - [All Contributors](../../contributors)
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

    artisan('package:init', [
        '--author' => 'John Doe',
        '--package-version' => 'v1.0.0',
        '--minimum-stability' => 'stable',
        '--type' => 'project',
        '--license' => 'Apache-2.0'
    ])
        ->expectsQuestion('What is the vendor name?', 'Acme')
        ->expectsQuestion('What is the package name?', 'Package')
        ->expectsQuestion('What is the package description?', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.')
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsOutputToContain('Replacing vendor [acme]...')
        ->expectsOutputToContain('Replacing package [package]...')
        ->expectsOutputToContain('Replacing author [John Doe]...')
        ->expectsOutputToContain('Replacing description [Lorem ipsum dolor sit amet consectetur adipisicing elit.]...')
        ->expectsOutputToContain('Replacing namespace [Acme\Package]...')
        ->expectsOutputToContain('Replacing package version [v1.0.0]...')
        ->expectsOutputToContain('Replacing minimum stability [stable]...')
        ->expectsOutputToContain('Replacing type [project]...')
        ->expectsOutputToContain('Replacing license [Apache-2.0]...')
        ->expectsOutputToContain('Package initialized successfully.')
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
            * @version v1.0.0
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

    $this->artisan('package:init', [
        'vendor' => 'Acme',
        'package' => 'Package',
        'description' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit.',
        '--author' => 'John Doe',
        '--package-version' => 'v1.0.0',
        '--minimum-stability' => 'stable',
        '--type' => 'project',
        '--license' => 'Apache-2.0'
    ])
        ->expectsConfirmation('Do you want to use this configuration?', 'yes')
        ->expectsOutputToContain('Replacing vendor [acme]...')
        ->expectsOutputToContain('Replacing package [package]...')
        ->expectsOutputToContain('Replacing author [John Doe]...')
        ->expectsOutputToContain('Replacing description [Lorem ipsum dolor sit amet consectetur adipisicing elit.]...')
        ->expectsOutputToContain('Replacing namespace [Acme\Package]...')
        ->expectsOutputToContain('Replacing package version [v1.0.0]...')
        ->expectsOutputToContain('Replacing minimum stability [stable]...')
        ->expectsOutputToContain('Replacing type [project]...')
        ->expectsOutputToContain('Replacing license [Apache-2.0]...')
        ->expectsOutputToContain('Package initialized successfully.')
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
