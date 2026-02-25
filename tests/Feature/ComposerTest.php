<?php

use App\Composer;
use Illuminate\Support\Facades\Process;
use Illuminate\Process\PendingProcess;

it('runs composer require with dev flag', function () {
    $process = Process::fake([
        "'composer' 'require' 'pestphp/pest' '--dev'" => Process::result(),
    ]);

    $composer = new Composer();

    $result = $composer->require('pestphp/pest', true);

    expect($result)->toBeTrue();

    expect($process)
        ->assertRanTimes(fn (PendingProcess $process) => $process->command === ['composer', 'require', 'pestphp/pest', '--dev']);
});

it('runs composer require with all dependencies flag', function () {
    $process = Process::fake([
        "'composer' 'require' 'laravel/framework' '--with-all-dependencies'" => Process::result(),
    ]);

    $composer = new Composer();

    $result = $composer->require('laravel/framework', false, true);

    expect($result)->toBeTrue();

    expect($process)
        ->assertRanTimes(fn (PendingProcess $process) => $process->command === ['composer', 'require', 'laravel/framework', '--with-all-dependencies']);
});

it('runs composer require without flags', function () {
    $process = Process::fake([
        "'composer' 'require' 'laravel/pint'" => Process::result(),
    ]);

    $composer = new Composer();

    $result = $composer->require('laravel/pint');

    expect($result)->toBeTrue();

    expect($process)
        ->assertRanTimes(fn (PendingProcess $process) => $process->command === ['composer', 'require', 'laravel/pint']);
});

it('runs composer require with multiple packages', function () {
    $process = Process::fake([
        "'composer' 'require' 'laravel/pint' 'phpstan/phpstan'" => Process::result(),
    ]);

    $composer = new Composer();

    $result = $composer->require(['laravel/pint', 'phpstan/phpstan']);

    expect($result)->toBeTrue();

    expect($process)
        ->assertRanTimes(fn (PendingProcess $process) => $process->command === ['composer', 'require', 'laravel/pint', 'phpstan/phpstan']);
});

it('returns false when composer require fails', function () {
    $process = Process::fake([
        "'composer' 'require' 'invalid/package'" => Process::result(exitCode: 1),
    ]);

    $composer = new Composer();

    $result = $composer->require('invalid/package');

    expect($result)->toBeFalse();

    expect($process)
        ->assertRanTimes(fn (PendingProcess $process) => $process->command === ['composer', 'require', 'invalid/package']);
});

it('uses custom working directory', function () {
    $process = Process::fake([
        "'composer' 'require' 'laravel/pint'" => Process::result(),
    ]);

    $customPath = '/custom/path';
    $composer = new Composer($customPath);

    $composer->require('laravel/pint');

    expect($process)
        ->assertRanTimes(function (PendingProcess $process) use ($customPath) {
            return $process->path === $customPath
                && $process->command === ['composer', 'require', 'laravel/pint'];
        });
});

it('handles both dev and with-all-dependencies flags together', function () {
    $process = Process::fake([
        "'composer' 'require' 'symfony/console' '--dev' '--with-all-dependencies'" => Process::result(),
    ]);

    $composer = new Composer();

    $result = $composer->require('symfony/console', dev: true, withAllDependencies: true);

    expect($result)->toBeTrue();

    expect($process)
        ->assertRanTimes(fn (PendingProcess $process) => $process->command === ['composer', 'require', 'symfony/console', '--dev', '--with-all-dependencies']);
});
