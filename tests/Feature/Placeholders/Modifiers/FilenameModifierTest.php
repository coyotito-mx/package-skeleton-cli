<?php

declare(strict_types=1);

use App\Placeholders\Modifiers\FilenameModifier;

it('modify value', function (): void {
    expect(new FilenameModifier)
        ->apply('HelloWorld.php')
        ->toBe('hello-world.php')
        ->apply('Hello World')
        ->toBe('hello-world');
});
