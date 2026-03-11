<?php

declare(strict_types=1);

namespace App\Placeholders\Namespace;

use App\Placeholders\BasePlaceholder;
use App\Placeholders\Exceptions\InvalidNamespaceException;

/**
 * Replacer for `namespace` placeholders
 *
 * @see \App\Placeholders\Modifiers for supported modifiers.
 */
class NamespacePlaceholder extends BasePlaceholder
{

    protected static function getDefaultModifiers(): array
    {
        return [
            Modifiers\LowerModifier::class,
            Modifiers\SlugModifier::class,
            Modifiers\UpperModifier::class,
            Modifiers\EscapeModifier::class,
            Modifiers\ReverseModifier::class,
        ];
    }

    protected function preProcess(string $replacement): string
    {
        return tap($replacement, fn (string $preProcessed) => InvalidNamespaceException::validate($preProcessed));
    }

    public static function getName(): string
    {
        return 'namespace';
    }
}
