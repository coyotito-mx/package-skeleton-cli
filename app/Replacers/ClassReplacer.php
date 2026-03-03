<?php

declare(strict_types=1);

namespace App\Replacers;

use App\Replacer;
use Illuminate\Support\Stringable;

/**
 * Replacer for `class` placeholders
 *
 * @see \App\Replacer for supported modifiers.
 */
class ClassReplacer extends Builder
{
    protected static string $placeholder = 'class';

    protected function configure(): Replacer
    {
        $replacer = $this->replacer;

        $replacer
            ->only([
                'filename',
            ])
            ->addModifier('filename', fn (Stringable $value) => $value->kebab())
            ->normalizeReplacementUsing(fn (Stringable $value) => $value->studly());

        return parent::configure();
    }
}
