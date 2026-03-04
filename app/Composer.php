<?php

declare(strict_types=1);

namespace App;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function Illuminate\Filesystem\join_paths;

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
        } catch (Throwable) {
            $output = null;
        }

        if ($output) {
            $output->write("\n");

            $result = $process->run(
                $command,
                function ($_, $line) use ($output): void {
                    $output->write($line);
                }
            );
        } else {
            $result = $process->run($command);
        }

        return $result->successful();
    }

    public function allowPlugin(string $plugin, bool $allow = true): void
    {
        $composerJsonPath = join_paths($this->cwd, 'composer.json');

        if (! File::exists($composerJsonPath)) {
            throw new \RuntimeException("Composer.json not found at path: $composerJsonPath");
        }

        $composerJson = json_decode(File::get($composerJsonPath), true);

        if (! isset($composerJson['config']['allow-plugins'])) {
            $composerJson['config']['allow-plugins'] = [];
        }

        $composerJson['config']['allow-plugins'][$plugin] = $allow;

        File::put($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
