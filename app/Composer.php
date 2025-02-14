<?php

declare(strict_types=1);

namespace App;

use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Support\Facades\Process;

class Composer
{
    public function install(): void
    {
        $this->runProcess('install');
    }

    public function require(string $package, bool $dev = false): void
    {
        $this->runProcess(['require', $package, $dev ? '--dev' : '']);
    }

    public function remove(string $package, bool $dev = false): void
    {
        $this->runProcess(['remove', $package, $dev ? '--dev' : '']);
    }

    public function update(string $package, bool $dev = false): void
    {
        $this->runProcess(['update', $package, $dev ? '--dev' : '']);
    }

    public function dumpAutoload(bool $optimize = false): void
    {
        $this->runProcess(['dump-autoload', $optimize ? '--optimize' : '']);
    }

    public function runProcess(string|array $command): void
    {
        $composer = 'composer';

        try {
            $command = Process::command([$composer, ...\Arr::wrap($command)]);

            $command->tty();

            $command->run();
        } catch (ProcessFailedException $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }
}
