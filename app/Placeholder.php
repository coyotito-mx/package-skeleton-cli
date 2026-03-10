<?php

declare(strict_types=1);

namespace App;

use App\Placeholders\Modifiers\AcronymModifier;
use App\Placeholders\Modifiers\CamelModifier;
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
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Base class for placeholders
 */
abstract class Placeholder
{
    /**
     * The modifier's registry
     *
     * @var array<string, class-string<ModifierContract>>
     */
    protected array $modifiersRegistry = [];

    /**
     * Contructor class
     */
    public function __construct(protected(set) array $modifiers = [])
    {
        $this->registerDefaultModifiers();
    }

    /**
     * Register modifier(s)
     *
     * @param  class-string<ModifierContract>|class-string<ModifierContract>[]  $modifier  The modifier to register
     */
    public function registerModifier(string|array $modifier): static
    {
        $modifiers = Arr::wrap($modifier);

        foreach ($modifiers as $modifier) {
            $this->modifiersRegistry[$modifier::getName()] = $modifier;
        }

        return $this;
    }

    /**
     * Get the default modifiers
     *
     * @return class-string<ModifierContract>[]
     */
    final protected static function getDefaultModifiers(): array
    {
        return [
            AcronymModifier::class,
            CamelModifier::class,
            KebabModifier::class,
            LowerModifier::class,
            PascalModifier::class,
            SlugModifier::class,
            SnakeModifier::class,
            StudlyModifier::class,
            UCFirstModifier::class,
            UpperModifier::class,
        ];
    }

    /**
     * Register the default modifiers
     */
    protected function registerDefaultModifiers(): void
    {
        foreach (static::getDefaultModifiers() as $modifier) {
            $this->registerModifier($modifier);
        }
    }

    /**
     * Parse a list of strings modifier
     *
     * @return array<string, ?string>
     */
    protected function parseModifiers(array $modifiers): array
    {
        $parsed = [];

        foreach ($modifiers as $modifier) {
            $parts = explode(':', (string) $modifier, 2);

            $name = $parts[0];
            $parsed[$name] = $parts[1] ?? null;
        }

        return $parsed;
    }

    /**
     * Resolve the given modifier's name with the argument
     *
     * @return ModifierContract The resolved modifier
     *
     * @throws ModifierNotRegistered
     */
    protected function resolveModifier(string $name, ?string $arg = null): ModifierContract
    {
        if (! isset($this->modifiersRegistry[$name])) {
            throw new ModifierNotRegistered("The provided modifier [$name] is not registered in the placeholder ".class_basename($this).'::class');
        }

        return new $this->modifiersRegistry[$name]($arg);
    }

    /**
     * Hook to pre-process the `$replacement`
     */
    protected function preProcess(string $replacement): string
    {
        return Str::headline($replacement);
    }

    /**
     * Process the `$replacement` using the setup modifiers
     */
    public function process(string $replacement): string
    {
        $modifiers = $this->parseModifiers($this->modifiers);
        $replacement = $this->preProcess($replacement);

        foreach ($modifiers as $name => $arg) {
            $modifier = $this->resolveModifier($name, $arg);

            $replacement = $modifier->apply($replacement);
        }

        return $replacement;
    }

    abstract public static function getName(): string;
}
