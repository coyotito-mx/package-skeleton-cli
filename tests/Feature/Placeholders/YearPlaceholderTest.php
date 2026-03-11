<?php

declare(strict_types=1);

use App\Placeholders\YearPlaceholder;
use App\Placeholders\Exceptions\InvalidYearException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

it('process value', function () {
    $placeholder = new YearPlaceholder();

    expect($placeholder)->process('1990')->toBe('1990');
});

it('fail to process non-valid year string', function () {
    $placeholder = new YearPlaceholder();

    expect($placeholder)->process('1970 a.C.'); // LOL
})->throws(InvalidYearException::class);


test('non-provided replacement will fallback to current year', function () {
    Carbon::setTestNow(Date::createFromFormat('Y', 1970));

    $placeholder = new YearPlaceholder();

    expect($placeholder)->process()->toBe('1970');
});