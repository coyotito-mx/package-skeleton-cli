<?php

declare(strict_types=1);

namespace App\Replacer;

class LicenseReplacer
{
    use Concerns\InteractsWithReplacer;

    protected static string|array $placeholder = 'license';
}
