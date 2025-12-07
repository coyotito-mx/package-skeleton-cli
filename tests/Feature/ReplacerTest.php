<?php

use App\Replacer;

it('replace placeholder', function () {
    $replacer = Replacer::make('name', 'john doe');

    expect($replacer)
        ->replace('Hello, {{name}}!')->toBe('Hello, John Doe!')
        ->replace('No placeholder here.')->toBe('No placeholder here.');
});

it('replace placeholder with modifiers', function () {
    $replacer = Replacer::make('name', 'john doe');

    expect($replacer)
        ->replace('Hello, {{name|upper}}!')->toBe('Hello, JOHN DOE!')
        ->replace('Hello, {{name|lower}}!')->toBe('Hello, john doe!')
        ->replace('Hello, {{name|title}}!')->toBe('Hello, John Doe!')
        ->replace('Hello, {{name|snake}}!')->toBe('Hello, john_doe!')
        ->replace('Hello, {{name|kebab}}!')->toBe('Hello, john-doe!')
        ->replace('Hello, {{name|camel}}!')->toBe('Hello, johnDoe!')
        ->replace('Hello, {{name|slug}}!')->toBe('Hello, john-doe!');

    $replacer = Replacer::make('name', 'mit');

    expect($replacer)
        ->replace('Hello, {{name|acronym}}!')->toBe('Hello, MIT!');
});

it('replace placeholder with multiple modifiers', function () {
    $replacer = Replacer::make('name', 'john doe');

    expect($replacer)
        ->replace('Hello, {{name|upper,lower}}!')->toBe('Hello, john doe!')
        ->replace('Hello, {{name|lower,upper}}!')->toBe('Hello, JOHN DOE!')
        ->replace('Hello, {{name|title,upper}}!')->toBe('Hello, JOHN DOE!')
        ->replace('Hello, {{name|title,lower}}!')->toBe('Hello, john doe!');
});

it('cannot replace malformed placeholder', function () {
    $replacer = Replacer::make('name', 'john doe');

    expect($replacer)
        ->replace('Hello, {name}}!')->toBe('Hello, {name}}!')
        ->replace('Hello, {{name!')->toBe('Hello, {{name!');
});

it('cannot replace placeholder with malformed modifiers', function () {
    $replacer = Replacer::make('name', 'john doe');

    expect($replacer)
        ->replace('Hello, {{name|}}!')->toBe('Hello, {{name|}}!')
        ->replace('Hello, {{name|unknown,}}!')->toBe('Hello, {{name|unknown,}}!')
        ->replace('Hello, {{name|upper|}}!')->toBe('Hello, {{name|upper|}}!')
        ->replace('Hello, {{name|,lower}}!')->toBe('Hello, {{name|,lower}}!');
});

it('register custom modifier', function () {
    $replacer = tap(Replacer::make('name', 'john doe'))
        ->addModifier('reverse', fn (\Illuminate\Support\Stringable $replacement) => $replacement->reverse());

    expect($replacer)
        ->replace('Hello, {{name|reverse}}!')->toBe('Hello, eoD nhoJ!');
});

it('exclude modifiers', function () {
    $replacer = tap(Replacer::make('name', 'john doe'))
        ->excludeModifiers(['upper', 'lower']);

    expect($replacer)
        ->replace('Hello, {{name|upper}}!')->toBe('Hello, John Doe!')
        ->replace('Hello, {{name|lower}}!')->toBe('Hello, John Doe!')
        ->replace('Hello, {{name|slug}}!')->toBe('Hello, john-doe!')
        ->excludeModifiers(['slug'])
        ->replace('Hello, {{name|slug}}!')->toBe('Hello, John Doe!');
});
