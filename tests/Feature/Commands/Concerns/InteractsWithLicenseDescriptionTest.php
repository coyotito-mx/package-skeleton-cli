<?php

use App\Commands\Concerns\InteractsWithLicenseDescription;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;

use function App\Helpers\mkdir;
use function App\Helpers\rmdir_recursive;

beforeEach(function () {
    mkdir(sandbox_path());

    $command = configurable_testing_command('', InteractsWithLicenseDescription::class);

    $command->setEntryUsing(function () {
        return ! $this->generateLicenseFile();
    });
});

afterEach(fn () => rmdir_recursive(sandbox_path()));

it('generate license description', function () {
    $filepath = sandbox_path('LICENSE.md');
    $stubpath = base_path('stubs/license.md.stub');

    $this->artisan('demo')
        ->assertSuccessful();

    expect($filepath)
        ->toBeFile()
        ->and(File::get($filepath))
        ->toBe(File::get($stubpath));
});

it('cannot replace already created file', function () {
    File::partialMock()->shouldReceive('exists')->andReturnTrue();

    $this->artisan('demo')
        ->expectsOutputToContain('The `LICENSE.md` file already exists')
        ->expectsConfirmation('Do you want to replace replace the `LICENSE.md` file?')
        ->assertFailed();
});

it('force license description replace', function () {
    $filepath = sandbox_path('LICENSE.md');
    $stubpath = base_path('stubs/license.md.stub');

    File::partialMock()->shouldReceive('exists')->andReturnTrue();

    $this->artisan('demo')
        ->expectsOutputToContain('The `LICENSE.md` file already exists')
        ->expectsConfirmation('Do you want to replace replace the `LICENSE.md` file?', 'yes')
        ->assertSuccessful();

    expect($filepath)
        ->toBeFile()
        ->and(File::get($filepath))
        ->toBe(File::get($stubpath));
});

it('skip license description file generation', function () {
    $filepath = sandbox_path('LICENSE.md');

    $this->artisan('demo', ['--skip-license-generation' => true])
        ->expectsOutputToContain('Skip file generation')
        ->assertFailed();

    expect($filepath)->not->toBeFile();
});

it('fail if file does not exist', function () {
    File::partialMock()->shouldReceive('get')->andThrow(FIleNotFoundException::class);

    $this->artisan('demo')->assertFailed();
})->throws(FileNotFoundException::class);
