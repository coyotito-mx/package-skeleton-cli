<?php

use App\Replacers\Exceptions\InvalidYearException;
use App\Replacers\YearReplacer;
use Illuminate\Support\Carbon;

it('replace year placeholder', function (): void {
    $replacer = YearReplacer::make('2023');

    expect($replacer->replace('{{year}}'))->toBe('2023')
        ->and($replacer->replace('{{year}}'))->toBe('2023')
        ->and($replacer->replace('Year: {{year}}'))->toBe('Year: 2023');
});

it('replace year with current year when no year is provided', function (): void {
    Carbon::setTestNow('2024-01-01');

    $replacer = YearReplacer::make();

    expect($replacer->replace('{{year}}'))->toBe('2024')
        ->and($replacer->replace('Year: {{year}}'))->toBe('Year: 2024');
});

it('throws an exception for invalid year', function (string $year): void {
    YearReplacer::make($year);
})
    ->throws(InvalidYearException::class)
    ->with([
        'abcd',
        '20a0',
        '-199',
        '30000',
        '',
        '20-20',
    ]);

test('cannot apply excluded modifiers', function (string $modifier): void {
    $replacer = YearReplacer::make('1990');

    expect($replacer)
        ->replace("Year: {{year|$modifier}}")->toBe('Year: 1990');
})->with([
    'lower',
    'title',
    'snake',
    'kebab',
    'camel',
    'slug',
    'acronym',
]);
