<?php

namespace App\Commands;

use App\Commands\Contracts\HasPackageConfiguration;
use App\Commands\Traits\InteractsWithPackageConfiguration;
use Illuminate\Console\Concerns\PromptsForMissingInput as ConcernsPromptsForMissingInput;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function Laravel\Prompts\clear;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

class PackageInitCommand extends Command implements HasPackageConfiguration, PromptsForMissingInput
{
    use ConcernsPromptsForMissingInput;
    use InteractsWithPackageConfiguration {
        InteractsWithPackageConfiguration::promptForMissingArgumentsUsing as packagePromptForMissingArgumentsUsing;
    }

    protected $signature = 'package:init
                         {--dir=* : The excluded directories}
                         {--file=* : The excluded files}
                         {--path= : The path where the package will be initialized}';

    protected $description = 'Init package';

    protected array $excludedDirectories = [
        '.git',
        '.github',
        '.idea',
        '.vscode',
        'vendor',
        'node_modules',
        'tests',
    ];

    protected array $excludedFiles = [
        '.gitignore',
        '.gitattributes',
        '.editorconfig',
    ];

    public function handle(): int
    {
        try {
            retry(3, function () {
                $this->printConfiguration();

                if (confirm('Do you want to use this configuration?')) {
                    return true;
                }

                $this->clear();

                $this->promptForMissingArguments($this->input, $this->output);

                throw new \Exception('You did not confirm the package initialization.');
            });
        } catch (\Throwable $th) {
            $this->error($th->getMessage());

            return self::FAILURE;
        }

        spin(fn () => $this->replacePlaceholdersInFiles($this->getFiles()), 'Processing files...');

        $this->installDependencies();

        return self::SUCCESS;
    }

    public function replacePlaceholdersInFile(SplFileInfo $file): SplFileInfo
    {
        $content = (new Pipeline)
            ->send($file->getContents())
            ->through($this->getPackageReplacers())
            ->thenReturn();

        $filename = (new Pipeline)
            ->send($file->getFilename())
            ->through($this->getPackageReplacers())
            ->thenReturn();

        tap(
            File::getFacadeRoot(),
            fn ($filesystem) => $filesystem->put($file->getRealPath(), $content)
        )->move($file->getRealPath(), $file->getPath().DIRECTORY_SEPARATOR.$filename);

        return $file;
    }

    public function replacePlaceholdersInFiles(array $files): array
    {
        return collect($files)
            ->map(fn (SplFileInfo $file) => $this->replacePlaceholdersInFile($file))
            ->toArray();
    }

    public function getFiles(): array
    {
        return collect(
            tap(new Finder)
                ->files()
                ->in($this->getPackagePath())
                ->filter(function (\SplFileInfo $file) {
                    return ! in_array($file->getRealPath(), $this->getExcludedFiles());
                })
                ->exclude($this->getExcludedDirectories())
        )->toArray();
    }

    protected function printConfiguration(): void
    {
        info("Package init on: <fg=white>[{$this->getPackagePath()}]</>");
        $this->newLine();

        info('These are the details you provided:');
        table(
            ['Vendor', 'Package', 'Author', 'Description', 'Namespace', 'Package Version', 'Minimum Stability', 'Type', 'License'],
            [[
                $this->getPackageVendor(),
                $this->getPackageName(),
                $this->getPackageAuthorName(),
                $this->getPackageDescription(),
                $this->getPackageNamespace(),
                $this->getPackageVersion(),
                $this->getPackageMinimumStability(),
                $this->getPackageType(),
                $this->getPackageLicense(),
            ]]
        );

        $this->newLine();

        info('List of excluded directories:');

        table(
            rows: collect($this->getExcludedDirectories())
                ->map(fn (string $directory) => [$this->getPackagePath($directory)])->toArray()
        );
    }

    protected function getExcludedDirectories(): array
    {
        return [...Arr::wrap($this->option('dir')), ...$this->excludedDirectories];
    }

    protected function getExcludedFiles(): array
    {
        return collect($this->option('file'))
            ->map(fn (string $file) => realpath($this->getPackagePath($file)))
            ->filter()
            ->toArray();
    }

    protected function getPackagePath(?string $path = null): string
    {
        return trim(($this->option('path') ?? getcwd()).($path ? DIRECTORY_SEPARATOR.$path : ''));
    }

    protected function installDependencies(): void
    {
        $this->info('Installing dependencies...');

        if (! confirm('Do you want to install the dependencies?')) {
            $this->info('Dependencies were not installed.');

            return;
        }

        \App\Facades\Composer::setWorkingPath($this->getPackagePath())->installDependencies();
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return $this->packagePromptForMissingArgumentsUsing();
    }

    protected function clear(): void
    {
        clear();

        collect($this->arguments())
            ->each(fn ($_, string $argument) => $this->input->setArgument($argument, null));
    }
}
