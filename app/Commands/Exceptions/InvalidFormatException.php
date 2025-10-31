<?php

namespace App\Commands\Exceptions;

use RuntimeException;

class InvalidFormatException extends RuntimeException
{
    /**
     * @param string $namespace The malformed `namespace`
     */
    public function __construct(protected(set) string $namespace)
    {
        parent::__construct('The provided namespace does not match the format.');
    }
}
