<?php

declare(strict_types=1);

namespace App\Placeholders\Modifiers;

use App\Placeholders\Modifiers\Concerns\HasName;

class PascalModifier implements Contracts\ModifierContract
{
    use HasName;

    public function apply(string $value): string
    {
        return \Illuminate\Support\Str::pascal($value);
    }
}
