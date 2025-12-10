<?php

namespace App\DependencyManagers\Exceptions;

use TypeError;

/**
 * The exception is thrown when a dependency format is invalid.
 */
class InvalidDependencyFormatException extends TypeError
{
    public function __construct(protected(set) string $dependency, protected(set) string $validFormat)
    {
        parent::__construct("The dependency [$dependency] has and invalid format");
    }
}
