<?php

namespace App\DependencyManagers\Exceptions;

use Illuminate\Support\Str;
use RuntimeException;

class DependencyManagerNotInstalledException extends RuntimeException
{
    public function __construct(
        protected(set) string $manager,
        protected(set) string $cause,
        protected(set) int $exitCode = -1,
        protected(set) ?string $binary = null
    ) {
        $class = Str::of($this->manager)->classBasename()->ucfirst();
        $suffix = $this->binary ? " (binary: {$this->binary})" : '';

        parent::__construct("[$class] is not installed or isn't available in the PATH{$suffix}");
    }
}
