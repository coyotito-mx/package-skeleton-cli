<?php

declare(strict_types=1);

namespace App\Placeholders;

use App\Placeholders\Modifiers\FilenameModifier;
use Illuminate\Support\Str;

/**
 * Replacer for `email` placeholders
 *
 * @see \App\Placeholders\Modifiers for supported modifiers.
 */
class ClassPlaceholder extends BasePlaceholder
{
    #[\Override]
    protected static function getDefaultModifiers(): array
    {
        return [
            FilenameModifier::class,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException if the provided value is not a valid e-amil
     */
    #[\Override]
    public function preProcess(string $replacement): string
    {
        return Str::of($replacement)->kebab()->studly()->toString();
    }

    public static function getName(): string
    {
        return 'class';
    }
}
