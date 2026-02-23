<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Composer extends Facade
{
    public static function fake(): \App\DependencyManagers\ComposerFake
    {
        $fake = new \App\DependencyManagers\ComposerFake();

        static::swap($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return 'composer';
    }
}
