<?php

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

it('can run', function () {
    $process = Process::fake([
        "'composer' '--version' *" => 'Composer version 2.1.3',
        "'composer' 'install'",
        "'npm' '--version' *" => '10.0.0',
        "'npm' 'install' ",
    ]);

    File::partialMock()
        ->shouldReceive('exists')
        ->andReturn(false);

    artisan('install:dependencies')->assertSuccessful();
    artisan('install:dependencies', ['--tool' => 'npm'])->assertSuccessful();

    expect($process)
        ->not->assertNothingRan()
        ->assertRanTimes(fn (PendingProcess $process) => str_contains(implode(' ', $process->command), 'composer --version'))
        ->assertRanTimes(fn (PendingProcess $process) => str_contains(implode(' ', $process->command), 'composer install'))
        ->assertRanTimes(fn (PendingProcess $process) => str_contains(implode(' ', $process->command), 'npm --version'))
        ->assertRanTimes(fn (PendingProcess $process) => str_contains(implode(' ', $process->command), 'npm install'), 2);
});

it('can dry run')->todo();

it('can install composer dependencies', function () {
    $process = Process::fake([
        "'composer' '--version' *",
        "'composer' 'install'" => 'Installing dependencies',
    ]);

    File::partialMock()
        ->shouldReceive('exists')
        ->andReturn(false);

    artisan('install:dependencies')->assertSuccessful();

    expect($process)
        ->not->assertNothingRan()
        ->assertRanTimes(fn (PendingProcess $process) => str_contains(implode(' ', $process->command), 'composer install'));
});

it('can install composer dependencies with lock file present', function () {
    $process = Process::fake([
        "'composer' '--version' *",
        "'composer' 'update'" => 'Installing dependencies',
    ]);

    File::partialMock()
        ->shouldReceive('exists')
        ->andReturnTrue();

    artisan('install:dependencies')->assertSuccessful();

    expect($process)
        ->not->assertNothingRan()
        ->assertRanTimes(fn (PendingProcess $process) => str_contains(implode(' ', $process->command), 'composer update'));
});

it('can install npm dependencies')->todo();

it('can install dev dependencies')->todo();

it('can install multiple dependencies')->todo();

it('fail to install with invalid tool')->todo();

it('cannot install unknown dependency')->todo();

it('fail if no tool found')->todo();
