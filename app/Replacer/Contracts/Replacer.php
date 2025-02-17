<?php

declare(strict_types=1);

namespace App\Replacer\Contracts;

interface Replacer
{
    public function __construct(string $placeholder, string $replacement);

    public function getPlaceholder(): string|array;

    public function getReplacement(): string;

    public function modifierUsing(string|array $modifier, \Closure $callback): static;

    public function getModifier(string $modifier): \Closure;

    public function getModifiers(): array;

    public function replace(string $text): string;
}
