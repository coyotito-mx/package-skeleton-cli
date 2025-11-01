<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Console\Input\InputOption;

use function Illuminate\Filesystem\join_paths;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\spin;

trait InteractsWithTemplate
{
    protected array $bootstrapTypes = [
        'vanilla' => 'git@github.com:coyotito-mx/package-skeleton.git',
        'laravel' => 'git@github.com:coyotito-mx/laravel-package-skeleton.git',
    ];

    protected function bootInteractsWithTemplate(): void
    {
        $list = implode(', ', array_keys($this->bootstrapTypes));

        $this->addOption(
            name: 'bootstrap',
            shortcut: 'b',
            mode: InputOption::VALUE_REQUIRED,
            description: "Bootstrap a package using a template ($list)",
            suggestedValues: array_keys($this->bootstrapTypes),
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
        $dirs = File::directories($directory);
        $files = File::files($directory, true);

        return collect($files)
            ->map(fn ($f) => $f->getPathname())
            ->merge($dirs)
            ->filter(fn (string $file) => ! in_array($file, $excludedPaths, true))
            ->isEmpty();
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
        $dirs = File::directories($from);
        $files = File::files($from);

        $allFiles = collect($files)
            ->map(fn ($f) => $f->getPathname())
            ->merge($dirs)
            ->toArray();

        progress(
            'Moving files',
            $allFiles,
            function (string $file, $progress) use ($to) {
                $progress->label("Moving file [$file]");

                if (File::isDirectory($file)) {
                    File::moveDirectory($file, $to);
                } else {
                    $filename = Str::afterLast($file, DIRECTORY_SEPARATOR);

                    File::move($file, join_paths($to, $filename));
                }

                Sleep::for(500_000)->microseconds();
            }
        );
    }
}
