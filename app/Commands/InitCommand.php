<?php

namespace App\Commands;

use App\Commands\Concerns\InteractsWithComposer;
use App\Commands\Concerns\InteractsWithPackageConfiguration;
use App\Commands\Concerns\InteractsWithTemplate;
use App\Commands\Contracts\HasPackageConfiguration;
use App\Commands\Exceptions\CliNotBuiltException;
use Illuminate\Console\Concerns\PromptsForMissingInput;
use Illuminate\Contracts\Console\PromptsForMissingInput as PromptsForMissingInputContract;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use LaravelZero\Framework\Commands\Command;
use Phar;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function Laravel\Prompts\clear;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

class InitCommand extends Command implements HasPackageConfiguration, PromptsForMissingInputContract
{
    use InteractsWithComposer,
        InteractsWithPackageConfiguration,
        PromptsForMissingInput {
            InteractsWithPackageConfiguration::promptForMissingArgumentsUsing as packagePromptForMissingArgumentsUsing;
        }
    use InteractsWithTemplate;

    protected $signature = 'init
                         {--dir=* : The excluded directories}
                         {--file=* : The excluded files}
                         {--path= : The path where the package will be initialized}
                         {--confirm : Skip the confirmation prompt}
                         {--d|do-not-install-dependencies : Do not install the dependencies after initialization}
                         {--s|no-self-delete : Do not delete this command after initialization}';

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
            $this->shouldBootstrapPackage();

            retry(3, callback: function () {
                ! $this->option('confirm') && $this->printConfiguration();

                if ($this->option('confirm') || confirm('Do you want to use this configuration?')) {
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

        ! $this->option('do-not-install-dependencies') && $this->installDependencies();

        $this->selfDelete();

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
            ['Vendor', 'Package', 'Author', 'Author Email', 'Description', 'Namespace', 'Package Version', 'Minimum Stability', 'Type', 'License'],
            [[
                $this->getPackageVendor(),
                $this->getPackageName(),
                $this->getPackageAuthorName(),
                $this->getPackageAuthorEmail(),
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
        return trim(($this->option('path') ?? getcwd()).(trim($path) ? DIRECTORY_SEPARATOR.$path : ''));
    }

    protected function installDependencies(): void
    {
        $this->info('Installing dependencies...');

        if (! confirm('Do you want to install the dependencies?')) {
            $this->info('Dependencies were not installed.');

            return;
        }

        $this->composer()->installDependencies(
            output: $this->getOutput()
        );
    }

    /**
     * Self-delete the CLI if it is running as a Phar and the user wants to.
     *
     * @throws CliNotBuiltException if the CLI is not running as a Phar
     */
    protected function selfDelete(): void
    {
        if ($this->option('no-self-delete')) {
            $this->warn('Self-deleting skipped');

            return;
        }

        $this->info('Attempting to self-delete the CLI');

        $binary = Phar::running(false);

        if (! $binary) {
            throw new CliNotBuiltException('The CLI has not been build. Self-deletion is not possible.');
        }

        $id = pcntl_fork();

        if ($id === -1) {
            $this->error('We could not self-delete the CLI');

            exit(self::FAILURE);
        }

        if ($id !== 0) {
            $this->info('Self-deleting the CLI...');
        } else {
            $process = Process::command(['unlink', $binary])->run();

            if ($process->failed()) {
                $this->error('We could not self-delete the CLI');

                exit(self::FAILURE);
            }

            $this->info('Bye bye 👋');
        }

        exit(self::SUCCESS);
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return $this->packagePromptForMissingArgumentsUsing();
    }

    protected function clear(): void
    {
        clear();

        $requiredArguments = array_keys($this->getPromptRequiredArguments());

        collect($requiredArguments)
            ->each(fn (string $argument) => $this->input->setArgument($argument, null));
    }
}
