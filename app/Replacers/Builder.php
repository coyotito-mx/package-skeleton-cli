<?php

declare(strict_types=1);

namespace App\Replacers;

use App\Replacer;
use Exception;
use Illuminate\Support\Stringable;

abstract class Builder
{
    /**
     * Constructor
     *
     * @throws Exception if the namespace is invalid
     */
    public function __construct(protected string $replacement)
    {
        //
    }

    /**
     * Create a new replacer instance
     *
     * @throws Exception if the namespace is invalid
     */
    public static function make(string $replacement): Replacer
    {
        return new static($replacement)->build();
    }

    /*
     * Build the replacer instance
     */
    protected function build(): Replacer
    {
        $replacer = Replacer::make(static::getPlaceholder(), $this->replacement);

        $this->configure($replacer);

        return $replacer;
    }

    /**
     * Configure the replacer with modifiers
     */
    protected function configure(Replacer $replacer): void
    {
        $this->setupModifiers($replacer);
    }

    /**
     * Setup the modifiers for the replacer
     */
    protected function setupModifiers(Replacer $replacer): void
    {
        foreach ($this->modifiers() as $name => $callback) {
            $replacer->addModifier($name, $callback);
        }

        $replacer->excludeModifiers(
            $this->getExcludedModifiers()
        );
    }

    /**
     * The array of modifiers to be added to the replacer
     *
     * @return array<string, Closure(Stringable $replacement): Stringable
     */
    protected function modifiers(): array
    {
        return [];
    }

    /**
     * Get the list of excluded modifiers
     *
     * @return list<string>
     */
    protected function getExcludedModifiers(): array
    {
        return [];
    }

    /**
     * Get the placeholder string
     */
    public static function getPlaceholder(): string
    {
        return static::$placeholder ?? throw new Exception('Placeholder not defined in subclass.');
    }
}
