<?php

namespace App\Placeholders\Exceptions;

use Exception;

/**
 * Abstract exception class for invalid format exceptions.
 */
abstract class InvalidFormatException extends Exception
{
    /**
     * Validate the given value.
     *
     *
     * @throws static if the value has an invalid format
     */
    abstract public static function validate(string $value): void;
}
