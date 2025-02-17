<?php

declare(strict_types=1);

namespace App\Commands\Exceptions;

class LicenseNotFound extends \Exception
{
    public function __construct(string $license)
    {
        parent::__construct("The license '{$license}' is not valid.");
    }
}
