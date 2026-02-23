<?php

namespace App\DependencyManagers;

use PHPUnit\Framework as PHPUnit;
use App\DependencyManagers\Contracts\DependencyManagerContract;

class ComposerFake implements DependencyManagerContract
{
    /**
     * The required dependencies with their versions and installation status.
     *
     * @var array<string, array{version: string, installed: bool}>
     */
    protected array $required = [];

    /**
     * The required dev dependencies with their versions and installation status.
     *
     * @var array<string, array{version: string, installed: bool}>
     */
    protected array $requiredDev = [];

    /**
     * {@inheritdoc}
     */
    public function add(array $dependencies, bool $dev = false): static
    {
        foreach ($dependencies as $name => $version) {
            if (is_int($name)) {
                $name = $version;

                $version = '*';
            }

            if ($dev) {
                $this->requiredDev[$name] = ['version' => $version ?? '*', 'installed' => false];
            } else {
                $this->required[$name] = ['version' => $version ?? '*', 'installed' => false];
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function install(array $dependencies = [], bool $dev = false): static
    {
        /**
         * Mark all required (and dev) dependencies as installed, then mark the given dependencies as installed as well.
         */
        foreach (array_keys($this->required) as $dependency) {
            $this->required[$dependency]['installed'] = true;
        }

        foreach (array_keys($this->requiredDev) as $dependency) {
            $this->requiredDev[$dependency]['installed'] = true;
        }


        if ($dev) {
            foreach ($dependencies as $name => $version) {
                if (is_int($name)) {
                    $name = $version;

                    $version = '*';
                }

                $this->requiredDev[$name] = ['version' => $version ?? '*', 'installed' => true];
            }
        } else {
            foreach ($dependencies as $name => $version) {
                if (is_int($name)) {
                    $name = $version;

                    $version = '*';
                }

                $this->required[$name] = ['version' => $version ?? '*', 'installed' => true];
            }
        }

        return $this;
    }

    public function assertDependencyAdded(string $dependency): void
    {
        $dependency = $this->required[$dependency] ?? $this->requiredDev[$dependency] ?? null;

        PHPUnit\Assert::assertNotNull($dependency, "Failed asserting that dependency [{$dependency}] was added.");
    }

    public function assertDependencyNotAdded(string $dependency): void
    {
        $dependency = $this->required[$dependency] ?? $this->requiredDev[$dependency] ?? null;

        PHPUnit\Assert::assertNull($dependency, "Failed asserting that dependency [{$dependency}] was not added.");
    }

    public function assertDependencyAddedButNotInstalled(string $dependency): void
    {
        $this->assertDependencyAdded($dependency);

        $this->assertDependencyNotInstalled($dependency);
    }

    public function assertDependencyInstalled(string $dependency): void
    {
        $dep = $this->required[$dependency] ?? $this->requiredDev[$dependency] ?? ['installed' => false];

        PHPUnit\Assert::assertTrue($dep['installed'], "Failed asserting that dependency [{$dependency}] was installed.");
    }

    public function assertDependencyNotInstalled(string $dependency): void
    {
        $dep = $this->required[$dependency] ?? $this->requiredDev[$dependency] ?? ['installed' => false];

        PHPUnit\Assert::assertFalse($dep['installed'], "Failed asserting that dependency [{$dependency}] was not installed.");
    }

    public function assertDependencyVersionInstalled(string $dependency, string $version): void
    {
        $dep = $this->required[$dependency] ?? $this->requiredDev[$dependency] ?? null;

        PHPUnit\Assert::assertEquals($version, $dep['version'] ?? null, "Failed asserting that dependency [{$dependency}] with version [{$version}] was installed.");
    }

    public function assertDependencyVersionNotInstalled(string $dependency, string $version): void
    {
        $dep = $this->required[$dependency] ?? $this->requiredDev[$dependency] ?? null;

        PHPUnit\Assert::assertNotEquals($version, $dep['version'] ?? null, "Failed asserting that dependency [{$dependency}] with version [{$version}] was not installed.");
    }

    public function assertNothingInstalled(): void
    {
        PHPUnit\Assert::assertEmpty($this->required, 'Failed asserting that no dependencies were added.');

        PHPUnit\Assert::assertEmpty($this->requiredDev, 'Failed asserting that no dev dependencies were added.');
    }
}
