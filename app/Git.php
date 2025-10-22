<?php

declare(strict_types=1);

namespace App;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use RuntimeException;

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

    /**
     * Ensure that the Git binary is available on the system.
     */
    protected function ensureBinaryIsAvailable(): void
    {
        $process = $this->makeProcess('--version')->run();

        if ($process->failed()) {
            throw new RuntimeException('Git is not available on this system.');
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
