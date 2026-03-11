<?php

declare(strict_types=1);

use App\Placeholders\Exceptions\InvalidPackageTypeException;
use App\Placeholders\Modifiers\Exceptions\ModifierNotRegistered;
use App\Placeholders\PackageTypePlaceholder;

it('process value', function (string $type): void {
    $placeholder = new PackageTypePlaceholder;

    expect($placeholder)->process($type)->toBe($type);
})->with(InvalidPackageTypeException::$validTypes);

it('fail to process non-valid package type', function (): void {
    $placeholder = new PackageTypePlaceholder;

    expect($placeholder)->process('Invalid Type');
})->throws(InvalidPackageTypeException::class);

it('fail to apply non-register modifier', function (string $modifier): void {
    $placeholder = new PackageTypePlaceholder([$modifier]);

    expect($placeholder)->process('library');
})
    ->with([
        'camel',
        'kebab',
        'lower',
        'pascal',
        'slug',
        'snake',
        'studly',
        'ucfirst',
        'upper',
    ])
    ->throws(ModifierNotRegistered::class);
