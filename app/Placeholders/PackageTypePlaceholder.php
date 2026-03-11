<?php

declare(strict_types=1);

namespace App\Placeholders;

use App\Placeholders\BasePlaceholder;
use App\Placeholders\Exceptions\InvalidPackageTypeException;
use Illuminate\Support\Str;

/**
 * Replacer for `author` placeholders
 *
 * @see \App\Placeholders\Modifiers for supported modifiers.
 */
class PackageTypePlaceholder extends BasePlaceholder
{
    /**
     * {@inheritdoc}
     * 
     * @throws InvalidPackageTypeException if the provided type is not valid
     */
    public function preProcess(string $replacement): string
    {
        return tap(Str::slug($replacement), fn (string $preProcessed) => InvalidPackageTypeException::validate($preProcessed));
    }

    public static function getName(): string
    {
        return 'type';
    }
}
