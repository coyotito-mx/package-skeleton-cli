<?php

namespace App\Replacers\Exceptions;

use Carbon\Exceptions\InvalidFormatException as CarbonInvalidFormatException;
use Illuminate\Support\Carbon;

/**
 * Exception thrown when a year string is not in a valid year format.
 */
class InvalidYear extends InvalidFormatException
{
    /**
     * Validate the given year string.
     *
     * @param string $value
     * @return void
     *
     * @throws self If the year string is not valid format.
     */
    public static function validate(string $value): void
    {
        try {
            Carbon::createFromFormat('Y', $value);
        } catch (CarbonInvalidFormatException) {
            throw new static("The year [$value] is not a valid year format.");
        }
    }
}
