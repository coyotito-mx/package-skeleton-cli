<?php

use App\Replacers\Exceptions\InvalidYear;
use App\Replacers\YearReplacer;
use Illuminate\Support\Carbon;

it('replace year placeholder', function () {
    $replacer = YearReplacer::make('2023');

    expect($replacer->replace('{{year}}'))->toBe('2023')
        ->and($replacer->replace('{{year}}'))->toBe('2023')
        ->and($replacer->replace('Year: {{year}}'))->toBe('Year: 2023');
});

it('replace year with current year when no year is provided', function () {
    Carbon::setTestNow('2024-01-01');

    $replacer = YearReplacer::make();

    expect($replacer->replace('{{year}}'))->toBe('2024')
        ->and($replacer->replace('Year: {{year}}'))->toBe("Year: 2024");
});

it('throws an exception for invalid year', function (string $year) {
    YearReplacer::make($year);
})
    ->throws(InvalidYear::class)
    ->with([
        'abcd',
        '20a0',
        '-199',
        '30000',
        '',
        '20-20',
    ]);

it('does not apply any modifier', function () {
    $replacer = YearReplacer::make('2022');

    expect($replacer->replace('{{year|slug}}'))->toBe('2022')
        ->and($replacer->replace('{{year|snake}}'))->toBe('2022')
        ->and($replacer->replace('{{year|capitalize}}'))->toBe('2022');
});
