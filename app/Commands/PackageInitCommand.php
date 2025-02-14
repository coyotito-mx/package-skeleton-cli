<?php

namespace App\Commands;

use App\Replacer;
use Illuminate\Console\Concerns\PromptsForMissingInput as ConcernsPromptsForMissingInput;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function Laravel\Prompts\clear;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;

/**
 * Class PackageInitCommand
 *
 * This command initializes a new package with the provided configuration.
 */
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

    /**
     * List of directories to be excluded.
     */
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
     * Execute the console command.
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
        } catch (\Throwable $th) {
            $this->error($th->getMessage());

            return;
        }

        $this->initPackage();

        $this->installDependencies();

        $this->installTestingTools();
    }

    /**
     * Print the current configuration to the console.
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
            [[
                $this->getVendorName(),
                $this->getPackageName(),
                $this->getAuthor(),
                $this->getPackageDescription(),
                $this->getNamespace(),
                $this->getPackageVersion(),
                $this->getMinimumStability(),
                $this->getPackageType(),
                $this->getLicense(),
            ]]
        );
        $this->newLine();
        info('List of excluded directories:');
        table(
            [],
            collect($this->getExcludedDirectories())
                ->map(fn (string $directory) => [$this->getPackagePath($directory)])
        );
    }

    /**
     * Initialize the package by processing all files and applying replacements.
     */
    public function initPackage(): void
    {
        $this->getFiles()->each(function (SplFileInfo $file) {
            (new \Illuminate\Pipeline\Pipeline)
                ->send($file->getContents())
                ->through($this->getReplacers())
                ->then(fn (string $content) => File::put($file->getRealPath(), $content));
        });

        info('Package initialized successfully.');
    }

    /**
     * Get the collection of files to be processed.
     */
    public function getFiles(): Collection
    {
        return collect(
            tap(new Finder)
                ->files()
                ->in($this->getPackagePath())
                ->exclude($this->getExcludedDirectories())
        );
    }

    /**
     * Get the array of replacers to be applied to the files.
     */
    protected function getReplacers(): array
    {
        return [
            $this->createReplacer('vendor', $this->getVendorName()),
            $this->createReplacer('package', $this->getPackageName()),
            $this->createReplacer('author', $this->getAuthor()),
            $this->createReplacer('description', $this->getPackageDescription()),
            $this->createReplacer('namespace', $this->getNamespace(), [
                'reverse' => fn (string $value) => Str::of($value)->replace('\\', '/'),
                'escape' => fn (string $value) => Str::of($value)->replace('\\', '\\\\'),
            ]),
            $this->createReplacer('version', $this->getPackageVersion()),
            $this->createReplacer('minimum-stability', $this->getMinimumStability()),
            $this->createReplacer('type', $this->getPackageType()),
            $this->createReplacer('license', $this->getLicense()),
        ];
    }

    /**
     * Create a replacer closure for a given key and value.
     */
    protected function createReplacer(string $key, string $value, array $modifiers = []): \Closure
    {
        return function (string $content, \Closure $next) use ($key, $value, $modifiers) {
            info(sprintf('Replacing %s [%s]...', Str::of($key)->slug(' ')->toString(), $value));
            $replacer = new Replacer($key, $value);

            foreach ($modifiers as $modifier => $callback) {
                $replacer->modifierUsing($modifier, $callback);
            }

            return $next($replacer->replace($content));
        };
    }

    /**
     * Get the vendor name from the command arguments.
     */
    protected function getVendorName(): string
    {
        return Str::lower($this->argument('vendor'));
    }

    /**
     * Get the package name from the command arguments.
     */
    protected function getPackageName(): string
    {
        return Str::lower($this->argument('package'));
    }

    /**
     * Get the package description from the command arguments.
     */
    protected function getPackageDescription(): string
    {
        return $this->argument('description');
    }

    /**
     * Get the author name from the command options or generate it automatically.
     */
    protected function getAuthor(): string
    {
        return Str::title($this->option('author') ?? $this->getVendorName());
    }

    /**
     * Get the license type from the command options or default to MIT.
     */
    protected function getLicense(): string
    {
        return $this->option('license') ?? 'MIT';
    }

    /**
     * Get the namespace from the command options or generate it automatically.
     */
    protected function getNamespace(): string
    {
        return Str::title($this->option('namespace') ?? Str::title("{$this->getVendorName()}\\{$this->getPackageName()}"));
    }

    /**
     * Get the package version from the command options or default to v0.0.1.
     */
    protected function getPackageVersion(): string
    {
        return Str::lower($this->option('package-version') ?? 'v0.0.1');
    }

    /**
     * Get the minimum stability from the command options or default to dev.
     */
    protected function getMinimumStability(): string
    {
        return Str::lower($this->option('minimum-stability') ?? 'dev');
    }

    /**
     * Get the package type from the command options or default to library.
     */
    protected function getPackageType(): string
    {
        return Str::lower($this->option('type') ?? 'library');
    }

    /**
     * Get the list of excluded directories.
     */
    protected function getExcludedDirectories(): array
    {
        return [...Arr::wrap($this->option('dir')), ...$this->excludedDirectories];
    }

    protected function installDependencies(): void
    {
        $this->info('Installing dependencies...');

        if (! confirm('Do you want to install the dependencies?')) {
            $this->info('Dependencies were not installed.');

            return;
        }

        \App\Facades\Composer::install();
    }

    protected function installTestingTools(): void
    {
        $this->info('Installing testing tools...');

        if (! confirm('Do you want to install the testing tools?')) {
            $this->info('Testing tools were not installed.');

            return;
        }

        $tool = $this->choice('Which testing tools do you want to install?', [
            'phpunit' => 'PHPUnit',
            'pest' => 'Pest',
        ], 'pest');

        if ($tool === 'phpunit') {
            \App\Facades\Composer::require('phpunit/phpunit', true);
        } else {
            \App\Facades\Composer::require('pestphp/pest', true);
        }
    }

    /**
     * Get the package path, optionally appending a subpath.
     */
    protected function getPackagePath(?string $path = null): string
    {
        return trim(($this->option('path') ?? getcwd()).($path ? DIRECTORY_SEPARATOR.$path : ''));
    }

    /**
     * Define the prompts for missing arguments.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'vendor' => fn () => text('What is the vendor name?'),
            'package' => fn () => text('What is the package name?'),
            'description' => fn () => text('What is the package description?'),
        ];
    }

    /**
     * Clear the current input arguments.
     */
    protected function clear(): void
    {
        clear();
        collect($this->arguments())->each(fn ($_, string $argument) => $this->input->setArgument($argument, null));
    }
}
