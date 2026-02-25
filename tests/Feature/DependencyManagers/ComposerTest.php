<?php

use App\DependencyManagers\Composer;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

function createComposerProject(string $path): void
{
    File::ensureDirectoryExists($path);

    File::put($path.DIRECTORY_SEPARATOR.'composer.json', json_encode([
        'name' => 'vendor/package',
        'require' => [],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

it('adds dependencies to composer.json', function () {
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'composer-'.uniqid();
    createComposerProject($path);

    $composer = new Composer($path, tty: false);

    try {
        $composer->add(['laravel/pint:1.0.0']);

        $content = File::json($path.DIRECTORY_SEPARATOR.'composer.json');

        expect($content['require'])->toMatchArray([
            'laravel/pint' => '1.0.0',
        ]);
    } finally {
        File::deleteDirectory($path);
    }
});

it('adds dev dependencies to composer.json', function () {
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'composer-'.uniqid();
    createComposerProject($path);

    $composer = new Composer($path, tty: false);

    try {
        $composer->add(['pestphp/pest:3.0.0'], true);

        $content = File::json($path.DIRECTORY_SEPARATOR.'composer.json');

        expect($content['require-dev'])->toMatchArray([
            'pestphp/pest' => '3.0.0',
        ]);
    } finally {
        File::deleteDirectory($path);
    }
});

it('uses update when lock file exists', function () {
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'composer-'.uniqid();
    createComposerProject($path);
    File::put($path.DIRECTORY_SEPARATOR.'composer.lock', '{}');

    $process = Process::fake([
        "'composer' '--version' '--quiet'" => Process::result(output: 'Composer version 2.1.3', exitCode: 0),
        "'composer' 'update'" => Process::result(output: 'ok', exitCode: 0),
    ]);

    $composer = new Composer($path, tty: false);

    try {
        $composer->install();

        expect($process)
            ->assertRanTimes(fn (PendingProcess $process) => $process->command === ['composer', '--version', '--quiet'])
            ->assertRanTimes(fn (PendingProcess $process) => $process->command === ['composer', 'update']);
    } finally {
        File::deleteDirectory($path);
    }
});

it('uses install when lock file is missing', function () {
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'composer-'.uniqid();
    createComposerProject($path);

    $process = Process::fake([
        "'composer' '--version' '--quiet'" => Process::result(output: 'Composer version 2.1.3', exitCode: 0),
        "'composer' 'install'" => Process::result(output: 'ok', exitCode: 0),
    ]);

    $composer = new Composer($path, tty: false);

    try {
        $composer->install();

        expect($process)
            ->assertRanTimes(fn (PendingProcess $process) => $process->command === ['composer', '--version', '--quiet'])
            ->assertRanTimes(fn (PendingProcess $process) => $process->command === ['composer', 'install']);
    } finally {
        File::deleteDirectory($path);
    }
});
