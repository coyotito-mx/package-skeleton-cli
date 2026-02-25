<?php

declare(strict_types=1);

namespace App\Replacers;

use App\Replacer;
use Illuminate\Support\Stringable;

/**
 * Replacer for `description` placeholders
 *
 * @see \App\Replacer for supported modifiers.
 */
class DescriptionReplacer extends Builder
{
    protected static string $placeholder = 'description';

    public function getExcludedModifiers(): array
    {
        return ['acronym', 'slug', 'snake', 'camel', 'pascal'];
    }

    protected function configure(): Replacer
    {
        return parent::configure()->normalizeReplacementUsing(fn (Stringable $replacement) => $replacement->ucfirst());
    }
}
