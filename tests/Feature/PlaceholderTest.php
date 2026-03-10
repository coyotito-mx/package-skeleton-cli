<?php

declare(strict_types=1);

use App\Placeholder;
use App\Placeholders\Modifiers\Concerns\HasName;
use App\Placeholders\Modifiers\Contracts\ModifierContract;
use App\Placeholders\Modifiers\Exceptions\ModifierNotRegistered;

function createPlaceholder(string $placeholder, array $modifiers = []): Placeholder
{
    return (static function (string $placeholderName, $modifiers): Placeholder {
        $classIdentifier = uniqid('TestingPlaceholder');

        $classDefinition = <<<PHP
        class $classIdentifier extends \App\Placeholder
        {
            public static function getName(): string
            {
                return "$placeholderName";
            }
        }
        PHP;

        eval($classDefinition);

        /** @phpstan-ignore-next-line */
        return new $classIdentifier($modifiers);
    })($placeholder, $modifiers);
}

it('process replacement', function (): void {
    $placeholder = createPlaceholder('foo');

    expect($placeholder)->process('Foo')->toBe('Foo');
});

it('apply one modifier', function (): void {
    $placeholder = createPlaceholder('foo', ['acronym']);

    expect($placeholder)
        ->process('Hewlett Packard')->toBe('HP')
        ->process('hewlett packard')->toBe('HP')
        ->process('HewLeTT pacKarD')->toBe('HP');
});

it('apply multiple modifiers', function (): void {
    $placeholder = createPlaceholder('foo', ['acronym', 'lower']);

    expect($placeholder)->process('Hewlett Packard')->toBe('hp');
});

it('can register modifier', function (): void {
    class TestModifier implements ModifierContract
    {
        use HasName;

        public function apply(string $replacement): string
        {
            return 'test';
        }
    }

    $placeholder = createPlaceholder('foo', ['test']);
    $placeholder->registerModifier(TestModifier::class);

    expect($placeholder)->process('John Doe')->toBe('test');
});

it('apply modifier', function (string $modifier, $replacement, $expected): void {
    $placeholder = createPlaceholder('foo', [$modifier]);

    expect($placeholder)->process($replacement)->toBe($expected);
})->with([
    'camelCase' => [
        'camel',
        'john doe',
        'johnDoe',
    ],
    'kebab-case' => [
        'kebab',
        'John Doe',
        'john-doe',
    ],
    'lowercase' => [
        'lower',
        'John Doe',
        'john doe',
    ],
    'PascalCase' => [
        'pascal',
        'John Doe',
        'JohnDoe',
    ],
    'slug-case' => [
        'slug',
        'John Doe',
        'john-doe',
    ],
    'snake_case' => [
        'snake',
        'John Doe',
        'john_doe',
    ],
    'StudlyCase' => [
        'studly',
        'John doe',
        'JohnDoe',
    ],
    'Ucfirstcase' => [
        'ucfirst',
        'john Doe',
        'John doe',
    ],
    'UPPERCASE' => [
        'upper',
        'john doe',
        'JOHN DOE',
    ],
]);

it('fail to apply non-register modifier', function (): void {
    $placeholder = createPlaceholder('foo', ['lower', 'unknown']);

    $placeholder->process('John Doe');
})->throws(ModifierNotRegistered::class);
