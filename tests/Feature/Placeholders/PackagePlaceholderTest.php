<?php

declare(strict_types=1);

use App\Placeholders\PackagePlaceholder;

it('process value', function (): void {
    $placeholder = new PackagePlaceholder;

    expect($placeholder)->process('Acme')->toBe('Acme');
});

it('apply modifier', function (string $modifier, string $value, string $expected): void {
    $placeholder = new PackagePlaceholder([$modifier::getName()]);

    expect($placeholder)->process($value)->toBe($expected);
})->with(fn () => getModifierDataset([
    'camel',
    'kebab',
    'lower',
    'pascal',
    'slug',
    'snake',
    'studly',
    'ucfirst',
    'upper',
    'title',
]));
