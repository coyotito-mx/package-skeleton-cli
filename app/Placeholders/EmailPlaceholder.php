<?php

declare(strict_types=1);

namespace App\Placeholders;

use App\Placeholders\Exceptions\InvalidEmailException;
use App\Placeholders\Modifiers\UpperModifier;
use Illuminate\Support\Str;

/**
 * Replacer for `email` placeholders
 *
 * @see \App\Placeholders\Modifiers for supported modifiers.
 */
class EmailPlaceholder extends BasePlaceholder
{
    #[\Override]
    protected static function getDefaultModifiers(): array
    {
        return [
            UpperModifier::class,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidEmailException if the provided email is not valid
     */
    #[\Override]
    public function preProcess(string $replacement): string
    {
        InvalidEmailException::validate($replacement);

        return Str::lower($replacement);
    }

    public static function getName(): string
    {
        return 'email';
    }
}
