<?php

declare(strict_types=1);

namespace App\Replacers;

use App\Replacer;
use App\Replacers\Exceptions\InvalidYear;

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

    public function __construct(string $replacement)
    {
        InvalidYear::validate($replacement);

        parent::__construct($replacement);
    }

    public static function make(?string $replacement = null): Replacer
    {
        if ($replacement === null) {
            $replacement = (string) now()->year;
        }

        return parent::make($replacement);
    }

    protected function getExcludedModifiers(): array
    {
        return ['upper', 'lower', 'title', 'snake', 'kebab', 'camel', 'slug', 'acronym'];
    }
}
