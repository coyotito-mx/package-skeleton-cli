<?php

use App\Replacers\EmailReplacer;
use App\Replacers\Exceptions\InvalidEmailException;

it('replace email placeholder', function () {
    $replacer = EmailReplacer::make('john@doe.com');

    expect($replacer)
        ->replace('Contact me at {{email}}')->toBe('Contact me at john@doe.com');
});

it('replace email placeholder with modifier', function () {
    $replacer = EmailReplacer::make('jane@doe.com');

    expect($replacer)
        ->replace('Contact me at {{email|upper}}')->toBe('Contact me at JANE@DOE.COM');
});

test('cannot apply excluded modifiers', function (string $modifier) {
    $replacer = EmailReplacer::make('john@doe.com');

    expect($replacer)
        ->replace("Contact me at {{email|$modifier}}")->toBe('Contact me at john@doe.com');
})->with([
    'lower',
    'title',
    'snake',
    'kebab',
    'camel',
    'slug',
    'acronym',
]);

it('throws exception for invalid email', function (string $invalidEmail) {
    expect(fn () => EmailReplacer::make($invalidEmail))
        ->toThrow(InvalidEmailException::class, "The email '$invalidEmail' is not a valid email address.");
})->with([
    'invalid-email',
    'user@.com',
    'user@domain',
    'user@domain..com',
    '@domain.com',
]);
