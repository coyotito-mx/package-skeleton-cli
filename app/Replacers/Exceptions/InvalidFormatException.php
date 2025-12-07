<?php

namespace App\Replacers\Exceptions;

use Exception;

/**
 * Abstract exception class for invalid format exceptions.
 */
abstract class InvalidFormatException extends Exception
{
    /**
     * Validate the given value.
     *
     * @param string $value
     *
     * @throws static if the value has an invalid format
     */
    abstract public static function validate(string $value): void;
}
