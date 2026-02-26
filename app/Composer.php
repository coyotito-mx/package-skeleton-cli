<?php

declare(strict_types=1);

namespace App;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Composer implements Contracts\ComposerContract
{
    public function __construct(public ?string $cwd = null, private readonly ?Application $app = null)
    {
        $this->cwd ??= getcwd();
    }

    public function require(string|array $package, bool $dev = false, bool $withAllDependencies = false): bool
    {
        $packages = Arr::wrap($package);

        $flags = [];

        if ($dev) {
            $flags[] = '--dev';
        }

        if ($withAllDependencies) {
            $flags[] = '--with-all-dependencies';
        }

        return $this->run(['composer', 'require', ...$packages, ...$flags]);
    }

    /**
        * Runs the provided command in the current working directory.
        *
     * @param  array<int, string>  $command
     */
    private function run(array $command): bool
    {
        $process = Process::path($this->cwd);

        if ($process->tty) {
            $process->tty(true);
        }

        try {
            $output = $this->app?->make(OutputInterface::class);
        } catch (Throwable $e) {
            $output = null;
        }

        if ($output) {
            $output->write("\n");

            $result = $process->run(
                $command,
                function ($_, $line) use ($output) {
                    $output->write($line);
                }
            );
        } else {
            $result = $process->run($command);
        }

        return $result->successful();
    }
}
