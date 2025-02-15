<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;


/**
 * Facade for Composer operations that includes license validation functionality.
 *
 * This facade provides a simple interface for running Composer commands and handling
 * license validations through the associated trait.
 */
class Composer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'composer';
    }
}
