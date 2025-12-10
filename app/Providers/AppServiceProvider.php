<?php

namespace App\Providers;

use App\DependencyManagers\Composer;
use App\DependencyManagers\Npm;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Stringable;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(Composer::class, function () {
            return new Composer(getcwd());
        });

        $this->app->singleton(Npm::class, function () {
            return new Npm(getcwd());
        });

        $this->app->alias(Composer::class, 'composer');

        $this->app->alias(Npm::class, 'npm');
    }

    public function register(): void
    {
        Stringable::macro('matchAllWithGroups', function (string $pattern): Collection {
            preg_match_all($pattern, $this->value(), $matches, PREG_SET_ORDER);

            return collect($matches)->map(fn ($match) => collect($match)->filter(fn (mixed $value, int|string $key) => is_string($key)));
        });
    }
}
