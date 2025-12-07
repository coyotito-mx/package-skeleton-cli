<?php

declare(strict_types=1);

namespace App\Replacers;

/**
 * Replacer for `author` placeholders
 *
 * @see \App\Replacer for supported modifiers.
 */
class AuthorReplacer extends Builder
{
    protected static string $placeholder = 'author';
}
