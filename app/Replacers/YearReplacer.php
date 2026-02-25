<?php

declare(strict_types=1);

namespace App\Replacers;

use App\Replacer;
use App\Replacers\Exceptions\InvalidYearException;
use Override;

/**
 * Replacer for `year` placeholders.
 *
 * This class replaces the 'year' placeholder with a specified year value or the current year if none is provided.
 *
 * Examples of valid years:
 * - 1
 * - 0001
 * - 1999
 * - 2024
 * - 2050
 * - 2100
 * - 9999
 *
 * Modifiers supported: none
 */
class YearReplacer extends Builder
{
    protected static string $placeholder = 'year';

    protected static ?string $invalidFormatException = InvalidYearException::class;

    #[Override]
    public static function make(?string $replacement = null): Replacer
    {
        if ($replacement === null) {
            $replacement = (string) now()->year;
        }

        return parent::make($replacement);
    }

    #[Override]
    protected function configure(): Replacer
    {
        $this->replacer->only(null);

        return parent::configure();
    }
}
