<?php

declare(strict_types=1);

namespace App;

use App\Contracts\ComposerContract;
use PHPUnit\Framework as PHPUnit;

class ComposerTestable implements ComposerContract
{
    public ?string $cwd = null;

    public array $required = [];

    public function require(string|array $package, bool $dev = false, bool $withAllDependencies = false): bool
    {
        $this->required = collect($package)
            ->mapWithKeys(fn (string $package) => [$package => $dev])
            ->toArray();

        return true;
    }

    public function assertPackageInstalled(string $package, bool $dev = false): void
    {
        PHPUnit\Assert::assertContainsEquals([$package => $dev], $this->required);
    }

    public function assertNothingInstalled(): void
    {
        PHPUnit\assertEmpty($this->required);
    }
}
