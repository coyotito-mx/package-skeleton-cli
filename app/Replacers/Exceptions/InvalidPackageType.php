<?php

namespace App\Replacers\Exceptions;

final class InvalidPackageType extends InvalidFormatException
{
    public static array $validTypes = [
        'library',
        'project',
        'metapackage',
        'composer-plugin',
        'php-ext',
        'php-ext-zend',
    ];

    public static function validate(string $value): void
    {
        if (! in_array($value, self::$validTypes, true)) {
            throw new self('Invalid package type provided');
        }
    }
}
