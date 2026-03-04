<?php

namespace App\Replacers\Exceptions;

final class InvalidEmailException extends InvalidFormatException
{
    public static function validate(string $value): void
    {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new self("The email '$value' is not a valid email address.");
        }
    }
}
