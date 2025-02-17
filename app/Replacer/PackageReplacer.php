<?php

declare(strict_types=1);

namespace App\Replacer;

class PackageReplacer
{
    use Traits\InteractsWithReplacer;

    protected string $placeholder = 'package';
}
