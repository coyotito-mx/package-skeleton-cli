<?php

declare(strict_types=1);

it('can be instantiated', function () {
    $replacer = new App\Replacer('name', 'John');

    expect($replacer)->toBeInstanceOf(App\Contracts\Replacer::class);
});

it('can replace a placeholder', function () {
    $replacer = new App\Replacer('name', 'John');

    expect($replacer->replace('Hello, {{name}}!'))->toBe('Hello, John!');
});

it('can replace multiple placeholders', function () {
    $replacer = new App\Replacer('name', 'John');

    expect($replacer->replace('Hello, {{name}}! My name is {{name}}.'))->toBe('Hello, John! My name is John.');
});

it('can replace a placeholder with a modifier', function () {
    $replacer = new App\Replacer('name', 'John');

    $replacer->modifierUsing('uppercase', function ($replacement) {
        return strtoupper($replacement);
    });

    expect($replacer->replace('Hello, {{name|uppercase}}!'))->toBe('Hello, JOHN!');
});

it('can\'t replace a placeholder with an unknown modifier', function () {
    $replacer = new App\Replacer('name', 'John');

    expect($replacer->replace('Hello, {{name|unknown}}!'))->toBe('Hello, {{name|unknown}}!');
});

it('can\'t replace a placeholder with multiple modifiers', function () {
    $replacer = new App\Replacer('name', 'John');

    $replacer->modifierUsing('uppercase', function ($replacement) {
        return strtoupper($replacement);
    });

    $replacer->modifierUsing('lowercase', function ($replacement) {
        return strtolower($replacement);
    });

    expect($replacer->replace('Hello, {{name|uppercase|lowercase}}!'))->toBe('Hello, {{name|uppercase|lowercase}}!');
});
