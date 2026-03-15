<?php

declare(strict_types=1);

use App\Placeholders\BasePlaceholder;
use App\Placeholders\Modifiers\AcronymModifier;
use App\Placeholders\Modifiers\Concerns\HasName;
use App\Placeholders\Modifiers\Contracts\ModifierContract;
use App\Placeholders\Modifiers\Exceptions\ModifierNotRegistered;
use App\Placeholders\Modifiers\LowerModifier;
use App\Placeholders\Modifiers\SlugModifier;
use App\Placeholders\Modifiers\UCFirstModifier;
use App\Placeholders\Modifiers\UpperModifier;

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

it('can setup default modifiers', function (): void {
    $placeholder = new class(['upper']) extends BasePlaceholder
    {
        #[\Override]
        protected static function getDefaultModifiers(): array
        {
            return [
                UpperModifier::class,
            ];
        }

        public static function getName(): string
        {
            return 'foo';
        }
    };

    expect($placeholder)->process('john doe')->toBe('JOHN DOE');
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
})->with(fn () => getModifierDataset([
    'camel',
    'kebab',
    'lower',
    'pascal',
    'slug',
    'snake',
    'studly',
    'ucfirst',
    'upper',
    'acronym',
]));

it('fail to apply non-register modifier', function (): void {
    $placeholder = createplaceholder('foo', ['lower']);

    $placeholder->process('john doe');
})->throws(ModifierNotRegistered::class);

test('modifiers order matters', function (string $expected, array $modifiers): void {
    $placeholder = createplaceholder('foo', $modifiers);

    $placeholder->registerModifier([
        SlugModifier::class,
        UCFirstModifier::class,
        UpperModifier::class,
    ]);

    expect($placeholder)->process('John Doe')->toBe($expected);
})->with([
    'slug and ucfirst' => ['John-doe', ['slug', 'ucfirst']],
    'ucfirst and slug' => ['john-doe', ['ucfirst', 'slug']],
    'upper and slug' => ['john-doe', ['upper', 'slug']],
    'slug and upper' => ['JOHN-DOE', ['slug', 'upper']],
]);
