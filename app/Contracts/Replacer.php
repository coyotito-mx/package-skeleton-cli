<?php

declare(strict_types=1);

namespace App\Contracts;

interface Replacer
{
    public function __construct(string|array $placeholder, string $replacement);

    public function getPlaceholder(): string|array;

    public function getReplacement(): string;

    public function modifierUsing(string $modifier, \Closure $callback): static;

    public function getModifier(string $modifier): \Closure;

    public function getModifiers(): array;

    public function replace(string $text): string;
}
