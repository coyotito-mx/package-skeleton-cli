<?php

namespace App\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Stringable;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        Stringable::macro('matchAllWithGroups', function (string $pattern): Collection {
            preg_match_all($pattern, $this->value, $matches, PREG_SET_ORDER);

            return collect($matches)->map(fn ($match) => collect($match)->filter(fn (mixed $value, int|string $key) => is_string($key)));
        });
    }
}
