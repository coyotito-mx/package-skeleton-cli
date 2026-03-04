<?php

declare(strict_types=1);

namespace App;

use App\Contracts\ComposerContract;
use PHPUnit\Framework as PHPUnit;

class ComposerTestable implements ComposerContract
{
    public ?string $cwd = null;

    public array $required = [];

    public array $config = [
        'allow-plugins' => [],
    ];

    public function require(string|array $package, bool $dev = false, bool $withAllDependencies = false): bool
    {
        $this->required = collect($package)
            ->mapWithKeys(fn (string $package) => [$package => $dev])
            ->toArray();

        return true;
    }

    public function allowPlugin(string $plugin, bool $allow = true): void
    {
        $this->setPluginConfig($plugin, $allow);
    }

    public function setPluginConfig(string $plugin, bool $allow): void
    {
        $this->config['allow-plugins'][$plugin] = $allow;
    }

    public function getPluginConfig(string $plugin): ?bool
    {
        return $this->config['allow-plugins'][$plugin] ?? null;
    }

    public function assertPackageInstalled(string $package, bool $dev = false): void
    {
        PHPUnit\Assert::assertContainsEquals([$package => $dev], $this->required);
    }

    public function assertNothingInstalled(): void
    {
        PHPUnit\Assert::assertEmpty($this->required);
    }

    public function assertPluginIsAllowed(string $plugin): void
    {
        PHPUnit\Assert::assertTrue($this->getPluginConfig($plugin), "Plugin [{$plugin}] is not allowed.");
    }

    public function assertPluginIsNotAllowed(string $plugin): void
    {
        PHPUnit\Assert::assertFalse($this->getPluginConfig($plugin), "Plugin [{$plugin}] is allowed.");
    }
}
