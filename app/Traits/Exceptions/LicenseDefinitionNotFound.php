<?php

declare(strict_types=1);

namespace App\Traits\Exceptions;

use RuntimeException;

class LicenseDefinitionNotFound extends RuntimeException
{
    public function __construct(string $identifier)
    {
        parent::__construct("License definition for identifier {$identifier} not found.");
    }
}
