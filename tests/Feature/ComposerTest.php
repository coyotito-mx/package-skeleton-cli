<?php

declare(strict_types=1);

use App\Composer;
use function App\Helpers\rmdir_recursive;

beforeEach(function () {
    mkdir(sandbox_path());

    $this->files = app('files');
    $this->composer = new Composer($this->files, sandbox_path());
});

afterEach(function () {
    rmdir_recursive(sandbox_path());
});

test('can add dependencies', function () {
    $this->files->put(
        sandbox_path('composer.json'),
        <<<'JSON'
        {
          "name": "vendor/package",
          "description": "A new PHP package"
        }
        JSON
    );

    $this->composer->addDependencies([
        'monolog/monolog' => '^2.0',
    ]);

    expect($this->files->get(sandbox_path('composer.json')))
        ->toBe(
            <<<'JSON'
            {
                "name": "vendor/package",
                "description": "A new PHP package",
                "require": {
                    "monolog/monolog": "^2.0"
                }
            }
            JSON
        );
});

test('can add dev dependencies', function () {
    $this->files->put(
        sandbox_path('composer.json'),
        <<<'JSON'
        {
            "name": "vendor/package",
            "description": "A new PHP package"
        }
        JSON
    );

    $this->composer->addDependencies('phpunit/phpunit', true);

    expect($this->files->get(sandbox_path('composer.json')))
        ->toBe(
            <<<'JSON'
            {
                "name": "vendor/package",
                "description": "A new PHP package",
                "require-dev": {
                    "phpunit/phpunit": "*"
                }
            }
            JSON
        );
});
