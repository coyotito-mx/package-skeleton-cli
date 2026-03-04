<?php

declare(strict_types=1);

namespace App\Replacers;

/**
 * Replacer for 'vendor' placeholders
 *
 * @see \App\Replacer for supported modifiers.
 */
class VendorReplacer extends Builder
{
    protected static string $placeholder = 'vendor';
}
