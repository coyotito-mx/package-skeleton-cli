<?php

declare(strict_types=1);

namespace App;

use Illuminate\Support\Arr;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @mixin \Illuminate\Support\Composer
 */
class Composer extends \Illuminate\Support\Composer
{
    use Concerns\WithLicense;

    /**
     * Install the dependencies from the current Composer lock file.
     *
     * @param  bool  $dev  if dev dependencies should be installed
     * @param  bool  $noProgress  if output should hide progress
     * @param  bool  $optimize  if autoloader should be optimized
     * @param  string|null  $composerBinary
     *
     * @throw \RuntimeException if the composer file is not found
     */
    public function installDependencies(bool $dev = true, bool $noProgress = false, bool $optimize = false, \Closure|OutputInterface|null $output = null, $composerBinary = null): bool
    {
        $this->findComposerFile();

        $command = collect($this->findComposer($composerBinary))
            ->merge([
                'install',
                '--ansi',
                '--no-interaction',
            ])
            ->when(! $dev, fn ($command) => $command->push('--no-dev'))
            ->when($noProgress, fn ($command) => $command->push('--no-progress'))
            ->when($optimize, fn ($command) => $command->push('--optimize-autoloader'))
            ->all();

        return $this->getProcess($command, ['COMPOSER_MEMORY_LIMIT' => '-1'])
            ->run(
                $output instanceof OutputInterface
                    ? fn ($type, $line) => $output->write('    '.$line)
                    : $output
            ) === 0;
    }

    /**
     * Add a dependency to the `composer.json` file.
     */
    public function addDependencies(string|array $packages, bool $dev = false): void
    {
        $packages = Arr::wrap($packages);

        $this->modify(
            function (array $json) use ($packages, $dev): array {
                $section = $dev ? 'require-dev' : 'require';

                foreach ($packages as $package => $version) {
                    if (is_int($package)) {
                        [$package, $version] = [$version, '*'];
                    }

                    $json[$section][$package] = $version;
                }

                return $json;
            }
        );
    }
}
