<?php

declare(strict_types=1);

namespace App\Placeholders\Version\Modifiers;

use App\Placeholders\Modifiers\Concerns\HasName;
use App\Placeholders\Modifiers\Contracts\ModifierContract;
use App\Placeholders\Version\Concerns\InteractsWithVersion;

class MinorModifier implements ModifierContract
{
    use HasName,
        InteractsWithVersion;

    public function apply(string $value): string
    {
        return $this->matchVersionSegment($value, 'minor');
    }
}