<?php

namespace App\Commands\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Illuminate\Filesystem\join_paths;

trait InteractsWithBinaryRemoval
{
    /**
     * Attempts to delete the CLI executable based on the current invocation context.
     *
     * @return bool if the file was successfully deleted, false otherwise.
     *
     * @throws \RuntimeException if the CLI executable path cannot be determined or is invalid.
     */
    protected function deleteBinary(): bool
    {
        $cliFilePath = $this->resolveExecutablePathFromInvocation();

        if (! $cliFilePath || ! File::exists($cliFilePath) || File::isDirectory($cliFilePath)) {
            throw new \RuntimeException('Unable to determine the CLI executable path for removal.');
        }

        return File::delete($cliFilePath);
    }

    protected function resolveExecutablePathFromInvocation(): ?string
    {
        $argv = $_SERVER['argv'] ?? [];

        if (! is_array($argv) || empty($argv)) {
            return null;
        }

        $firstArg = $this->resolvePathFromArg((string) ($argv[0] ?? ''));

        if ($firstArg && realpath($firstArg) === realpath(PHP_BINARY)) {
            $scriptArg = (string) ($argv[1] ?? '');

            return $this->resolvePathFromArg($scriptArg);
        }

        if ($this->isRunningFromPhar()) {
            return $this->resolveRunningPharPath();
        }

        return $firstArg;
    }

    protected function isRunningFromPhar(): bool
    {
        return $this->runningPharFromRuntime() !== '';
    }

    protected function resolveRunningPharPath(): ?string
    {
        $runningPhar = $this->runningPharFromRuntime();

        if ($runningPhar === '') {
            return null;
        }

        $resolved = realpath($runningPhar);

        if ($resolved !== false && File::exists($resolved) && ! File::isDirectory($resolved)) {
            return $resolved;
        }

        if (File::exists($runningPhar) && ! File::isDirectory($runningPhar)) {
            return $runningPhar;
        }

        return null;
    }

    protected function runningPharFromRuntime(): string
    {
        return \Phar::running(false);
    }

    private function resolvePathFromArg(string $arg): ?string
    {
        if ($arg === '') {
            return null;
        }

        if (File::exists(join_paths(getcwd(), $arg))) {
            return realpath(join_paths(getcwd(), $arg)) ?: join_paths(getcwd(), $arg);
        }

        if (Str::contains($arg, [DIRECTORY_SEPARATOR, '/'])) {
            $absolutePath = Str::startsWith($arg, DIRECTORY_SEPARATOR)
                ? $arg
                : join_paths(getcwd() ?: '.', $arg);

            $resolved = realpath($absolutePath);

            return $resolved ?: $absolutePath;
        }

        foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $pathDirectory) {
            if ($pathDirectory === '') {
                continue;
            }

            $candidate = join_paths($pathDirectory, $arg);

            if (File::exists($candidate)) {
                $resolved = realpath($candidate);

                return $resolved ?: $candidate;
            }
        }

        return null;
    }
}
