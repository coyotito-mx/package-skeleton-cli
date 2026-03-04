<?php

declare(strict_types=1);

namespace App\Dependencies;

class PHPUnitDependency extends ComposerDependency
{
    protected string|array $package = 'phpunit/phpunit';

    protected bool $dev = true;

    protected bool $withAllDependencies = true;
}
