<?php

declare(strict_types=1);

namespace App\Replacer\Contracts;

use Closure;
use InvalidArgumentException;

interface Replacer
{
    /**
     * Replacer class constructor
     *
     * @param string $placeholder The placeholder(s) to search for
     * @param string $replacement The value from which the placeholder will be replaced
     * @return mixed
     */
    public function __construct(string|array $placeholder, string $replacement);

    /**
     * The placeholder(s) to match
     */
    public function getPlaceholder(): string|array;

    /**
     * The value from which the placeholder will be replaced
     */
    public function getReplacement(): string;

    /**
     * Define new modifier(s)
     *
     * When an array is provided, the key is the modifier's name, and the value must be the callback, which will
     * be executed when the modifier is used.
     */
    public function modifierUsing(string|array $modifier, \Closure $callback): static;

    /**
     * Get the registered modifier
     *
     * @throws InvalidArgumentException if modifier's name is not registered
     */
    public function getModifier(string $name): \Closure;

    /**
     * Get all the modifiers registered
     */
    public function getModifiers(): array;

    /**
     * Replace the placeholder(s) in the given text
     */
    public function replace(string $text): string;
}
