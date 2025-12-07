<?php

namespace App\Replacers\Exceptions;

use Illuminate\Support\Str;

/**
 * Exception thrown when an invalid namespace format is provided.
 */
class InvalidNamespace extends InvalidFormatException
{
    /**
     * Regex pattern to validate a namespace format.
     *
     * Raw pattern: `/^(?<vendor>[A-Z][A-Za-z0-9]*)(?<separator>\\)(?<package>[A-Z][A-Za-z0-9]*)$/`
     *
     * Pattern breakdown:
     * - `/.../` : Delimiters for the regex pattern
     * - `^` : Start of the string
     * - `(?<vendor>[A-Z][A-Za-z0-9]*)` : Named capturing group "vendor" - starts with an uppercase letter followed by alphanumeric characters
     * - `(?<separator>\\)` : Named capturing group "separator" - matches a single backslash
     * - `(?<package>[A-Z][A-Za-z0-9]*)` : Named capturing group "package" - starts with an uppercase letter followed by alphanumeric characters
     * - `$` : End of the string
     */
    public static string $namespacePattern = '/^(?<vendor>[A-Z][A-Za-z0-9]*)(?<separator>\\\\)(?<package>[A-Z][A-Za-z0-9]*)$/';

    /**
     * Validate the given namespace.
     *
     * @throws self if the namespace has an invalid format
     */
    public static function validate(string $value): void
    {
        if (Str::isMatch(static::$namespacePattern, $value)) {
            return;
        }

        throw new self('Invalid namespace provided');
    }
}
