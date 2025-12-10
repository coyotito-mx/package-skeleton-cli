<?php

declare(strict_types=1);

namespace App\DependencyManagers;

use App\DependencyManagers\Exceptions\DependencyInstallationFailException;
use App\DependencyManagers\Exceptions\InvalidDependencyFormatException;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class Composer extends DependencyManager
{
    protected static string $dependencyPattern = '/^(?<name>[a-z0-9_.-]+\/[a-z0-9_.-]+)(:(?<version>[^\s]+))?$/i';

    public function add(array $dependencies, bool $dev = false): static
    {
        if (blank($dependencies)) {
            return $this;
        }

        $composerFile = $this->context.DIRECTORY_SEPARATOR.'composer.json';

        if (! File::exists($composerFile)) {
            throw new RuntimeException('composer.json does not exist');
        }

        $composer = File::json($composerFile, JSON_THROW_ON_ERROR);

        foreach ($dependencies as $dependency) {
            ['name' => $pkg, 'version' => $version] = $this->parseDependency($dependency);

            $composer['require'.($dev ? '-dev' : '')][$pkg] = $version;
        }

        File::put($composerFile, json_encode($composer, JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        return $this;
    }

    /*
     * Install dependencies using Composer.
     *
     * @param array $dependencies List of dependencies to install in the format 'vendor/package:version'.
     * @param bool $dev Whether to install as dev dependencies, and will only be applied when dependencies are provided.
     */
    public function install(array $dependencies = [], bool $dev = false): static
    {
        $this->add($dependencies, $dev);

        $this->ensureInstalled();

        try {
            $process = $this->run('install');
        } catch (ProcessTimedOutException) {
            throw new RuntimeException('Installation timed out.');
        } catch (RuntimeException $exception) {
            throw new RuntimeException('Installation failed: '.$exception->getMessage(), code: $exception->getCode(), previous: $exception);
        }

        if (! $process->successful()) {
            throw new DependencyInstallationFailException('Dependency installation failed', process: $process, dependencies: $dependencies);
        }

        return $this;
    }

    public function validateDependency(string $dependency): void
    {
        if (! Str::isMatch(static::$dependencyPattern, $dependency)) {
            throw new InvalidDependencyFormatException($dependency, '<vendor>/<package>[:<version>]');
        }
    }

    public function parseDependency(string $dependency): array
    {
        $this->validateDependency($dependency);

        $dependency = Str::of($dependency)->matchAllWithGroups(static::$dependencyPattern)->first();

        return ['name' => Str::lower($dependency['name']), 'version' => $dependency['version'] ?? '*'];
    }

    protected function getBinary(): string
    {
        return 'composer';
    }
}
