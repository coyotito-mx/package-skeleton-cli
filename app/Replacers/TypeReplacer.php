<?php

namespace App\Replacers;

use App\Replacer;
use App\Replacers\Exceptions\InvalidPackageTypeException;
use Illuminate\Support\Stringable;

class TypeReplacer extends Builder
{
    protected static string $placeholder = 'type';

    protected static ?string $invalidFormatException = InvalidPackageTypeException::class;

    protected function configure(): Replacer
    {
        $this->replacer
            ->normalizeReplacementUsing(fn (Stringable $replacement): Stringable => $replacement->slug())
            ->only(null);

        return parent::configure();
    }
}
