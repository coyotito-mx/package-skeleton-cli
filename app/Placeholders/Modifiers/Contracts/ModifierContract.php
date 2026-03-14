<?php

declare(strict_types=1);

namespace App\Placeholders\Modifiers\Contracts;

interface ModifierContract
{
    /**
     * Apply the modifier to the given modifier
     *
     * @return string The modified value
     */
    public function apply(string $value): string;

    /**
     * The name of the modifier
     */
    public static function getName(): string;
}
