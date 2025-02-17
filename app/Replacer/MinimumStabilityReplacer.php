<?php

declare(strict_types=1);

namespace App\Replacer;

class MinimumStabilityReplacer
{
    use Traits\InteractsWithReplacer;

    protected static string $placeholder = 'minimum-stability';
}
