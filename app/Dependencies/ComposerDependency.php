<?php

declare(strict_types=1);

namespace App\Dependencies;

use App\Contracts\ComposerContract;
use Illuminate\Support\Arr;

abstract class ComposerDependency
{
    /**
     * The Composer package(s) to require.
     */
    protected string|array $package;

    /**
     * Whether to require the package(s) as a development dependency
     */
    protected bool $dev = false;

    /**
     * Allows all inherited dependencies to be updated, including those that are root requirements
     */
    protected bool $withAllDependencies = false;

    protected array $plugins = [];

    public function __construct(protected ComposerContract $composer)
    {
        //
    }

    /**
     * Requires the dependency
     */
    public function install(): bool
    {
        $packages = Arr::wrap($this->package);

        foreach ($this->plugins as $plugin => $allow) {
            if (is_int($plugin)) {
                $plugin = $allow;

                $allow = true;
            }

            $this->composer->allowPlugin($plugin, (bool) $allow);
        }

        return $this->composer->require($packages, $this->dev, $this->withAllDependencies);
    }
}
