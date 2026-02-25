<?php

declare(strict_types=1);

namespace App\Contracts;

interface ComposerContract
{
    public ?string $cwd { get; set; }

    /**
     * Runs a composer require with the provided package.
     */
    public function require(string|array $package, bool $dev = false, bool $withAllDependencies = false): bool;
}
