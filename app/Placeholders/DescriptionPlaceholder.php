<?php

declare(strict_types=1);

namespace App\Placeholders;

use App\Placeholders\Modifiers\LowerModifier;
use App\Placeholders\Modifiers\UCFirstModifier;
use App\Placeholders\Modifiers\UpperModifier;

/**
 * Replacer for `email` placeholders
 *
 * @see \App\Placeholders\Modifiers for supported modifiers.
 */
class DescriptionPlaceholder extends BasePlaceholder
{
    #[\Override]
    protected static function getDefaultModifiers(): array
    {
        return [
            LowerModifier::class,
            UCFirstModifier::class,
            UpperModifier::class,
        ];
    }

    public static function getName(): string
    {
        return 'description';
    }
}
