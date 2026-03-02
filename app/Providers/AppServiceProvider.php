<?php

namespace App\Providers;

use App\Composer;
use App\Contracts\ComposerContract;
use App\Dependencies\PestDependency;
use App\Dependencies\PHPUnitDependency;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Stringable;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }

    #[\Override]
    public function register(): void
    {
        $this->app->singleton(
            ComposerContract::class,
            fn ($app) => new Composer(app: $app),
        );

        $this->registerDependencyBindings();

        Stringable::macro('matchAllWithGroups', function (string $pattern): Collection {
            $result = preg_match_all($pattern, (string) $this, $matches, PREG_SET_ORDER);

            if ($result === false) {
                throw new \RuntimeException("Invalid regex pattern: $pattern");
            }

            return collect($matches)->map(fn ($match) => collect($match)->filter(fn (mixed $value, int|string $key) => is_string($key)));
        });
    }

    protected function registerDependencyBindings(): void
    {
        $this->app->bind(
            'pest',
            fn ($app) => new PestDependency(composer: $app->make(ComposerContract::class)),
        );

        $this->app->bind(
            'phpunit',
            fn ($app) => new PHPUnitDependency(composer: $app->make(ComposerContract::class)),
        );
    }
}
