<?php

use App\Commands\Concerns\InteractsWithType;

beforeEach(fn () => configurable_testing_command('{{type}}', InteractsWithType::class));

dataset('value', function () {
    static $values = null;

    if (filled($values)) {
        return $values;
    }

    $trait = new \ReflectionClass(InteractsWithType::class);

    $prop = $trait->getProperty('packageTypes');

    return $values = $prop->getDefaultValue();
});

dataset('invalid', [
    'next',
    'typescript',
    'yii2',
    'laravel',
    'codeigniter',
]);

it('replace type', function () {
    $this->artisan('demo')
        ->expectsOutput('library')
        ->assertSuccessful();
});

it('replace type with value', function (string $value) {
    $this->artisan('demo', ['--type' => $value])
        ->expectsOutput($value)
        ->assertSuccessful();
})->with('value');

it('throws error for invalid type', function (string $invalid) {
    $this->artisan('demo', ['--type' => $invalid])->assertFailed();
})->with('invalid')->throws(RuntimeException::class);
