<?php

namespace App\Replacers\Exceptions;

use Illuminate\Support\Str;

class InvalidNamespace extends InvalidFormatException
{
    public static string $namespacePattern = '/^(?<vendor>[A-Z][A-Za-z0-9]*)(?<separator>\\\\)(?<package>[A-Z][A-Za-z0-9]*)$/';

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
