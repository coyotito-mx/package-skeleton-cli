<?php

declare(strict_types=1);

namespace App;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Symfony\Component\Console\Exception\CommandNotFoundException;

/**
 * Simple Git wrapper to perform Git operations.
 */
class Git
{
    public function __construct(protected string $directory)
    {
        //
    }

    /**
     * Clone a Git repository to a specified destination.
     */
    public function cloneRepository(string $url, string $destination, ?string $branch = null, int $depth = -1): void
    {
        $this->ensureBinaryIsAvailable();

        $process = $this->makeProcess([
            'clone',
            $url,
            $destination,
            ...(
                filled($branch) ?
                    ['--branch', $branch] :
                    ($depth > 0 ? ['--depth', $depth] : [])
            ),
        ])->run();

        $process->throw();
    }

    public function getConfig(string $key, mixed $default = null): mixed
    {
        $this->ensureBinaryIsAvailable();

        $process = $this->makeProcess(['config', $key])->run();

        if ($process->seeInErrorOutput("key does not contain a section: $key")) {
            return $default;
        }

        $value = trim($process->output());

        if (ctype_digit($value)) {
            return (int) $value;
        }

        if (ctype_alnum($value)) {
            return (string) $value;
        }

        return $value ?: $default;
    }

    /**
     * Ensure that the Git binary is available on the system.
     *
     * @throws CommandNotFoundException if the git command is not installed, or is not available in the $PATH
     * @throws RuntimeException if something unexpected happen
     */
    protected function ensureBinaryIsAvailable(): void
    {
        $process = $this->makeProcess('--version')->run();

        if ($process->failed()) {
            if ($process->seeInErrorOutput('command not found')) {
                throw new CommandNotFoundException('Git is not available on this system.');
            }

            throw new RuntimeException($process->errorOutput());
        }
    }

    /**
     * Create a new PendingProcess instance for Git commands.
     */
    protected function makeProcess(string|array $args): PendingProcess
    {
        return Process::command([
            $this->getBinary(),
            ...(is_array($args) ? $args : explode(' ', $args)),
        ])
            ->tty(false)
            ->path($this->directory);
    }

    /**
     * Get the Git binary name.
     */
    protected function getBinary(): string
    {
        return 'git';
    }
}
