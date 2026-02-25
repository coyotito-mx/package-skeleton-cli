<?php

declare(strict_types=1);

namespace App\DependencyManagers;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\File;
use RuntimeException;

class Composer extends DependencyManager
{
    /**
     * Composer dependency format: vendor/package[:version]
     * - name: vendor/package (lowercase letters, numbers, dots, dashes, underscores)
     * - version: optional, any non-whitespace characters
     */
    protected static string $patternDependency = '/^(?<name>[a-z0-9_.-]+\/[a-z0-9_.-]+)(?:\:(?<version>[^\s]+))$/';

    /**
     * {@inheritdoc}
     */
    protected function getValidFormatDescription(): string
    {
        return '<vendor>/<package>[:<version>]';
    }

    /**
     * {@inheritdoc}
     */
    public function add(array $dependencies, bool $dev = false): static
    {
        if (blank($dependencies)) {
            return $this;
        }

        $this->validateDependencies($dependencies);

        $composerFile = $this->ensureProjectFileExists('composer.json');
        $composer = $this->readJsonFile($composerFile);

        $section = 'require'.($dev ? '-dev' : '');
        $composer[$section] ??= [];

        foreach ($dependencies as $dependency) {
            $parsed = $this->parseDependency($dependency);

            if ($parsed === null) {
                continue;
            }

            ['name' => $pkg, 'version' => $version] = $parsed;

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
                command: [$this->getBinary(), $this->hasLockFile() ? 'update' : 'install'],
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
     * Check if the project has a `composer.lock` file, which indicates that the dependencies have been installed at least once.
     */
    protected function hasLockFile(): bool
    {
        return File::exists($this->context.DIRECTORY_SEPARATOR.'composer.lock');
    }

    /**
     * Get the composer binary command.
     */
    protected function getBinary(): string
    {
        return 'composer';
    }
}
