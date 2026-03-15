<?php

declare(strict_types=1);

use App\PlaceholderReplacer;
use App\Placeholders\Modifiers\LowerModifier;
use App\Placeholders\Modifiers\UpperModifier;

it('replace placeholder', function (): void {
    $placeholderClass = createPlaceholderClass('foo');

    $replacer = new PlaceholderReplacer()->registerPlaceholderWithValue($placeholderClass, 'Bar');

    expect($replacer)->replace('Hello, {{foo}}')->toBe('Hello, Bar');
});

it('replacer placeholders', function (): void {
    $fooPlaceholderClass = createPlaceholderClass('foo');
    $barPlaceholderClass = createPlaceholderClass('bar');

    $replacer = new PlaceholderReplacer()
        ->registerPlaceholderWithValue($fooPlaceholderClass, 'Buzz')
        ->registerPlaceholderWithValue($barPlaceholderClass, 'FooBar');

    expect($replacer)->replace('Hello, {{foo}}-{{bar}}')->toBe('Hello, Buzz-FooBar');
});

it('replace placeholder with modifiers', function (): void {
    $placeholderClass = createPlaceholderClass('foo', [
        UpperModifier::class,
        LowerModifier::class,
    ]);

    $replacer = new PlaceholderReplacer()->registerPlaceholderWithValue($placeholderClass, 'Bar');

    expect($replacer)
        ->replace('{{foo|upper}}')
        ->toBe('BAR')
        ->replace('{{foo|lower}}')
        ->toBe('bar');
});

test('will not replace non-registered placeholder', function (): void {
    $replacer = new PlaceholderReplacer;

    expect($replacer)->replace('Hello, {{foo}}')->toBe('Hello, {{foo}}');
});

test('will not replace malformed placeholder', function (): void {
    $placeholderClass = createPlaceholderClass('foo');

    $replacer = new PlaceholderReplacer()->registerPlaceholderWithValue($placeholderClass, 'Bar');

    expect($replacer)
        ->replace('Hello, {{foo }}')->toBe('Hello, {{foo }}')
        ->replace('Hello, {{ foo}}')->tobe('Hello, {{ foo}}')
        ->replace('Hello, {{ foo }}')->tobe('Hello, {{ foo }}')
        ->replace('Hello, {{foo')->tobe('Hello, {{foo')
        ->replace('Hello, foo}}')->tobe('Hello, foo}}')
        ->replace('Hello, {foo}')->tobe('Hello, {foo}');
});
