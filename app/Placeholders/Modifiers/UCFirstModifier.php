<?php

declare(strict_types=1);

namespace App\Placeholders\Modifiers;

class UCFirstModifier implements Contracts\ModifierContract
{
    public function apply(string $value): string
    {
        return \Illuminate\Support\Str::of($value)->lower()->ucfirst()->toString();
    }

    public static function getName(): string
    {
        return 'ucfirst';
    }
}
