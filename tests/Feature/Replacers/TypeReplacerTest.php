<?php

use App\Replacers\Exceptions\InvalidPackageTypeException;
use App\Replacers\TypeReplacer;

it('replace placeholder', function (string $type) {
    $replacer = TypeReplacer::make($type);

    expect($replacer)
        ->replace("type: {{type}}")->toBe("type: $type");
})->with(InvalidPackageTypeException::$validTypes);

it('replace placeholder with invalid type', function (string $type) {
    $replacer = TypeReplacer::make($type);
})->throws(InvalidPackageTypeException::class, 'Invalid package type provided')->with([
    'none',
    'custom',
    'none-standard',
    'Invalid Type',
    'vanilla',
    'laravel'
]);

test('cannot apply excluded modifier', function (string $modifier) {
    $replacer = TypeReplacer::make('php-ext');

    expect($replacer)
        ->replace("type: {{type|$modifier}}")->toBe('type: php-ext');
})->with([
    'upper',
    // 'lower', We cannot assert this because the text is already lower
    'title',
    // 'snake', We cannot assert this because if the text contains `-`, this will not replace them
    'kebab',
    'camel',
    // 'slug', we cannot assert this because we slugify the value at the beginning
    'acronym',
]);
