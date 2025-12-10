<?php

namespace App\DependencyManagers;

use App\DependencyManagers\Exceptions\DependencyInstallationFailException;
use App\DependencyManagers\Exceptions\DependencyManagerNotInstalledException;
use App\DependencyManagers\Exceptions\InvalidDependencyFormatException;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\OutputInterface;

abstract class DependencyManager
{
    protected static string $patternDependency;

    public bool $tty = true {
        get {
            if ($this->output instanceof OutputInterface) {
                return false;
            }

            return $this->tty;
        }
        set {
            if ($this->output instanceof OutputInterface) {
                $this->tty = false;
            } else {
                $this->tty = $value;
            }
        }
    }

    /**
     * @param string $context The context (working directory) for the dependency manager operations.
     * @param bool $tty Indicates whether the process should run in TTY mode.
     */
    public function __construct(public string $context, bool $tty = true, public ?OutputInterface $output = null)
    {
        $this->tty = $tty;
    }

    /**
     * Add dependencies to the project file (e.g., composer.json, package.json).
     *
     * @param string[] $dependencies List of dependencies to add
     * @param bool $dev Whether to add as development dependencies
     */
    abstract public function add(array $dependencies, bool $dev = false): static;

    /**
     * Install dependencies using the dependency manager.
     *
     * The manager should install all dependencies listed in the project file and any other specified dependencies.
     *
     * @param string[] $dependencies List of dependencies to install
     * @param bool $dev Whether to install development dependencies
     * @return $this
     * @throws DependencyInstallationFailException if the installation fails
     */
    abstract public function install(array $dependencies = [], bool $dev = false): static;

    /**
     * Validate the dependency format of the given dependency string
     *
     * @param string|array $dependencies
     * @throws InvalidDependencyFormatException if the dependency is not in a valid format for the manager
     */
    public function validateDependencies(string|array $dependencies): void
    {
        if (is_string($dependencies)) {
            if (! Str::isMatch(static::$patternDependency, $dependencies)) {
                throw new InvalidDependencyFormatException($dependencies, static::$patternDependency);
            }

            return;
        }

        foreach ($dependencies as $dependency) {
            $this->validateDependencies($dependency);
        }
    }

    protected function run(string|array $command = [], array $arguments = []): ProcessResult
    {
        $command = array_filter([$this->getBinary(), ...Arr::wrap($command ?: null), ...$arguments]);

        $process = Process::path($this->context)->command($command);

        if ($this->tty) {
            $process->tty();
        }

        return $process->run(output: function (string $type, string $output) {
            if ($this->output instanceof OutputInterface) {
                $this->output->writeln(trim($output, "\n"));
            }
        });
    }

    /**
     * Ensure that the dependency manager is installed on the system.
     *
     * @return void
     * @throws DependencyManagerNotInstalledException if the manager is not installed
     */
    protected function ensureInstalled(): void
    {
        $result = $this->run(arguments: ['--version', '--quiet']);

        if (! $result->successful()) {
            throw new DependencyManagerNotInstalledException(
                manager: static::class,
                binary: $this->getBinary(),
                cause: $result->errorOutput(),
                exitCode: $result->exitCode()
            );
        }
    }

    /**
     * Get the binary path of the dependency manager.
     *
     * @return string
     */
    abstract protected function getBinary(): string;
}
