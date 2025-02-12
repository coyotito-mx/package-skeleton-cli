<?php

declare(strict_types=1);

namespace App;

use Closure;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Override;
use Illuminate\Support\Str;

class Replacer implements Contracts\Replacer
{
    protected array $modifiers = [];

    #[Override]
    public function __construct(
        protected string|array $placeholder,
        protected string $replacement,
        protected string $openTag = '{{',
        protected string $closeTag = '}}',
    )
    {
        //
    }

    public function getPlaceholder(): string|array
    {
        return $this->placeholder;
    }

    public function getReplacement(): string
    {
        return $this->replacement;
    }

    public function modifierUsing(string $modifier, Closure $callback): static
    {
        $this->modifiers[$modifier] = $callback;

        return $this;
    }

    public function getModifier(string $modifier): Closure
    {
        if (! isset($this->modifiers[$modifier])) {
            throw new InvalidArgumentException("Modifier [{$modifier}] is not defined.");
        }

        return $this->modifiers[$modifier];
    }

    public function getModifiers(): array
    {
        return $this->modifiers;
    }

    public function replace(string $text): string
    {
        $placeholder = $this->wrapPlaceholder($this->placeholder);
        $placeholdersReplacements = [
            $placeholder => $this->getReplacement(),
            ...$this->getPlaceholdersReplacements()
        ];

        return Str::of($text)
            ->replace(
                collect($placeholdersReplacements)->keys()->toArray(),
                collect($placeholdersReplacements)->values()->toArray(),
            )
            ->toString();
    }

    protected function getPlaceholdersReplacements(): array
    {
        return Arr::mapWithKeys(
            $this->getModifiers(),
            fn (\Closure $cb, string $modifier) => [
                $this->wrapPlaceholder($this->getPlaceholder().'|'.$modifier) => $cb($this->getReplacement()),
            ]
        );
    }

    protected function wrapPlaceholder(string $placeholder): string
    {
        return Str::of($placeholder)
            ->prepend($this->openTag)
            ->append($this->closeTag)
            ->toString();
    }

    protected function unwrapPlaceholder(string $placeholder): string
    {
        return Str::of($placeholder)
            ->replace($this->openTag, '')
            ->replace($this->closeTag, '')
            ->trim()
            ->toString();
    }
}
