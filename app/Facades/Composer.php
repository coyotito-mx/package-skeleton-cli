<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \App\Composer
 */
class Composer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'composer';
    }
}
