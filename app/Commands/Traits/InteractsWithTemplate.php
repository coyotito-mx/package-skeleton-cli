<?php

declare(strict_types=1);

namespace App\Commands\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function Illuminate\Filesystem\join_paths;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\spin;

trait InteractsWithTemplate
{
    protected array $bootstrapTypes = [
        'vanilla' => 'git@github.com:coyotito-mx/package-skeleton.git',
        'laravel' => 'git@github.com:coyotito-mx/laravel-package-skeleton.git',
    ];

    protected function bootPackageInteractsWithTemplate(): void
    {
        $list = implode(', ', array_keys($this->bootstrapTypes));

        $this->addOption(
            name: 'bootstrap',
            shortcut: 'b',
            mode: InputOption::VALUE_OPTIONAL,
            description: "Bootstrap a package using a template ($list)",
        );
    }

    protected function shouldBootstrapPackage(): void
    {
        $template = $this->option('bootstrap');

        if (! $template) {
            return;
        }

        $tempDirectory = spin(fn () => $this->downloadTemplate($template), 'Downloading template');

        $this->moveTemplateFiles($tempDirectory, $this->getPackagePath());

    }

    private function checkBoostrapTemplateExists(string $template): bool
    {
        return in_array($template, array_keys($this->bootstrapTypes));
    }

    /**
     * Check if the directory is empty
     */
    private function checkIfDirectoryIsEmpty(string $directory, array $excludedPaths): bool
    {
        return collect(
            Finder::create()
                ->in($directory)
                ->notPath($excludedPaths)
                ->depth(0)
                ->ignoreDotFiles(false)
                ->getIterator()
        )->isEmpty();
    }

    /**
     * Download the specify template
     */
    private function downloadTemplate(string $template): string
    {
        if (! $this->checkBoostrapTemplateExists($template)) {
            throw new RuntimeException("[$template] is not a valid template");
        }

        // The path variant of the CLI -> <cli name> or <cli name>.phar
        $excludedPaths = [config('app.name'), config('app.name').'.phar'];

        if (! $this->checkIfDirectoryIsEmpty($this->getPackagePath(), $excludedPaths)) {
            throw new RuntimeException('The directory where you want to bootstrap the package is not empty (CLI file is ignore)');
        }

        app('git')
            ->cloneRepository(
                url: $this->bootstrapTypes[$template],
                destination: $temp = join_paths(sys_get_temp_dir(), uniqid()),
                branch: 'main'
            );

        return $temp;
    }

    /**
     * Move the files from the downloaded template to a different location
     */
    private function moveTemplateFiles(string $from, string $to): void
    {
        $files = Finder::create()
            ->in($to)
            ->depth(0)
            ->ignoreDotFiles(false)
            ->ignoreVCS(true)
            ->getIterator();

        progress(
            'Moving files',
            $files,
            function (SplFileInfo $file, $progress) use ($from, $to) {
                $progress->label("Moving file [{$file->getFilename()}]");

                if ($file->isDir()) {
                    File::moveDirectory($file->getPathname(), $to);
                } else {
                    $filename = Str::after($to, $from);

                    File::move($file->getPathname(), join_paths($to, $filename));
                }

                Sleep::for(500_000)->microseconds();
            }
        );
    }
}
