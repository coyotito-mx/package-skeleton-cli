<?php

namespace App\Replacers;

class LicenseDescriptionReplacer extends Builder
{
    protected static string $placeholder = 'license-description';

    protected function getExcludedModifiers(): array
    {
        return ['snake', 'kebab', 'camel', 'slug', 'acronym'];
    }
}
