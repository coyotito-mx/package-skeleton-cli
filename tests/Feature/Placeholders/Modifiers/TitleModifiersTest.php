<?php

declare(strict_types=1);

use App\Placeholders\Modifiers\TitleModifier;

it('modify value', function (): void {
    expect(new TitleModifier)
        ->apply('HelloWorld')
        ->toBe('Hello World')
        ->apply('hello world')
        ->toBe('Hello World')
        ->apply('hello-world')
        ->toBe('Hello World');
});
