<?php

declare(strict_types=1);

namespace App\DependencyManagers;


class Npm extends DependencyManager
{
    protected static string $patternDependency = '/^(?<name>(?:@[a-z0-9-~][a-z0-9-._~]*\/)?[a-z0-9-~][a-z0-9-._~]*)(?:@(?<version>[\w.*^~<>=-]+))?$/';

    public function add(array $dependencies, bool $dev = false): static
    {
        $this->ensureInstalled();

        $this->validateDependencies($dependencies);

        $this->run(['install', $dependencies], [$dev ? '--save-dev' : '', '--package']);

        return $this;
    }

    public function install(array $dependencies = [], bool $dev = false): static
    {
        $this->add($dependencies, $dev);

        $this->run('install');

        return $this;
    }

    protected function getBinary(): string
    {
        return 'npm';
    }
}
