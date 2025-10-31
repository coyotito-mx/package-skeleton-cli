<?php

namespace App\Commands\Exceptions;

use RuntimeException;

class InvalidFormatException extends RuntimeException
{
    /**
     * Constructor function
     *
     * @param string $message The message to error to report
     * @param string $value The malformed `value`
     */
    public function __construct(string $message, protected(set) string $value)
    {
        parent::__construct($this->message);
    }
}
