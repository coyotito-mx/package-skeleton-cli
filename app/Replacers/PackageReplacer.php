<?php

declare(strict_types=1);

namespace App\Replacers;

/**
 * Replacer for `package` placeholders.
 *
 * @see \App\Replacer for supported modifiers.
 */
class PackageReplacer extends Builder
{
    protected static string $placeholder = 'package';
}
