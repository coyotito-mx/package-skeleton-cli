<?php

namespace App\DependencyManagers\Exceptions;

use Illuminate\Support\Str;
use RuntimeException;

class DependencyManagerNotInstalledException extends RuntimeException
{
    public function __construct(protected(set) string $manager, protected(set) string $cause, protected(set) int $exitCode = -1)
    {
        $class = Str::of($this->manager)->classBasename()->ucfirst();

        parent::__construct("[$class] is not installed or isn't available in the PATH");
    }
}
