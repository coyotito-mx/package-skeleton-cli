<?php

declare(strict_types=1);

namespace App\DependencyManagers;

class Npm extends DependencyManager
{
    protected static string $patternDependency = '/^(?<name>(?:@[a-z0-9-~][a-z0-9-._~]*\/)?[a-z0-9-~][a-z0-9-._~]*)(?:@(?<version>[\w.*^~<>=-]+))?$/';

    public function add(array $dependencies, bool $dev = false): static
    {
        if (blank($dependencies)) {
            return $this;
        }

        $packageFile = $this->ensureProjectFileExists('package.json');
        $package = $this->readJsonFile($packageFile);
        $section = $dev ? 'devDependencies' : 'dependencies';

        $package[$section] ??= [];

        foreach ($dependencies as $dependency) {
            ['name' => $name, 'version' => $version] = $this->parseDependency($dependency);

            $package[$section][$name] = $version;
        }

        $this->writeJsonFile($packageFile, $package);

        return $this;
    }

    public function install(array $dependencies = [], bool $dev = false): static
    {
        $this->add($dependencies, $dev);

        $this->runInstallCommand(command: 'install', dependencies: $dependencies);

        return $this;
    }

    public function validateDependency(string $dependency): void
    {
        $this->parseDependencyByPattern($dependency, static::$patternDependency, '<package>[@<version>]');
    }

    public function parseDependency(string $dependency): array
    {
        $parsed = $this->parseDependencyByPattern($dependency, static::$patternDependency, '<package>[@<version>]');

        return [
            'name' => $parsed['name'],
            'version' => $parsed['version'] ?: 'latest',
        ];
    }

    protected function getBinary(): string
    {
        return 'npm';
    }
}
