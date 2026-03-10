<?php

declare(strict_types=1);

use App\Placeholder;
use App\Placeholders\Modifiers\AcronymModifier;
use App\Placeholders\Modifiers\CamelModifier;
use App\Placeholders\Modifiers\Concerns\HasName;
use App\Placeholders\Modifiers\Contracts\ModifierContract;
use App\Placeholders\Modifiers\Exceptions\ModifierNotRegistered;
use App\Placeholders\Modifiers\KebabModifier;
use App\Placeholders\Modifiers\LowerModifier;
use App\Placeholders\Modifiers\PascalModifier;
use App\Placeholders\Modifiers\SlugModifier;
use App\Placeholders\Modifiers\SnakeModifier;
use App\Placeholders\Modifiers\StudlyModifier;
use App\Placeholders\Modifiers\UCFirstModifier;
use App\Placeholders\Modifiers\UpperModifier;

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

it('apply one modifier', function (): void {
    $placeholder = createPlaceholder('foo', ['acronym']);

    $placeholder->registerModifier(AcronymModifier::class);

    expect($placeholder)
        ->process('Hewlett Packard')->toBe('HP')
        ->process('hewlett packard')->toBe('HP')
        ->process('HewLeTT pacKarD')->toBe('HP');
});

it('apply multiple modifiers', function (): void {
    $placeholder = createPlaceholder('foo', ['acronym', 'lower']);

    $placeholder->registerModifier([
        AcronymModifier::class,
        LowerModifier::class,
    ]);

    expect($placeholder)->process('Hewlett Packard')->toBe('hp');
});

it('apply modifier', function (string $modifier, string $replacement, $expected): void {
    /** @var class-string<\App\Placeholders\Modifiers\Contracts\ModifierContract $modifer */
    $placeholder = createPlaceholder('foo', [$modifier::getName()]);

    $placeholder->registerModifier($modifier);

    expect($placeholder)->process($replacement)->toBe($expected);
})->with([
    'camelCase' => [
        CamelModifier::class,
        'john doe',
        'johnDoe',
    ],
    'kebab-case' => [
        KebabModifier::class,
        'John Doe',
        'john-doe',
    ],
    'lowercase' => [
        LowerModifier::class,
        'John Doe',
        'john doe',
    ],
    'PascalCase' => [
        PascalModifier::class,
        'John Doe',
        'JohnDoe',
    ],
    'slug-case' => [
        SlugModifier::class,
        'John Doe',
        'john-doe',
    ],
    'snake_case' => [
        SnakeModifier::class,
        'John Doe',
        'john_doe',
    ],
    'StudlyCase' => [
        StudlyModifier::class,
        'John doe',
        'JohnDoe',
    ],
    'Ucfirstcase' => [
        UCFirstModifier::class,
        'john Doe',
        'John doe',
    ],
    'UPPERCASE' => [
        UpperModifier::class,
        'john doe',
        'JOHN DOE',
    ],
]);

it('fail to apply non-register modifier', function (): void {
    $placeholder = createPlaceholder('foo', ['lower', 'unknown']);

    $placeholder->process('John Doe');
})->throws(ModifierNotRegistered::class);
