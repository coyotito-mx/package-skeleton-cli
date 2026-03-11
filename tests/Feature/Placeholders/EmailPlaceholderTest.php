<?php

declare(strict_types=1);

use App\Placeholders\EmailPlaceholder;
use App\Placeholders\Modifiers\Exceptions\ModifierNotRegistered;
use App\Placeholders\Exceptions\InvalidEmailException;

it('process value', function () {
    $placeholder = new EmailPlaceholder();

    expect($placeholder)->process('john@doe.com')->toBe('john@doe.com');
});

it('fail to process non-valid e-mail', function () {
    $placeholder = new EmailPlaceholder();

    expect($placeholder)->process('john@doe');
})->throws(InvalidEmailException::class);

it('fail to apply non-register modifier', function (string $modifier): void {
    $placeholder = new EmailPlaceholder([$modifier]);

    expect($placeholder)->process('john@doe.com');
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
    ])
    ->throws(ModifierNotRegistered::class);