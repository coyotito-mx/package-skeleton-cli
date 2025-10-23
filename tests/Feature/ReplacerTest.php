<?php

declare(strict_types=1);

it('can be instantiated', function () {
    $replacer = new \App\Replacer\Replacer('name', 'John');

    expect($replacer)->toBeInstanceOf(\App\Replacer\Contracts\Replacer::class);
});

it('can replace a placeholder', function () {
    $replacer = new \App\Replacer\Replacer('name', 'John');

    expect($replacer->replace('Hello, {{name}}!'))->toBe('Hello, John!');
});

it('can replace multiple placeholders', function () {
    $replacer = new \App\Replacer\Replacer('name', 'John');

    expect($replacer->replace('Hello, {{name}}! My name is {{name}}.'))->toBe('Hello, John! My name is John.');
});

it('can replace a placeholder with a modifier', function () {
    $replacer = new \App\Replacer\Replacer('name', 'John');

    expect($replacer->replace('Hello, {{name|upper}}!'))->toBe('Hello, JOHN!');
});

it('can replace a placeholder with a custom modifier', function () {
    $replacer = new \App\Replacer\Replacer('name', 'John');

    $replacer->modifierUsing('oddupper', function ($value) {
        return collect(mb_str_split($value))->map(function ($char, $index) {
            return $index % 2 === 0 ? strtoupper($char) : strtolower($char);
        })->join('');
    });

    expect($replacer->replace('Hello, {{name|oddupper}}!'))->toBe('Hello, JoHn!');
});

it('cannot add a modifier without a valid callback', function () {
    $replacer = new \App\Replacer\Replacer('name', 'John');

    expect(fn () => $replacer->modifierUsing('oddupper'))->toThrow(InvalidArgumentException::class);
});

it('can\'t replace none existing placeholder', function () {
    $replacer = new \App\Replacer\Replacer('name', 'John');

    expect($replacer->replace('Hello, {{unknown}}!'))->toBe('Hello, {{unknown}}!');
});

test('throw an exception when modifier does not exists', function () {
    $replacer = new \App\Replacer\Replacer('name', 'John');

    $replacer->replace('Hello, {{name|unknown}}!');
})->throws(InvalidArgumentException::class);

it('can use multiple modifiers', function () {
    $replacer = new \App\Replacer\Replacer('name', 'john doe');

    expect($replacer->replace('Hello, {{name|reverse,slug,ucfirst}}!'))->toBe('Hello, Eod-nhoj!');
});
