<?php

namespace App\Commands;

use App\Commands\Contracts\HasPackageConfigurationCommand;
use App\Commands\Exceptions\LicenseNotFound;
use App\Commands\Traits\InteractsWithPackageConfiguration;
use App\Replacer;
use Illuminate\Console\Concerns\PromptsForMissingInput as ConcernsPromptsForMissingInput;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function Laravel\Prompts\clear;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

class PackageInitCommand extends Command implements HasPackageConfigurationCommand, PromptsForMissingInput
{
    use ConcernsPromptsForMissingInput;
    use InteractsWithPackageConfiguration {
        InteractsWithPackageConfiguration::promptForMissingArgumentsUsing as packagePromptForMissingArgumentsUsing;
    }

    // Command signature and description
    protected $signature = 'package:init
                         {--author= : The package author}
                         {--license= : The package license (default: MIT)}
                         {--package-version= : The package version (default: v0.0.1)}
                         {--minimum-stability= : The package minimum-stability (default: dev)}
                         {--type= : The package type (default: library)}
                         {--dir=* : The excluded directories}
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
            ->through($this->getReplacers())
            ->thenReturn();

        File::put($file->getRealPath(), $content);

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
            tap(new Finder)->files()->in($this->getPackagePath())->exclude($this->getExcludedDirectories())
        )->toArray();
    }

    /**
     * @throws LicenseNotFound
     */
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

    /**
     * @throws LicenseNotFound
     */
    protected function getReplacers(): array
    {
        return [
            $this->createReplacer('vendor', $this->getPackageVendor()),
            $this->createReplacer('package', $this->getPackageName()),
            $this->createReplacer('author', $this->getPackageAuthorName()),
            $this->createReplacer('description', $this->getPackageDescription()),
            $this->createReplacer('namespace', $this->getPackageNamespace(), [
                'reverse' => fn (string $value) => Str::of($value)->replace('\\', '/'),
                'escape' => fn (string $value) => Str::of($value)->replace('\\', '\\\\'),
            ]),
            $this->createReplacer('version', $this->getPackageVersion()),
            $this->createReplacer('minimum-stability', $this->getPackageMinimumStability()),
            $this->createReplacer('type', $this->getPackageType()),
            $this->createReplacer('license', $this->getPackageLicense()),
        ];
    }

    protected function createReplacer(string $placeholder, string $replacement, array $modifiers = []): \Closure
    {
        return function (string $content, \Closure $next) use ($placeholder, $replacement, $modifiers) {
            $replacer = new Replacer($placeholder, $replacement);

            collect($modifiers)->each(fn (\Closure $cb, string $modifier) => $replacer->modifierUsing($modifier, $cb));

            return $next($replacer->replace($content));
        };
    }

    public function getPackageAuthorName(): string
    {
        return Str::title($this->option('author') ?? $this->getPackageVendor());
    }

    /**
     * @throws LicenseNotFound
     */
    public function getPackageLicense(): string
    {
        $license = $this->option('license') ?? 'MIT';

        if (! \App\Facades\Composer::validateLicense($license)) {
            throw new Exceptions\LicenseNotFound($license);
        }

        return $license;
    }

    public function getPackageVersion(): string
    {
        return Str::lower($this->option('package-version') ?? 'v0.0.1');
    }

    public function getPackageMinimumStability(): string
    {
        return Str::lower($this->option('minimum-stability') ?? 'dev');
    }

    public function getPackageType(): string
    {
        return Str::lower($this->option('type') ?? 'library');
    }

    protected function getExcludedDirectories(): array
    {
        return [...Arr::wrap($this->option('dir')), ...$this->excludedDirectories];
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

        \App\Facades\Composer::installDependencies();
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
