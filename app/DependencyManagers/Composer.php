<?php

declare(strict_types=1);

namespace App\DependencyManagers;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class Composer extends DependencyManager
{
    protected static string $patternDependency = '/^(?<name>[a-z0-9_.-]+\/[a-z0-9_.-]+)(?:\:(?<version>[^\s]+))$/i';

    /**
     * {@inheritdoc}
     */
    public function add(array $dependencies, bool $dev = false): static
    {
        if (blank($dependencies)) {
            return $this;
        }

        $composerFile = $this->ensureProjectFileExists('composer.json');
        $composer = $this->readJsonFile($composerFile);

        $section = 'require'.($dev ? '-dev' : '');
        $composer[$section] ??= [];

        foreach ($dependencies as $dependency) {
            ['name' => $pkg, 'version' => $version] = $this->parseDependency($dependency);

            $composer[$section][$pkg] = $version;
        }

        $this->writeJsonFile($composerFile, $composer);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function install(array $dependencies = [], bool $dev = false): static
    {
        $this->add($dependencies, $dev);

        try {
            $this->runInstallCommand(
                command: $this->hasLockFile() ? 'update' : 'install',
                dependencies: $dependencies
            );
        } catch (ProcessTimedOutException) {
            throw new RuntimeException('Installation timed out.');
        } catch (RuntimeException $exception) {
            throw new RuntimeException('Installation failed: '.$exception->getMessage(), code: $exception->getCode(), previous: $exception);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validateDependency(string $dependency): void
    {
        $this->parseDependencyByPattern($dependency, static::$patternDependency, '<vendor>/<package>[:<version>]');
    }

    /**
     * Check if the project has a `composer.lock` file, which indicates that the dependencies have been installed at least once.
     */
    protected function hasLockFile(): bool
    {
        return File::exists($this->context.DIRECTORY_SEPARATOR.'composer.lock');
    }

    /**
     * {@inheritdoc}
     */
    public function parseDependency(string $dependency): array
    {
        $parsed = $this->parseDependencyByPattern($dependency, static::$patternDependency, '<vendor>/<package>[:<version>]');

        return [
            'name' => Str::lower($parsed['name']),
            'version' => $parsed['version'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getBinary(): string
    {
        return 'composer';
    }
}
