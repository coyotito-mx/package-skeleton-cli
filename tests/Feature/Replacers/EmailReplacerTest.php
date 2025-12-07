<?php

use App\Replacers\EmailReplacer;
use App\Replacers\Exceptions\InvalidEmail;

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

it('throws exception for invalid email', function (string $invalidEmail) {
    expect(fn () => EmailReplacer::make($invalidEmail))
        ->toThrow(InvalidEmail::class, "The email '$invalidEmail' is not a valid email address.");
})->with([
    'invalid-email',
    'user@.com',
    'user@domain',
    'user@domain..com',
    '@domain.com',
]);
