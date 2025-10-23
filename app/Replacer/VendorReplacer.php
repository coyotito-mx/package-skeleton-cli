<?php

declare(strict_types=1);

namespace App\Replacer;

class VendorReplacer
{
    use Concerns\InteractsWithReplacer;

    protected static string $placeholder = 'vendor';
}
