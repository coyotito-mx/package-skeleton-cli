<?php

declare(strict_types=1);

namespace App\Placeholders;

use App\Placeholders\Exceptions\InvalidYearException;

/**
 * Replacer for `author` placeholders
 *
 * @see \App\Placeholders\Modifiers for supported modifiers.
 */
class YearPlaceholder extends BasePlaceholder
{
    #[\Override]
    protected function preProcess(string $replacement): string
    {
        return tap($replacement, fn (string $preProcessed) => InvalidYearException::validate($preProcessed));
    }

    #[\Override]
    public function process(?string $replacement = null): string
    {
        if (blank($replacement)) {
            $replacement = (string) now()->year;
        }

        return parent::process($replacement);
    }

    public static function getName(): string
    {
        return 'year';
    }
}
