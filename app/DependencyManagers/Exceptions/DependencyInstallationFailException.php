<?php

namespace App\DependencyManagers\Exceptions;

use Illuminate\Contracts\Process\ProcessResult;
use RuntimeException;

class DependencyInstallationFailException extends RuntimeException
{
    public function __construct(string $message, protected(set) ProcessResult $process, protected(set) array $dependencies = [])
    {
        parent::__construct($message);
    }
}
