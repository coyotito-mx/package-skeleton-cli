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

/**
 * Replacer for `author` placeholders
 *
 * @see \App\Placeholders\Modifiers for supported modifiers.
 */
class LicensePlaceholder extends BasePlaceholder
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

    public static function getName(): string
    {
        return 'license';
    }
}
