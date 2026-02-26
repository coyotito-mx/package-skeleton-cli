<?php

declare(strict_types=1);

namespace App\Replacers;

/**
 * Replacer for `license-description` placeholders.
 *
 * @see \App\Replacer for supported modifiers.
 */
class LicenseDescriptionReplacer extends Builder
{
    protected static string $placeholder = 'license-description';

    #[\Override]
    public function getExcludedModifiers(): array
    {
        return ['snake', 'kebab', 'camel', 'slug', 'acronym'];
    }
}
