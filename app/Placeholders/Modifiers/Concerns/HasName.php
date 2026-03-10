<?php

declare(strict_types=1);

namespace App\Placeholders\Modifiers\Concerns;

use Illuminate\Support\Str;

trait HasName
{
    public static function getName(): string
    {
        $className = class_basename(static::class);

        return Str::of($className)->replace('Modifier', '')->kebab()->toString();
    }
}
