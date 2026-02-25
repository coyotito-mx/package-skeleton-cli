<?php

namespace App\DependencyManagers;

use App\DependencyManagers\Exceptions\DependencyInstallationFailException;
use App\DependencyManagers\Exceptions\DependencyManagerNotInstalledException;
use App\DependencyManagers\Exceptions\InvalidDependencyFormatException;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

abstract class DependencyManager implements Contracts\DependencyManagerContract
{
    protected static string $patternDependency;

    /**
     * Constructor
     *
     * @param  string  $context  The context path where the dependency manager operates
     * @param  bool  $tty  Whether to use TTY for process output
     * @param  null|OutputInterface  $output  Optional output interface for process output, if null, TTY will be used if available
     * @return void
     */
    public function __construct(public string $context, public bool $tty = true, public ?OutputInterface $output = null)
    {
        //
    }

    /**
     * Adds dependencies to the project file without installing them.
     */
    abstract public function add(array $dependencies, bool $dev = false): static;

    /**
     * Installs dependencies in the project.
     */
    abstract public function install(array $dependencies = [], bool $dev = false): static;

    /**
     * Returns a human-readable dependency format description.
     */
    abstract protected function getValidFormatDescription(): string;

    /**
     * Returns the binary name for the dependency manager.
     */
    abstract protected function getBinary(): string;

    /**
     * Validates a single dependency.
     */
    public function validateDependency(string $dependency): void
    {
        if ($this->parseDependencyByPattern($dependency, static::$patternDependency) === null) {
            throw new InvalidDependencyFormatException($dependency, $this->getValidFormatDescription());
        }
    }

    /**
     * Parses a single dependency into its components.
     *
     * @return array{name: string, version?: string}|null
     */
    public function parseDependency(string $dependency): ?array
    {
        return $this->parseDependencyByPattern($dependency, static::$patternDependency);
    }

    public function validateDependencies(array $dependencies): void
    {
        foreach ($dependencies as $dependency) {
            $this->validateDependency($dependency);
        }
    }

    /**
     * Runs a command in the context of the dependency manager.
     *
     * @param  string|array<int, string>  $command
     * @param  string[]  $arguments
     */
    protected function run(string|array $command = [], array $arguments = []): ProcessResult
    {
        $command = array_filter([...Arr::wrap($command ?: null), ...$arguments]);

        $process = Process::path($this->context)->command($command);

        if ($this->shouldUseTty()) {
            $process->tty();
        }

        return $process->run(output: function (string $type, string $output) {
            if ($this->output instanceof OutputInterface) {
                $this->output->writeln(trim($output, "\n"));
            }
        });
    }

    protected function shouldUseTty(): bool
    {
        return $this->output === null && $this->tty;
    }

    protected function ensureInstalled(): void
    {
        $result = $this->run(command: $this->getBinary(), arguments: ['--version', '--quiet']);

        if (! $result->successful()) {
            throw new DependencyManagerNotInstalledException(
                manager: static::class,
                cause: $result->errorOutput(),
                exitCode: $result->exitCode(),
                binary: $this->getBinary()
            );
        }
    }

    /**
     * Runs the installation command and throws a standard exception if it fails.
     *
     * @param  string|array<int, string>  $command
     * @param  string[]  $dependencies
     */
    protected function runInstallCommand(string|array $command = 'install', array $dependencies = []): ProcessResult
    {
        $this->ensureInstalled();

        $process = $this->run($command);

        if (! $process->successful()) {
            throw new DependencyInstallationFailException(
                'Dependency installation failed',
                process: $process,
                dependencies: $dependencies
            );
        }

        return $process;
    }

    /**
     * Get the full path of a project file based on the context.
     */
    protected function projectFilePath(string $filename): string
    {
        return $this->context.DIRECTORY_SEPARATOR.$filename;
    }

    /**
     * Ensures that a project file exists and returns its path, otherwise throws an exception.
     */
    protected function ensureProjectFileExists(string $filename): string
    {
        $path = $this->projectFilePath($filename);

        if (! File::exists($path)) {
            throw new RuntimeException("$filename does not exist");
        }

        return $path;
    }

    /**
     * Reads a JSON file and returns its content as an associative array
     *
     * Throws an exception if the file cannot be read or parsed.
     *
     * @return array<string, mixed>
     */
    protected function readJsonFile(string $path): array
    {
        return File::json($path, JSON_THROW_ON_ERROR);
    }

    /**
     * Writes an associative array to a JSON file with pretty print and unescaped slashes.
     *
     * @param  array<string, mixed>  $content
     */
    protected function writeJsonFile(string $path, array $content): void
    {
        File::put($path, json_encode($content, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Parses a dependency string using a given pattern and returns its components.
     *
     * @return array{name: string, version: ?string}
     */
    protected function parseDependencyByPattern(string $dependency, string $pattern): ?array
    {
        if (! preg_match($pattern, $dependency, $matches)) {
            return null;
        }

        $parsed = array_filter(
            $matches,
            static fn (mixed $value, int|string $key) => is_string($key),
            ARRAY_FILTER_USE_BOTH
        );

        return $parsed;
    }
}
