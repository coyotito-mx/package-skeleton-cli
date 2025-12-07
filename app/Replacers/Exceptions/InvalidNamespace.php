<?php

namespace App\Replacers\Exceptions;

use Exception;
use Illuminate\Support\Str;


class InvalidNamespace extends InvalidFormatException
{
    protected static string $namespacePattern = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\\\\[a-zA-Z0-9_\x7f-\xff]*$/';

    /**
     * Validate the given namespace.
     *
     * @throws self if the namespace is invalid
     */
    public static function validate(string $value): void
    {
        if (Str::isMatch(static::$namespacePattern, $value)) {
            return;
        }

        throw new self('Invalid namespace provided');
    }
}
