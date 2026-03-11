<?php

declare(strict_types=1);

namespace App;

use App\Placeholders\Exceptions\PlaceholderNotFound;
use Illuminate\Support\Arr;

/**
 * Placeholder Builder
 */
class PlaceholderBuilder
{
    /**+
     * The placeholder name
     */
    protected string $placeholder;

    /**
     * The modifiers
     */
    protected array $modifiers;

    /**
     * Registry of placeholders
     */
    protected static array $registry = [];

    /**
     * Class constructor
     */
    protected function __construct(string $expression)
    {
        ['placeholder' => $placeholder, 'modifiers' => $modifiers] = $this->parseExpression($expression);

        $this->placeholder = $placeholder;
        $this->modifiers = $modifiers;
    }

    /**
     * Create a builder
     */
    final public static function make(string $expression): self
    {
        return new self($expression);
    }

    /**
     * Register placeholder
     *
     * @param  class-string<Placeholder>|class-string<Placeholder>[]  $placeholder  The class string of a Placeholder
     */
    public static function register(string|array $placeholder): void
    {
        $placeholders = Arr::wrap($placeholder);

        foreach ($placeholders as $placeholder) {
            static::$registry[$placeholder::$name] = $placeholder;
        }
    }

    /**
     * Parse the given expresion to extract the placeholder name and modifiers (or not)
     */
    protected function parseExpression(string $expression): array
    {
        $parts = explode('|', $expression, 2);

        $placeholder = trim($parts[0]);

        // Process modifiers
        $modifiers = [];

        if (data_has($parts, 1)) {
            $modifiers = array_map(trim(...), explode(',', $parts[1]));
        }

        return [
            'placeholder' => $placeholder,
            'modifiers' => $modifiers,
        ];
    }

    /**
     * Resolve the placeholder from the given name
     *
     * @return class-string<Placeholder>
     *
     * @throws PlaceholderNotFound
     */
    private function resolvePlaceholder(string $name): string
    {
        return static::$registry[$name] ?? throw new PlaceholderNotFound("The placeholder [$name] is not registered");
    }

    /**
     * Build a the placeholder
     */
    public function build(): Placeholder
    {
        $resolved = $this->resolvePlaceholder($this->placeholder);

        return new $resolved($this->modifiers);
    }
}
