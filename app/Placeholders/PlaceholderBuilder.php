<?php

declare(strict_types=1);

namespace App\Placeholders;

use App\Placeholders\Exceptions\PlaceholderNotFound;

/**
 * BasePlaceholder Builder
 */
class PlaceholderBuilder
{
    /**
     * A placeholder registry of the available placeholders
     *
     * @var class-string<BasePlaceholder>[]
     */
    protected array $placeholdersRegistry = [];

    /**
     * Constructor class
     */
    protected function __construct()
    {
        //
    }

    /**
     * Make a builder
     */
    public static function make(): self
    {
        return new self;
    }

    /**
     * Make a builder and register placeholders
     *
     * @param  class-string<BasePlaceholder>|class-string<BasePlaceholder>[]  $placeholder
     */
    public static function using(string|array $placeholder): self
    {
        return with(self::make(), fn (self $builder): self => $builder->register($placeholder));
    }

    /**
     * Register placeholders
     *
     * @param  class-string<BasePlaceholder>|class-string<BasePlaceholder>[]  $placeholder
     */
    public function register(string|array $placeholder): self
    {
        if (is_string($placeholder)) {
            return $this->register([$placeholder]);
        }

        foreach ($placeholder as $ph) {
            $this->placeholdersRegistry[$ph::getName()] = $ph;
        }

        return $this;
    }

    /**
     * Parse the given expresion to extract the placeholder name and modifiers
     *
     * @return array{placeholder: string, modifiers: array} The parsed result
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
     * @return BasePlaceholder The resolved placeholder
     *
     * @throws PlaceholderNotFound
     */
    private function resolvePlaceholder(string $placeholder, array $modifiers = []): BasePlaceholder
    {
        $placeholderClass = $this->placeholdersRegistry[$placeholder] ?? throw new PlaceholderNotFound("The placeholder [$placeholder] is not registered");

        return new $placeholderClass($modifiers);
    }

    public function build(string $expression): BasePlaceholder
    {
        ['placeholder' => $placeholder, 'modifiers' => $modifiers] = $this->parseExpression($expression);

        return $this->resolvePlaceholder($placeholder, $modifiers);
    }
}
