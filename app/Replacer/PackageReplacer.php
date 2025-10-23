<?php

declare(strict_types=1);

namespace App\Replacer;

class PackageReplacer
{
    use Concerns\InteractsWithReplacer;

    protected static string $placeholder = 'package';
}
