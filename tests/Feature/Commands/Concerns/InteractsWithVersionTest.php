<?php

use App\Commands\Concerns\InteractsWithVersion;
use App\Commands\Exceptions\InvalidFormatException;

beforeEach(fn () => $this->command = testingReplacersInCommand('{{version}}', InteractsWithVersion::class));

dataset('version', function () {
    return collect(range(1, 6))
        ->map(function (int $v) {
            $extras = [
                'rc',
                'alpha',
                'beta',
                'preview',
                'dev',
            ];

            $template = "%d.%d.%d";

            $extra = mt_rand(0, 1) ? '-'.$extras[random_int(0, count($extras) - 1)] : '';

            return sprintf($template, 0, 0, $v).$extra;
        })
        ->toArray();
});

dataset('invalid', function () {
    return collect(range(1, 6))
        ->map(function (int $v) {
            $extras = [
                'rc',
                'alpha',
                'beta',
                'preview',
                'dev',
            ];

            $template = "%d.%d";

            $extra = mt_rand(0, 1) ? '-'.$extras[random_int(0, count($extras) - 1)] : '';

            return sprintf($template, 0, $v).$extra;
        })
        ->toArray();
});

it('replace version', function () {
    $this->artisan('demo')
        ->expectsOutput('0.0.1')
        ->assertSuccessful();
});

it('replace version with value', function (string $version) {
    $this->artisan('demo', ['--package-version' => $version])
        ->expectsOutput($version)
        ->assertSuccessful();
})->with('version');

it('throws error for invalid format', function (string $invalid) {
    $this->artisan('demo', ['--package-version' => $invalid])
        ->assertFailed();
})->with('invalid')->throws(InvalidFormatException::class);
