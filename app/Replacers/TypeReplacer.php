<?php

namespace App\Replacers;

use App\Replacer;
use App\Replacers\Exceptions\InvalidPackageTypeException;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class TypeReplacer extends Builder
{
    protected static string $placeholder = 'type';

    public function __construct(string $replacement)
    {
        $replacement = Str::slug($replacement);

        InvalidPackageTypeException::validate($replacement);

        parent::__construct($replacement);
    }

    protected function configure(Replacer $replacer): void
    {
        $replacer
            ->normalizeReplacementUsing(fn (Stringable $replacement): Stringable => $replacement->slug())
            ->onlyWith(null);

        parent::configure($replacer);
    }
}
