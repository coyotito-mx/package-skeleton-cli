<?php

namespace App\Replacers\Exceptions;

use Carbon\Exceptions\InvalidFormatException as CarbonInvalidFormatException;
use Illuminate\Support\Carbon;

/**
 * Exception thrown when a year string is not in a valid year format.
 */
final class InvalidYear extends InvalidFormatException
{
    /**
     * Validate the given year string.
     *
     *
     * @throws static If the year string is not valid format.
     */
    public static function validate(string $value): void
    {
        try {
            Carbon::createFromFormat('Y', $value);
        } catch (CarbonInvalidFormatException) {
            throw new self("The year [$value] is not a valid year format.");
        }
    }
}
