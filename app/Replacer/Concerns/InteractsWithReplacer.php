<?php

declare(strict_types=1);

namespace App\Replacer\Concerns;

use App\Replacer\Replacer;
use RuntimeException;

trait InteractsWithReplacer
{
    public static function make(string $replacement): \App\Replacer\Contracts\Replacer
    {
        return (new Replacer(static::getPlaceholder(), $replacement))->modifierUsing(static::getModifiers());
    }

    public static function getPlaceholder(): string|array
    {
        return static::$placeholder ?? throw new RuntimeException('Static property '.static::class.'::$placeholder is not defined');
    }

    public static function getModifiers(): array
    {
        return [];
    }
}
