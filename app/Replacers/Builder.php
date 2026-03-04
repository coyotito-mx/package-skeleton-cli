<?php

declare(strict_types=1);

namespace App\Replacers;

use App\Replacer;
use App\Replacers\Exceptions\InvalidFormatException;
use Closure;
use Exception;
use Illuminate\Support\Stringable;

abstract class Builder
{
    /**
     * Validation format exception
     *
     * This class string represents the exception used to validate the replacement.
     *
     * @var ?class-string<InvalidFormatException>
     */
    protected static ?string $invalidFormatException = null;

    /**
     * Constructor
     *
     * @throws Exception If the replacement format is invalid.
     */
    final public function __construct(protected string $replacement, protected Replacer $replacer)
    {
        if (static::$invalidFormatException) {
            static::$invalidFormatException::validate($this->replacement);
        }
    }

    /**
     * Create a new replacer instance.
     *
     * @return Replacer The configured replacer instance.
     *
     * @throws Exception If the replacement format is invalid.
     */
    public static function make(string $replacement): Replacer
    {
        return new static($replacement, Replacer::make(static::getPlaceholder(), $replacement))->build();
    }

    /**
     * Build the replacer instance.
     */
    public function build(): Replacer
    {
        return $this->configure();
    }

    /**
     * Configure the replacer with modifiers
     */
    protected function configure(): Replacer
    {
        $this->setupModifiers(
            customModifiers: $this->modifiers(),
            modifiersToExclude: $this->getExcludedModifiers()
        );

        return $this->replacer;
    }

    /**
     * Setup the modifiers for the replacer
     *
     * @param  array<string, \Closure(Stringable $replacement): Stringable>  $customModifiers
     * @param  string[]  $modifiersToExclude
     */
    final protected function setupModifiers(array $customModifiers = [], array $modifiersToExclude = []): void
    {
        foreach ($customModifiers as $name => $callback) {
            $this->replacer->addModifier($name, $callback);
        }

        $this->replacer->excludeModifiers($modifiersToExclude);
    }

    /**
     * The array of modifiers to be added to the replacer
     *
     * @return array<string, Closure(Stringable $replacement): Stringable>
     */
    public function modifiers(): array
    {
        return [];
    }

    /**
     * Get the list of excluded modifiers
     *
     * @return list<string>
     */
    public function getExcludedModifiers(): array
    {
        return [];
    }

    /**
     * Get the placeholder string
     *
     * @throws \RuntimeException if the placeholder is not defined in the subclass
     */
    final public static function getPlaceholder(): string
    {
        /** @phpstan-ignore-next-line */
        return static::$placeholder ?? throw new \RuntimeException('The placeholder is not defined');
    }
}
