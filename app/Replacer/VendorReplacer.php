<?php

declare(strict_types=1);

namespace App\Replacer;

class VendorReplacer
{
    use Traits\InteractsWithReplacer;

    protected string $placeholder = 'vendor';
}
