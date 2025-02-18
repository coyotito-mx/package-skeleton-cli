<?php

declare(strict_types=1);

namespace App\Replacer\Traits;

use App\Replacer\Replacer;

trait InteractsWithReplacer
{
    public static function make(string $replacement): \App\Replacer\Contracts\Replacer
    {
        return (new Replacer(static::getPlaceholder(), $replacement))->modifierUsing(static::getModifiers());
    }

    public static function getPlaceholder(): string
    {
        return static::$placeholder;
    }

    public static function getModifiers(): array
    {
        return [];
    }
}
