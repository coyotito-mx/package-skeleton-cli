<?php

declare(strict_types=1);

namespace App\Replacers;

use App\Replacer;
use App\Replacers\Exceptions\InvalidPackageTypeException;
use Illuminate\Support\Stringable;

/**
 * Replacer for `type` placeholders.
 *
 * This replacer normalizes package type values as slugs and disallows modifiers.
 *
 * @see \App\Replacer for supported modifiers.
 */
class TypeReplacer extends Builder
{
    protected static string $placeholder = 'type';

    protected static ?string $invalidFormatException = InvalidPackageTypeException::class;

    /**
     * Configure replacement behavior for package types.
     */
    protected function configure(): Replacer
    {
        $this->replacer
            ->normalizeReplacementUsing(fn (Stringable $replacement): Stringable => $replacement->slug())
            ->only(null);

        return parent::configure();
    }
}
