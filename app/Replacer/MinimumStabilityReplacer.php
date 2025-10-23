<?php

declare(strict_types=1);

namespace App\Replacer;

class MinimumStabilityReplacer
{
    use Concerns\InteractsWithReplacer;

    protected static string|array $placeholder = 'minimum-stability';
}
