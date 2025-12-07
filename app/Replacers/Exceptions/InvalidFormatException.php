<?php

namespace App\Replacers\Exceptions;

use Exception;

abstract class InvalidFormatException extends Exception
{
    abstract public static function validate(string $value): void;
}
