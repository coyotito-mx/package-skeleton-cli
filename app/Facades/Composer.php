<?php

declare(strict_types=1);

namespace App\Facades;

use App\ComposerTestable;
use App\Contracts\ComposerContract;
use Illuminate\Support\Facades\Facade;

class Composer extends Facade
{
    public static function fake(): ComposerTestable
    {
        $instance = new ComposerTestable;

        static::swap($instance);

        return $instance;
    }

    protected static function getFacadeAccessor()
    {
        return ComposerContract::class;
    }
}
