<?php

declare(strict_types=1);

namespace App\Placeholders;

use App\Placeholders\Modifiers\Contracts\ModifierContract;
use App\Placeholders\Modifiers\Exceptions\ModifierNotRegistered;
use Illuminate\Support\Arr;

/**
 * Base class for placeholders
 */
abstract class BasePlaceholder
{
    /**
     * The modifier's registry
     *
     * @var array<string, class-string<ModifierContract>>
     */
    protected array $modifiersRegistry = [];

    protected ?\Closure $modifierResolver = null;

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
    protected static function getDefaultModifiers(): array
    {
        return [
            //
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
        return $this->getModifierResolver()->call($this, name: $name, arg: $arg);
    }

    /**
     * Hook to pre-process the `$replacement`
     */
    protected function preProcess(string $replacement): string
    {
        return $replacement;
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

    /**
     * Get the modifier class resolver
     *
     * @return \Closure(string $name, ?string $arg): ModifierContract The modifier resolver
     */
    protected function getModifierResolver(): \Closure
    {
        return $this->modifierResolver ?? function (string $name, ?string $arg): ModifierContract {
            if (! isset($this->modifiersRegistry[$name])) {
                throw new ModifierNotRegistered("The provided modifier [$name] is not registered in the placeholder ".class_basename($this).'::class');
            }

            return new $this->modifiersRegistry[$name]($arg);
        };
    }

    /**
     * Set the closure to handle the modifier resolution using the current object context
     *
     * @param  \Closure(string $name, ?string $arg): ModifierContract  $resolver
     */
    public function setModifierResolverUsing(?\Closure $resolver = null): void
    {
        $this->modifierResolver = $resolver;
    }

    abstract public static function getName(): string;
}
