<?php

declare(strict_types=1);

namespace App\Dependencies;

class PestDependency extends ComposerDependency
{
    protected string|array $package = 'pestphp/pest';

    protected bool $dev = true;

    protected bool $withAllDependencies = true;

    protected array $plugins = [
        'pestphp/pest-plugin',
    ];
}
