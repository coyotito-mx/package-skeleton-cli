<?php

declare(strict_types=1);

namespace App;

use App\Placeholders\Exceptions\PlaceholderNotFound;
use App\Placeholders\PlaceholderBuilder;
use Illuminate\Support\Str;

/**
 * PlaceholderB Builder class
 */
class PlaceholderReplacer extends Replacer
{
    protected PlaceholderBuilder $builder;

    protected array $placeholdersWithValue = [];

    public function __construct()
    {
        $this->builder = PlaceholderBuilder::make();
    }

    /**
     * Register a placeholder with a value to be replaced
     *
     * @param  class-string<\App\Placeholders\BasePlaceholder>  $placeholder
     * @param  string  $value  The value to be replaced
     */
    public function registerPlaceholderWithValue(string $placeholder, string $value): self
    {
        $this->builder->register($placeholder);

        $this->placeholdersWithValue[$placeholder::getName()] = $value;

        return $this;
    }

    /**
     * Get the placeholder pattern
     */
    protected function getPattern(): string
    {
        return '/(?<!{){{(?<expression>(?:[a-z0-9]+(?:-[a-z0-9]+)*)(?:\|(?:[^|,}\s]+(?:,[^|,}\s]+)*))?)}}(?!})/';
    }

    /**
     * Replace the placeholders in the give content
     */
    public function replace(string $content): string
    {
        return Str::replaceMatches(
            $this->getPattern(),
            function (array $matches) {
                $expression = $matches['expression'];

                try {
                    $placeholder = $this->builder->build($expression);
                } catch (PlaceholderNotFound) {
                    return $matches[0];
                }

                return $placeholder->process($this->placeholdersWithValue[$placeholder::getName()]);
            },
            $content
        );
    }
}
