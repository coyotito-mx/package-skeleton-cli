<?php

namespace App\Replacers\Exceptions;

use Exception;
use Illuminate\Support\Str;


class InvalidNamespace extends Exception
{
    protected static string $pattern = '/^[A-Za-z_][A-Za-z0-9_\\\\]*$/';

    /**
     * Validate the given namespace.
     *
     * @throws static if the namespace is invalid
     */
    public static function verification(string $namespace): void
    {
        if (Str::isMatch(static::$pattern, $namespace)) {
            return;
        }

        throw new static('Invalid namespace provided');
    }
}
