<?php

use App\Replacers\Exceptions\InvalidVersion;
use App\Replacers\VersionReplacer;

it('relace version placeholder', function () {
    $replacer = VersionReplacer::make('0.0.1');

    expect($replacer)
        ->replace('The current version is {{version}}.')
        ->toBe('The current version is 0.0.1.');
});

it('throw exception when version is invalid', function (string $version) {
    expect(fn () => VersionReplacer::make($version))
        ->toThrow(InvalidVersion::class, "The version '$version' is not a valid semantic version.");
})
    ->with([
        'invalid-version',
        '1',
        '1.0',
        'v1.0.0',
    ]);

it('replace major version', function () {
    $replacer = VersionReplacer::make('2.5.3');

    expect($replacer)
        ->replace('Major version: {{version|major}}')
        ->toBe('Major version: 2');
});

it('replace minor version', function () {
    $replacer = VersionReplacer::make('2.5.3');

    expect($replacer)
        ->replace('Minor version: {{version|minor}}')
        ->toBe('Minor version: 5');
});

it('replace patch version', function () {
    $replacer = VersionReplacer::make('2.5.3');

    expect($replacer)
        ->replace('Patch version: {{version|patch}}')
        ->toBe('Patch version: 3');
});

it('replace pre-release version', function () {
    $replacer = VersionReplacer::make('1.0.0-alpha');

    expect($replacer)
        ->replace('Pre-release version: {{version|pre}}')
        ->toBe('Pre-release version: alpha');
});

test('chaining multiple replacer will only use the first one', function () {
    $replacer = VersionReplacer::make('2.5.3');

    expect($replacer)
        ->replace('Version: {{version|minor,major}}')
        ->toBe('Version: 5');
});

it('prefix with v', function () {
    $replacer = VersionReplacer::make('1.2.3');

    expect($replacer)
        ->replace('Version: {{version|prefix}}')
        ->toBe('Version: v1.2.3');
});

it('cannot only be prefix once', function () {
    $replacer = VersionReplacer::make('1.2.3');

    expect($replacer)
        ->replace('Version: {{version|prefix,prefix}}')
        ->toBe('Version: v1.2.3');
});
