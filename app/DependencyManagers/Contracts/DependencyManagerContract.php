<?php

declare(strict_types=1);

namespace App\DependencyManagers\Contracts;

interface DependencyManagerContract
{
    /**
     * Adds dependencies to the project file without installing them.
     */
    public function add(array $dependencies, bool $dev = false): static;

    /**
     * Installs dependencies in the project.
     */
    public function install(array $dependencies = [], bool $dev = false): static;
}
