<?php

declare(strict_types=1);

namespace App\Replacer;

class VendorReplacer
{
    use Traits\InteractsWithReplacer;

    protected static string $placeholder = 'vendor';
}
