<?php

namespace App\Replacers\Exceptions;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;

class InvalidYear extends \Exception
{
    public static function verification(string $year): void
    {
        try {
            Carbon::createFromFormat('Y', $year);
        } catch (InvalidFormatException) {
            throw new static("The year [$year] is not a valid year format.");
        }
    }
}
