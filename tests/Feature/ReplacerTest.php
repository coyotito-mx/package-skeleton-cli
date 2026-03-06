<?php

use App\Replacers\Replacer;

it('replace placeholder', function (): void {
    $replacer = Replacer::make('name', 'john doe');

    expect($replacer)
        ->replace('Hello, {{name}}!')->toBe('Hello, John Doe!');
});

it('cannot replace unknown placeholder', function (): void {
    $replacer = Replacer::make('name', 'john doe');

    expect($replacer)
        ->replace('Hello, {{username}}!')->toBe('Hello, {{username}}!');
});

it('replace placeholder with modifiers', function (string $modifier, string $expected): void {
    $replacer = Replacer::make('name', 'john doe');

    expect($replacer)
        ->replace("Hello, {{name|$modifier}}!")->toBe("Hello, $expected!");
})->with([
    'upper modifier' => [
        'modifier' => 'upper',
        'expected' => 'JOHN DOE',
    ],
    'lower modifier' => [
        'modifier' => 'lower',
        'expected' => 'john doe',
    ],
    'title modifier' => [
        'modifier' => 'title',
        'expected' => 'John Doe',
    ],
    'snake modifier' => [
        'modifier' => 'snake',
        'expected' => 'john_doe',
    ],
    'kebab modifier' => [
        'modifier' => 'kebab',
        'expected' => 'john-doe',
    ],
    'camel modifier' => [
        'modifier' => 'camel',
        'expected' => 'johnDoe',
    ],
    'slug modifier' => [
        'modifier' => 'slug',
        'expected' => 'john-doe',
    ],
    'acronym modifier' => [
        'modifier' => 'acronym',
        'expected' => 'JD',
    ],
]);

it('replace placeholder with multiple modifiers', function (array $modifiers, string $expected): void {
    $replacer = Replacer::make('name', 'john doe');

    $modifiers = implode(',', $modifiers);

    expect($replacer)
        ->replace("Hello, {{name|$modifiers}}")->toBe("Hello, $expected");
})->with([
    'upper then slug' => [
        'modifiers' => ['upper', 'slug'],
        'expected' => 'john-doe',
    ],
    'lower then slug' => [
        'modifiers' => ['lower', 'slug'],
        'expected' => 'john-doe',
    ],
    'title then upper' => [
        'modifiers' => ['title', 'upper'],
        'expected' => 'JOHN DOE',
    ],
    'slug then upper' => [
        'modifiers' => ['slug', 'upper'],
        'expected' => 'JOHN-DOE',
    ],
    'slug then lower' => [
        'modifiers' => ['slug', 'lower'],
        'expected' => 'john-doe',
    ],
    'acronym then upper' => [
        'modifiers' => ['acronym', 'lower'],
        'expected' => 'jd',
    ],
]);

it('ignores duplicate modifiers after the first occurrence', function (array $modifiers, string $expected): void {
    $replacer = Replacer::make('name', 'john doe');

    $modifiers = implode(',', $modifiers);

    expect($replacer)
        ->replace("Hello, {{name|$modifiers}}!")->toBe("Hello, $expected!");
})->with([
    'duplicate upper' => [
        'modifiers' => ['upper', 'lower', 'upper'],
        'expected' => 'john doe',
    ],
    'duplicate lower' => [
        'modifiers' => ['lower', 'upper', 'lower'],
        'expected' => 'JOHN DOE',
    ],
    'duplicate slug' => [
        'modifiers' => ['slug', 'upper', 'slug'],
        'expected' => 'JOHN-DOE',
    ],
    'duplicate slug lower' => [
        'modifiers' => ['slug', 'lower', 'slug'],
        'expected' => 'john-doe',
    ],
    'duplicate title' => [
        'modifiers' => ['title', 'upper', 'title'],
        'expected' => 'JOHN DOE',
    ],
    'mixed duplicates' => [
        'modifiers' => ['upper', 'slug', 'upper', 'snake', 'lower', 'slug'],
        'expected' => 'john-doe',
    ],
    'mixed duplicates 2' => [
        'modifiers' => ['lower', 'camel', 'lower', 'upper', 'camel', 'snake'],
        'expected' => 'j_o_h_n_d_o_e',
    ],
]);

it('cannot replace malformed placeholder', function (string $malformedPlaceholder): void {
    $replacer = Replacer::make('name', 'john doe');

    expect($replacer)
        ->replace("Hello, $malformedPlaceholder!")->toBe("Hello, $malformedPlaceholder!");
})->with([
    'missing opening brace' => '{name}}',
    'missing closing brace' => '{{name',
    'no braces' => 'name!',
    'extra braces' => '{{{name}}}',
    'spaces in braces' => '{{ name }}',
]);

it('cannot replace placeholder with malformed modifiers', function (string $malformedModifiers): void {
    $replacer = Replacer::make('name', 'john doe');

    expect($replacer)
        ->replace("Hello, $malformedModifiers!")->toBe("Hello, $malformedModifiers!");
})->with([
    'empty modifier' => '{{name|}}',
    'trailing comma' => '{{name|unknown,}}',
    'trailing pipe' => '{{name|upper|}}',
    'leading comma' => '{{name|,lower}}',
    'multiple pipes' => '{{name|upper||lower}}',
    'multiple commas' => '{{name|upper,,lower}}',
]);

it('register custom modifier', function (): void {
    $replacer = tap(Replacer::make('name', 'john doe'))
        ->addModifier('reverse', fn (\Illuminate\Support\Stringable $replacement) => $replacement->reverse());

    expect($replacer)
        ->replace('Hello, {{name|reverse}}!')->toBe('Hello, eoD nhoJ!');
});

it('permit only specific modifiers', function (string $modifier, ?string $expected, bool $isIncluded): void {
    $replacer = Replacer::make('name', 'john doe')->only(null);

    if (! $isIncluded && is_null($expected)) {
        $expected = 'John Doe';
    } else {
        $replacer->only([$modifier]);
    }

    expect($replacer)
        ->replace("Hello, {{name|$modifier}}!")->toBe("Hello, $expected!");
})->with([
    'upper modifier' => [
        'modifier' => 'upper',
        'expected' => 'JOHN DOE',
        'isIncluded' => true,
    ],
    'lower modifier' => [
        'modifier' => 'lower',
        'expected' => 'john doe',
        'isIncluded' => true,
    ],
    'slug modifier non-included' => [
        'modifier' => 'slug',
        'expected' => null,
        'isIncluded' => false,
    ],
    'title modifier non-included' => [
        'modifier' => 'title',
        'expected' => null,
        'isIncluded' => false,
    ],
    'kebab modifier non-included' => [
        'modifier' => 'kebab',
        'expected' => null,
        'isIncluded' => false,
    ],
    'slug modifiers' => [
        'modifier' => 'slug',
        'expected' => 'john-doe',
        'isIncluded' => true,
    ],
    'camel modifier non-included' => [
        'modifier' => 'camel',
        'expected' => null,
        'isIncluded' => false,
    ],
    'acronym modifier' => [
        'modifier' => 'acronym',
        'expected' => 'JD',
        'isIncluded' => true,
    ],
    'snake modifier' => [
        'modifier' => 'snake',
        'expected' => 'john_doe',
        'isIncluded' => true,
    ],
    'acronym modifier non-included' => [
        'modifier' => 'acronym',
        'expected' => null,
        'isIncluded' => false,
    ],
]);

it('use only specified modifiers', function (): void {
    $replacer = tap(Replacer::make('name', 'john doe'))
        ->only(['emoji'])
        ->addModifier('emoji', fn (\Illuminate\Support\Stringable $replacement): \Illuminate\Support\Stringable => $replacement->append(' 😊'));

    expect($replacer)
        ->replace('Hello, {{name|upper}}!')->toBe('Hello, John Doe!')
        ->replace('Hello, {{name|emoji}}')->toBe('Hello, John Doe 😊')
        ->replace('Hello, {{name|slug}}!')->toBe('Hello, John Doe!');
});

it('exclude modifiers', function (): void {
    $replacer = tap(Replacer::make('name', 'john doe'))
        ->excludeModifiers(['upper', 'lower']);

    expect($replacer)
        ->replace('Hello, {{name|upper}}!')->toBe('Hello, John Doe!')
        ->replace('Hello, {{name|lower}}!')->toBe('Hello, John Doe!')
        ->replace('Hello, {{name|slug}}!')->toBe('Hello, john-doe!')
        ->excludeModifiers(['slug'])
        ->replace('Hello, {{name|slug}}!')->toBe('Hello, John Doe!');
});
