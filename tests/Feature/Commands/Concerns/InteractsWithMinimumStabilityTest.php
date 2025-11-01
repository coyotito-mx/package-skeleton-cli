<?php

use App\Commands\Concerns\InteractsWithMinimumStability;

dataset('values', function () {
    static $values = null;

    if (filled($values)) {
        return $values;
    }

    $trait = new \ReflectionClass(InteractsWithMinimumStability::class);

    $prop = $trait->getProperty('minimumStabilityAvailable');

    return $values = $prop->getDefaultValue();
});

beforeEach(fn () => $this->command = configurable_testing_command('{{minimum-stability}}', InteractsWithMinimumStability::class));

it('replace minimum stability', function () {
    $this->artisan('demo')
        ->expectsOutput('dev')
        ->assertSuccessful();
});

it('raplace minimum stability using available value', function (string $value) {
    $this->artisan('demo', ['--minimum-stability' => $value])
        ->expectsOutput($value)
        ->assertSuccessful();
})
    ->with('values');

it('failed to replace minimum stability using non-available value', function (string $value) {
    $this->artisan('demo', ['--minimum-stability' => $value])->assertFailed();
})->throws(RuntimeException::class, 'Invalid minimum stability.')->with([
    'pre-alpha',
    'pre-beta',
    'release',
    'not-release',
]);
