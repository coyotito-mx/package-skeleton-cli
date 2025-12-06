<?php

namespace App\Replacers;

use App\Replacer;
use App\Replacers\Exceptions\InvalidNamespace;
use Exception;
use Illuminate\Support\Stringable;

abstract class Builder
{
    /**
     * Constructor
     *
     * @param string $namespace
     * @throws Exception if the namespace is invalid
     */
    public function __construct(protected string $namespace)
    {
        InvalidNamespace::verification($namespace);
    }

    /**
     * Create a new replacer instance
     *
     * @throws Exception if the namespace is invalid
     */
    public static function make(string $namespace): Replacer
    {
        return new static($namespace)->build();
    }

    /*
     * Build the replacer instance
     */
    protected function build(): Replacer
    {
        $replacer = Replacer::make(static::getPlaceholder(), $this->namespace);

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
    abstract protected function modifiers(): array;

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
