<?php

use App\Commands\Concerns\InteractsWithTemplate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;

dataset('template', ['vanilla', 'laravel']);

beforeEach(function () {
    $this->command = testingReplacersInCommand('', InteractsWithTemplate::class);

    $this->command->setEntryUsing(function () {
        $this->shouldBootstrapPackage();

        return static::SUCCESS;
    });
});

it('bootstrap package', function () {
    Sleep::fake();
    Process::fake();

    File::shouldReceive('directories')
        ->times(2)
        ->andReturn(
            [],      // "target" directory
            ['/dir'] // Temporary "download" directory
        );

    File::shouldReceive('files')
        ->times(2)
        ->andReturn(
            [],                                      // "target" directory
            [new SplFileInfo('/file.txt')] // Temporary "download" directory
        );

    File::shouldReceive('isDirectory')
        ->times(2)
        ->andReturns(false, true);

    File::shouldReceive('move')
        ->times(1)
        ->withArgs(function (string $from, string $to) {
            $path = preg_quote(sandbox_path(), '/-');
            $regex = "/^$path\/.*\.txt$/";

            return $from === '/file.txt' && Str::isMatch($regex, $to);
        })
        ->andReturn();
    File::shouldReceive('moveDirectory')
        ->times(1)
        ->withArgs(fn (string $from, string $to) => $from === '/dir' && $to === sandbox_path())
        ->andReturn();

    $this->artisan('demo', ['--bootstrap' => 'vanilla'])->assertSuccessful();

    Sleep::assertSleptTimes(2);

    expect($this->command)
        ->option('bootstrap')
        ->not->toBeEmpty();
})->with('template');

it('cannot bootstrap package with invalid template', function () {
    File::shouldReceive('files')->times(0);
    File::shouldReceive('directories')->times(0);
    File::shouldReceive('move')->times(0);
    File::shouldReceive('makeDirectory')->times(0);

    $this->artisan('demo', ['--bootstrap' => 'no-template'])->assertSuccessful();
})->throws(RuntimeException::class, '[no-template] is not a valid template');

it('does not bootstrap package', function () {
    File::shouldReceive('files')->times(0);
    File::shouldReceive('directories')->times(0);
    File::shouldReceive('move')->times(0);
    File::shouldReceive('makeDirectory')->times(0);

    $this->artisan('demo')->assertSuccessful();
});

it('cannot bootstrap package on none empty directory', function () {
    File::shouldReceive('files')->times(1)->andReturn([new SplFileInfo('/file.txt')]);
    File::shouldReceive('directories')->times(1)->andReturn([]);

    $this->artisan('demo', ['--bootstrap' => 'vanilla'])->assertSuccessful();

    expect($this->command)
        ->option('bootstrap')
        ->not->toBeEmpty();
})->throws(RuntimeException::class, 'The directory where you want to bootstrap the package is not empty (CLI file is ignore)');
