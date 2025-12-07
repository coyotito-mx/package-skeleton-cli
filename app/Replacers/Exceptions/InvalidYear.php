<?php

namespace App\Replacers\Exceptions;

use Carbon\Exceptions\InvalidFormatException as CarbonInvalidFormatException;
use Illuminate\Support\Carbon;

class InvalidYear extends InvalidFormatException
{
    public static function validate(string $value): void
    {
        try {
            Carbon::createFromFormat('Y', $value);
        } catch (CarbonInvalidFormatException) {
            throw new static("The year [$value] is not a valid year format.");
        }
    }
}
