<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * @mixin \Illuminate\Support\Composer
 */
class Composer extends \Illuminate\Support\Composer
{
    use Traits\WithLicense;


    /**
     * Install the dependencies from the current Composer lock file.
     *
     * @param  bool  $dev if dev dependencies should be installed
     * @param  bool  $noProgress if output should hide progress
     * @param  bool  $optimize if autoloader should be optimized
     * @param  \Closure|\Symfony\Component\Console\Output\OutputInterface|null  $output
     * @param  string|null  $composerBinary
     * @return bool
     */
    public function installDependencies(bool $dev = true, bool $noProgress = false, bool $optimize = false, \Closure|OutputInterface|null $output = null, $composerBinary = null): bool
    {
        $command = collect($this->findComposer($composerBinary))
            ->merge([
                'install',
                '--ansi',
                '--no-interaction',
            ])
            ->when(!$dev, fn($command) => $command->push('--no-dev'))
            ->when($noProgress, fn($command) => $command->push('--no-progress'))
            ->when($optimize, fn($command) => $command->push('--optimize-autoloader'))
            ->all();

        return 0 === $this->getProcess($command, ['COMPOSER_MEMORY_LIMIT' => '-1'])
            ->run(
                $output instanceof OutputInterface
                    ? fn($type, $line) => $output->write('    '.$line)
                    : $output
            );
    }
}
