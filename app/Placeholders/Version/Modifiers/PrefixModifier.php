<?php

declare(strict_types=1);

namespace App\Placeholders\Version\Modifiers;

use App\Placeholders\Modifiers\Concerns\HasName;
use App\Placeholders\Modifiers\Contracts\ModifierContract;
use Illuminate\Support\Str;

class PrefixModifier implements ModifierContract
{
    use HasName;

    public function apply(string $value): string
    {
        return Str::of($value)->trim('v')->prepend('v')->toString();
    }
}
