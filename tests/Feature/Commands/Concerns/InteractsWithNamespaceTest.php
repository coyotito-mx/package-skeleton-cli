<?php

use App\Commands\Concerns\InteractsWithNamespace;
use App\Commands\Exceptions\InvalidFormatException;
use Illuminate\Support\Str;

dataset('namespace', [
    'foo/bar',
    'vendor/acme',
    'vendor/package',
    'asciito/acme',
    'asciito/package',
]);

dataset('malformed-namespace', [
    'foo\\bar',
    'Foo Bar\\Buz',
    'Lorem ipsum',
    'some vendor/package',
    'vendor/acme package',
]);

beforeEach(fn () => testingReplacersInCommand('{{namespace}}', InteractsWithNamespace::class));

it('replace namespace', function (string $namespace) {
    [$vendor, $package] = explode('/', $namespace);

    $this->artisan('demo', ['--namespace' => $namespace])
        ->expectsOutput(Str::pascal($vendor).'\\'.Str::pascal($package))
        ->assertSuccessful();
})->with('namespace');

it('aks for missing arguments', function (string $namespace) {
    [$vendor, $package] = explode('/', $namespace);

    $this->artisan('demo', ['vendor' => $vendor, 'package' => $package])
        ->expectsOutput(Str::pascal($vendor).'\\'.Str::pascal($package))
        ->assertSuccessful();
})->with('namespace');

it('throw an error if namespace format is incorrect', function (string $namespace) {
    $this->artisan('demo', ['--namespace' => $namespace])->assertFailed();
})
    ->with('malformed-namespace')
    ->throws(InvalidFormatException::class);
