<?php

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Mockery as m;

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

    artisan('install:dependencies')
        ->expectsOutputToContain("Composer successfully installed your project dependencies")
        ->assertSuccessful();
    artisan('install:dependencies', ['--tool' => 'npm'])
        ->expectsOutputToContain("Npm successfully installed your project dependencies")
        ->assertSuccessful();

    expect($process)
        ->not->assertNothingRan()
        ->assertRanTimes(fn (PendingProcess $process) => str_contains(implode(' ', $process->command), 'composer --version'))
        ->assertRanTimes(fn (PendingProcess $process) => str_contains(implode(' ', $process->command), 'composer install'))
        ->assertRanTimes(fn (PendingProcess $process) => str_contains(implode(' ', $process->command), 'npm --version'))
        ->assertRanTimes(fn (PendingProcess $process) => str_contains(implode(' ', $process->command), 'npm install'), 1);
});

it('can dry run', function () {
    $process = Process::fake();

    artisan('install:dependencies', ['dependency' => ['laravel/pint:^1.0'], '--dry-run' => true])
        ->expectsOutputToContain('Dry run completed for Composer. No changes were applied.')
        ->expectsOutputToContain('The provided dependencies were validated:')
        ->assertSuccessful();

    expect($process)->assertNothingRan();
});

it('can install composer dependencies', function () {
    $process = Process::fake([
        "'composer' '--version' *",
        "'composer' 'install'" => 'Installing dependencies',
    ]);

    File::partialMock()
        ->shouldReceive('exists')
        ->andReturn(false);

    artisan('install:dependencies')
        ->expectsOutputToContain("Composer successfully installed your project dependencies")
        ->assertSuccessful();

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

    artisan('install:dependencies')
        ->expectsOutputToContain("Composer successfully installed your project dependencies")
        ->assertSuccessful();

    expect($process)
        ->not->assertNothingRan()
        ->assertRanTimes(fn (PendingProcess $process) => str_contains(implode(' ', $process->command), 'composer update'));
});

it('can install npm dependencies', function () {
    $process = Process::fake([
        "'npm' '--version' *" => '10.0.0',
        "'npm' 'install'" => 'Installing dependencies',
    ]);

    File::partialMock()
        ->shouldReceive('exists')
        ->andReturn(true)
        ->shouldReceive('json')
        ->andReturn(['name' => 'test', 'version' => '1.0.0'])
        ->shouldReceive('put')
        ->andReturn(true);

    artisan('install:dependencies', ['--tool' => 'npm', 'dependency' => ['lodash@^4.17.21']])
        ->expectsOutputToContain('Npm successfully installed your project dependencies')
        ->assertSuccessful();

    expect($process)
        ->not->assertNothingRan()
        ->assertRanTimes(fn (PendingProcess $process) => str_contains(implode(' ', $process->command), 'npm --version'))
        ->assertRanTimes(fn (PendingProcess $process) => str_contains(implode(' ', $process->command), 'npm install'));
});

it('can install dev dependencies', function () {
    $process = Process::fake([
        "'composer' '--version' *" => 'Composer version 2.1.3',
        "'composer' 'install'" => 'Installing dependencies',
    ]);

    File::partialMock()
        ->shouldReceive('exists')
        ->with(m::pattern('/composer\.json$/'))
        ->andReturn(true)
        ->shouldReceive('exists')
        ->with(m::pattern('/composer\.lock$/'))
        ->andReturn(false)
        ->shouldReceive('json')
        ->andReturn(['name' => 'vendor/package', 'require' => [], 'require-dev' => []])
        ->shouldReceive('put')
        ->andReturn(true);

    artisan('install:dependencies', ['dependency' => ['pestphp/pest:^3.0'], '--dev' => true])
        ->expectsOutputToContain('Composer successfully installed your project dependencies')
        ->assertSuccessful();

    expect($process)
        ->not->assertNothingRan()
        ->assertRanTimes(fn (PendingProcess $process) => str_contains(implode(' ', $process->command), 'composer install'));
});

it('can install multiple dependencies', function () {
    $process = Process::fake([
        "'composer' '--version' *" => 'Composer version 2.1.3',
        "'composer' 'install'" => 'Installing dependencies',
    ]);

    File::partialMock()
        ->shouldReceive('exists')
        ->with(m::pattern('/composer\.json$/'))
        ->andReturn(true)
        ->shouldReceive('exists')
        ->with(m::pattern('/composer\.lock$/'))
        ->andReturn(false)
        ->shouldReceive('json')
        ->andReturn(['name' => 'vendor/package', 'require' => []])
        ->shouldReceive('put')
        ->andReturn(true);

    artisan('install:dependencies', ['dependency' => ['laravel/pint:^1.0', 'nesbot/carbon:^2.0']])
        ->expectsOutputToContain('Composer successfully installed your project dependencies')
        ->expectsTable(['Dependency'], [['laravel/pint:^1.0'], ['nesbot/carbon:^2.0']])
        ->assertSuccessful();

    expect($process)
        ->not->assertNothingRan()
        ->assertRanTimes(fn (PendingProcess $process) => str_contains(implode(' ', $process->command), 'composer install'));
});

it('fail to install with invalid tool', function () {
    artisan('install:dependencies', ['--tool' => 'pnpm'])
        ->expectsOutputToContain('Please use Composer or NPM.')
        ->assertFailed();
});

it('cannot install invalid dependency format', function () {
    artisan('install:dependencies', ['dependency' => ['invalid-package-format']])
        ->expectsOutputToContain('[invalid-package-format] has an invalid format')
        ->assertFailed();
});

it('fails when dependency manager binary is not installed', function () {
    Process::fake([
        "'npm' '--version' *" => Process::result(errorOutput: 'command not found', exitCode: 127),
    ]);

    artisan('install:dependencies', ['--tool' => 'npm'])
        ->expectsOutputToContain('Fail to install dependencies.')
        ->assertFailed();
});
