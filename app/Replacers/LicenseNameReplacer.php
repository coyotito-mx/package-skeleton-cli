<?php

declare(strict_types=1);

namespace App\Replacers;

/**
 * Replacer for `license` placeholders.
 *
 * @see \App\Replacer for supported modifiers.
 */
class LicenseNameReplacer extends Builder
{
    protected static string $placeholder = 'license';
}
