<?php

namespace App\Commands;

use App\Replacer;
use Illuminate\Console\Concerns\PromptsForMissingInput as ConcernsPromptsForMissingInput;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;
use function Laravel\Prompts\clear;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;

class PackageInitCommand extends Command implements PromptsForMissingInput
{
    use ConcernsPromptsForMissingInput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package:init
                            {vendor : The vendor name}
                            {package : The package name}
                            {description : The package description}
                            {--author= : The package author. If not provided, it will be generated automatically}
                            {--license= : The package license. Available values: MIT, Apache-2.0, GPL-3.0, default: MIT}
                            {--namespace= : The package namespace. If not provided, it will be generated automatically}
                            {--package-version= : The package version. default: v0.0.1}
                            {--minimum-stability= : The package minimum-stability. Available values: dev, alpha, beta, RC, stable}
                            {--type= : The package type. Available values: project, library, metapackage, composer-plugin}
                            {--dir=* : The excluded directories}
                            {--path= : The path where the package will be initialized}';

    /**
     * The console command description.
     *
     * @var string
     */
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

    /**
     * Handle the command
     *
     * @return void
     * @throws \Throwable
     */
    public function handle(): void
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
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->initPackage();
    }

    /**
     * Print the package configuration
     *
     * @return void
     */
    protected function printConfiguration(): void
    {
        info("Package init on: <fg=white>[{$this->getPackagePath()}]</>");
        $this->newLine();
        info('These are the details you provided:');
        table(
            [
                'Vendor',
                'Package',
                'Author',
                'Description',
                'Namespace',
                'Package Version',
                'Minimum Stability',
                'Type',
                'License',
            ],
            [
                [
                    $this->getVendorName(),
                    $this->getPackageName(),
                    $this->getAuthor(),
                    $this->getPackageDescription(),
                    $this->getNamespace(),
                    $this->getPackageVersion(),
                    $this->getMinimumStability(),
                    $this->getPackageType(),
                    $this->getLicense(),
                ],
            ]
        );
        $this->newLine();
        info('List of excluded directories:');
        table(
            [],
            collect($this->getExcludedDirectories())->map(fn(string $directory) => [$this->getPackagePath($directory)])
        );
    }

    /**
     * Initialize the package
     *
     * @return void
     */
    public function initPackage(): void
    {
        $files = tap(new Finder)
            ->files()
            ->in($this->getPackagePath())
            ->exclude($this->getExcludedDirectories());

        collect($files)
            ->each(function (SplFileInfo $file) {
                (new \Illuminate\Pipeline\Pipeline)
                    ->send($file->getContents())
                    ->through([
                        // Vendor
                        function (string $content, \Closure $next) {
                            $vendor = $this->getVendorName();
                            info("Replacing vendor [$vendor]...");

                            return $next(
                                (new Replacer('vendor', $vendor))->replace($content)
                            );
                        },

                        // Package
                        function (string $content, \Closure $next) {
                            $package = $this->getPackageName();
                            info("Replacing package [$package]...");

                            return $next(
                                (new Replacer('package', $package))->replace($content)
                            );
                        },

                        // Author
                        function (string $content, \Closure $next) {
                            $author = $this->getAuthor();
                            info("Replacing author [$author]...");

                            return $next(
                                (new Replacer('author', $author))->replace($content)
                            );
                        },

                        // Description
                        function (string $content, \Closure $next) {
                            $description = $this->getPackageDescription();
                            info('Replacing description ['.Str::ucfirst($description).']...');

                            return $next(
                                (new Replacer('description', Str::lower($description)))->replace($content)
                            );
                        },

                        // Namespace
                        function (string $content, \Closure $next) {
                            $namespace = $this->getNamespace();
                            info("Replacing namespace [$namespace]...");

                            return $next(
                                (new Replacer('namespace', $namespace))
                                    ->modifierUsing('reverse', fn(string $value) => Str::of($value)->replace('\\', '/'))
                                    ->modifierUsing('escape', fn(string $value) => Str::of($value)->replace('\\', '\\\\'))
                                    ->replace($content)
                            );
                        },

                        // Package Version
                        function (string $content, \Closure $next) {
                            $packageVersion = $this->getPackageVersion();
                            info("Replacing package version [$packageVersion]...");

                            return $next(
                                (new Replacer('version', $packageVersion))->replace($content)
                            );
                        },

                        // Minimum Stability
                        function (string $content, \Closure $next) {
                            $minimumStability = $this->getMinimumStability();
                            info("Replacing minimum stability [$minimumStability]...");

                            return $next(
                                (new Replacer('minimum-stability', $minimumStability))->replace($content)
                            );
                        },

                        // Type
                        function (string $content, \Closure $next) {
                            $packageType = $this->getPackageType();
                            info("Replacing type [$packageType]...");

                            return $next(
                                (new Replacer('type', $packageType))->replace($content)
                            );
                        },

                        // License
                        function (string $content, \Closure $next) {
                            $license = $this->getLicense();
                            info("Replacing license [$license]...");

                            return $next(
                                (new Replacer('license', $license))->replace($content)
                            );
                        }
                        ])->then(fn (string $content) => File::put($file->getRealPath(), $content));
            });

        info('Package initialized successfully.');
    }

    /**
     * Get the vendor name
     *
     * @return string
     */
    protected function getVendorName(): string
    {
        return Str::lower($this->argument('vendor'));
    }

    /**
     * Get the package name
     *
     * @return string
     */
    protected function getPackageName(): string
    {
        return Str::lower($this->argument('package'));
    }

    /**
     * Get the package description
     *
     * @return string
     */
    protected function getPackageDescription(): string
    {
        return $this->argument('description');
    }

    /**
     * Get the package author
     *
     * @return string
     */
    protected function getAuthor(): string
    {
        return Str::title($this->option('author') ?? $this->getVendorName());
    }

    /**
     * Get the package license
     *
     * @return string
     */
    protected function getLicense(): string
    {
        return $this->option('license') ?? 'MIT';
    }

    /**
     * Get the package namespace
     *
     * @return string
     */
    protected function getNamespace(): string
    {
        return Str::title($this->option('namespace') ?? Str::title("{$this->getVendorName()}\\{$this->getPackageName()}"));
    }

    /**
     * Get the package version
     *
     * @return string
     */
    protected function getPackageVersion(): string
    {
        return Str::lower($this->option('package-version') ?? 'v0.0.1');
    }

    /**
     * Get the package minimum-stability
     *
     * @return string
     */
    protected function getMinimumStability(): string
    {
        return Str::lower($this->option('minimum-stability') ?? 'dev');
    }

    /**
     * Get the package type
     *
     * @return string
     */
    protected function getPackageType(): string
    {
        return Str::lower($this->option('type') ?? 'library');
    }

    /**
     * Get the excluded directories
     *
     * @return array
     */
    protected function getExcludedDirectories(): array
    {
        return [
            ...Arr::wrap($this->option('dir')),
            ...$this->excludedDirectories,
        ];
    }

    /**
     * Get the package path
     *
     * @param string|null $path
     * @return string
     */
    protected function getPackagePath(?string $path = null): string
    {
        return trim(($this->option('path') ?? getcwd()) . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * @inheritDoc
     *
     * @return array<string, \Closure>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'vendor' => fn() => text('What is the vendor name?'),
            'package' => fn() => text('What is the package name?'),
            'description' => fn() => text('What is the package description?'),
        ];
    }

    /**
     * Clear the console screen and the input arguments
     *
     * @return void
     */
    protected function clear(): void
    {
        clear();

        collect($this->arguments())
            ->each(
                fn($_, string $argument) => $this->input->setArgument($argument, null)
            );
    }
}
