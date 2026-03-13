<?php

declare(strict_types=1);

namespace App;

use App\Placeholders\Exceptions\PlaceholderNotFound;
use App\Placeholders\PlaceholderBuilder;
use Illuminate\Support\Str;

final class Replacer
{
    protected PlaceholderBuilder $builder;

    protected array $placeholdersWithValue = [];

    public function __construct()
    {
        $this->builder = PlaceholderBuilder::make();
    }

    public function registerPlaceholderWithValue(string $placeholder, string $value): self
    {
        $this->builder->register($placeholder);

        $this->placeholdersWithValue[$placeholder::getName()] = $value;

        return $this;
    }

    public function getPlaceholderPattern(): string
    {
        return '/(?<!{){{(?<expression>(?:[a-z0-9]+(?:-[a-z0-9]+)*)(?:\|(?:[^|,}\s]+(?:,[^|,}\s]+)*))?)}}(?!})/';
    }

    public function replace(string $content): string
    {
        return Str::replaceMatches(
            $this->getPlaceholderPattern(),
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