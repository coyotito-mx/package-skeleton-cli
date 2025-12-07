<?php

declare(strict_types=1);

namespace App\Replacers;

/**
 * Replacer for `description` placeholders
 *
 * @see \App\Replacer for supported modifiers.
 */
class DescriptionReplacer extends Builder
{
    protected static string $placeholder = 'description';
}
