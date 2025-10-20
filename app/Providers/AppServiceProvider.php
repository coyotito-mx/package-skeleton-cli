<?php

namespace App\Providers;

use App\Composer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        $this->app->bind('composer', function ($app) {
            return new Composer($app['files'], getcwd());
        });
    }
}
