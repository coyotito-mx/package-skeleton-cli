<?php

declare(strict_types=1);

namespace App\Replacers;

use App\Replacer;
use App\Replacers\Exceptions\InvalidYear;

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
