<?php

declare(strict_types=1);

use App\Placeholders\BasePlaceholder;
use App\Placeholders\Exceptions\PlaceholderNotFound;
use App\Placeholders\PlaceholderBuilder;
use PHPUnit\Framework as PHPunit;

function callNonAccessibleMethod(PlaceholderBuilder $builder, string $method, ...$args): mixed
{
    return (fn (...$args) => $this->$method(...$args))->call($builder, ...$args);
}

function assertPropertyIsEqual(PlaceholderBuilder $builder, string $property, mixed $value): void
{
    (fn () => PHPUnit\Assert::assertEquals($this->$property, $value, "The property \${$property} and the value provided are not equal"))->call($builder);
}

it('can register placeholder', function (): void {
    $pleceholderClass = createPlaceholderClass('foo');

    $builder = PlaceholderBuilder::make()->register($pleceholderClass);

    assertPropertyIsEqual($builder, 'placeholdersRegistry', [
        $pleceholderClass::getName() => $pleceholderClass,
    ]);
});

it('can parse placeholder expression', function (): void {
    $builder = PlaceholderBuilder::make();

    expect(callNonAccessibleMethod($builder, 'parseExpression', 'foo|bar,buzz'))->toBe([
        'placeholder' => 'foo',
        'modifiers' => ['bar', 'buzz'],
    ]);
});

it('can resolve placeholder', function (): void {
    $pleceholderClass = createPlaceholderClass('foo');

    $builder = PlaceholderBuilder::using($pleceholderClass);

    expect(
        callNonAccessibleMethod($builder, 'resolvePlaceholder', placeholder: 'foo')
    )->toBeInstanceOf(BasePlaceholder::class);
});

it('can build placeholder', function (): void {
    $pleceholderClass = createPlaceholderClass('foo');

    $builder = PlaceholderBuilder::make()->register($pleceholderClass);

    assertPropertyIsEqual($builder, 'placeholdersRegistry', [
        $pleceholderClass::getName() => $pleceholderClass,
    ]);
});

it('fail to build non-registered placeholder', function (): void {
    PlaceholderBuilder::make()->build('foo');
})->throws(PlaceholderNotFound::class);
