<?php

declare(strict_types=1);

namespace App\Placeholders;

use App\Placeholders\Modifiers\CamelModifier;
use App\Placeholders\Modifiers\KebabModifier;
use App\Placeholders\Modifiers\LowerModifier;
use App\Placeholders\Modifiers\PascalModifier;
use App\Placeholders\Modifiers\SlugModifier;
use App\Placeholders\Modifiers\SnakeModifier;
use App\Placeholders\Modifiers\StudlyModifier;
use App\Placeholders\Modifiers\UCFirstModifier;
use App\Placeholders\Modifiers\UpperModifier;
use Illuminate\Support\Str;

/**
 * Replacer for `author` placeholders
 *
 * @see \App\Placeholders\Modifiers for supported modifiers.
 */
class AuthorPlaceholder extends BasePlaceholder
{
    #[\Override]
    protected static function getDefaultModifiers(): array
    {
        return [
            CamelModifier::class,
            KebabModifier::class,
            LowerModifier::class,
            PascalModifier::class,
            SlugModifier::class,
            SnakeModifier::class,
            StudlyModifier::class,
            UCFirstModifier::class,
            UpperModifier::class,
        ];
    }

    #[\Override]
    public function preProcess(string $replacement): string
    {
        return Str::headline($replacement);
    }

    public static function getName(): string
    {
        return 'author';
    }
}
