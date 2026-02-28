<?php

namespace App\Concerns;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Process;

trait InteractsWithProcess
{
    /**
     * Create a new process instance for the given command
     *
     * @param  string  $command  The command to run
     * @return PendingProcess The process instance, allowing the caller to handle the output and errors
     */
    public function makeProcess(string|array $command, string|array $args = []): PendingProcess
    {
        $args = Arr::wrap($args);
        $command = Arr::wrap($command);

        return Process::command([...$command, ...$args]);
    }
}
