<?php

use App\Commands\Concerns\InteractsWithCurrentYear;
use Illuminate\Support\Carbon;

it('replace current year', function (int $year) {
    Carbon::setTestNow("$year-01-02");

    configurable_testing_command('{{year}}', InteractsWithCurrentYear::class);

    $this->artisan('demo')
        ->expectsOutput($year)
        ->assertSuccessful();
})->with(function () {
    return collect(range(0, 5))
        ->map(fn (int $year) => Carbon::createFromDate(2000)->addYears($year)->year)
        ->toArray();
});
