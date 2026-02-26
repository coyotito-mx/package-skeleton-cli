<?php

namespace App\Commands;

use App\Contracts\ComposerContract;
use App\Facades\Composer;
use App\Replacers\AuthorReplacer;
use App\Replacers\Builder;
use App\Replacers\DescriptionReplacer;
use App\Replacers\EmailReplacer;
use App\Replacers\Exceptions\InvalidFormatException;
use App\Replacers\Exceptions\InvalidNamespaceException;
use App\Replacers\LicenseDescriptionReplacer;
use App\Replacers\LicenseNameReplacer;
use App\Replacers\NamespaceReplacer;
use App\Replacers\PackageReplacer;
use App\Replacers\VendorReplacer;
use App\Replacers\VersionReplacer;
use App\Replacers\YearReplacer;
use Exception;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function Laravel\Prompts\alert;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;
use function Laravel\Prompts\warning;

class PackageCommand extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init
                            { vendor : The name of the package vendor (prompted if not provided) }
                            { package : The name of the package (prompted if not provided) }
                            { namespace : The package namespace (auto-generated as Vendor\Package if not provided) }
                            { author : The package author (prompted if not provided) }
                            { email : The package author email (prompted if not provided) }
                            { description : The package description (optional) }
                            { --proceed : Accept the configuration and proceed without confirmation }
                            { --no-install : Skip installing composer dependencies }
                            { --path= : The path to initialize the package in (defaults to current working directory) }
                            { --exclude=* : Paths to exclude when processing files }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize a new package structure';

    /**
     * The list of replacers to be used for replacing placeholders in files.
     *
     * @var array<class-string<Builder>, null|string|\Closure(): ?string>
     */
    protected array $replacers = [];

    /**
     * Paths to exclude when processing files for placeholder replacement.
     *
     * @var string[]
     */
    protected array $excludedPaths = [
        '.git',
        '.DS_Store',
        'vendor',
        'node_modules',
    ];

    /**
     * Available testing frameworks and their corresponding composer dependencies.
     *
     * @var array<string, array{name: string, dependencies: string[]}>
     */
    protected array $testingFrameworks = [
        'phpunit' => [
            'name' => 'PHPUnit',
            'dependencies' => ['phpunit/phpunit'],
        ],
        'pest' => [
            'name' => 'Pest',
            'dependencies' => ['pestphp/pest'],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addReplacer(VendorReplacer::class, fn () => $this->getVendor())
            ->addReplacer(PackageReplacer::class, fn () => $this->getPackage())
            ->addReplacer(NamespaceReplacer::class, fn () => $this->getNamespace())
            ->addReplacer(DescriptionReplacer::class, fn () => $this->getPackageDescription())
            ->addReplacer(AuthorReplacer::class, fn () => $this->getAuthor())
            ->addReplacer(EmailReplacer::class, fn () => $this->getEmail())
            ->addReplacer(LicenseNameReplacer::class, fn () => 'MIT')
            ->addReplacer(LicenseDescriptionReplacer::class, fn () => 'This package is open-sourced software licensed under the MIT license.')
            ->addReplacer(VersionReplacer::class, fn () => '0.0.1')
            ->addReplacer(YearReplacer::class); // This will replace the year with the current year
    }

    /**
     * Add a replacer to the list of replacers.
     *
     * @param  class-string<Builder>  $replacer  The replacer class to be added.
     * @param  string|callable|null  $value  A string, a callback, or null that returns the value to be used for replacement when this replacer is applied.
     */
    protected function addReplacer(string $replacer, null|string|callable $value = null): self
    {
        $this->replacers[$replacer] = $value;

        return $this;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        intro('Initializing package...');

        try {
            $this->displayConfiguration();
            $this->displayFilesToProcess();
            $this->displayExcludedPaths();

            if (! $this->option('proceed') && ! confirm('Do you want to proceed with this configuration?')) {
                error('Package initialization cancelled.');

                return self::FAILURE;
            }

            $this->replacePlaceholders();

            /** @phpstan-ignore-next-line */
            $this->installDependencies(shouldSkip: $this->option('no-install') ?? false);
        } catch (InvalidFormatException $e) {
            error($e->getMessage());

            logger()->error('Invalid format exception occurred', [
                'exception' => $e,
                'config' => [
                    'vendor' => $this->argument('vendor'),
                    'package' => $this->argument('package'),
                    'namespace' => $this->argument('namespace'),
                    'description' => $this->argument('description'),
                    'author' => $this->argument('author'),
                    'email' => $this->argument('email'),
                ],
            ]);

            return self::FAILURE;
        }

        $this->displaySuccessMessage();

        return self::SUCCESS;
    }

    /**
     * Get the package vendor name formatted in StudlyCase.
     */
    private function getVendor(): string
    {
        return Str::studly($this->argument('vendor'));
    }

    /**
     * Get the package name formatted in StudlyCase.
     */
    private function getPackage(): string
    {
        return Str::studly($this->argument('package'));
    }

    /**
     * Get the package namespace, either from user input or auto-generated from vendor/package.
     *
     * @throws InvalidNamespaceException
     */
    private function getNamespace(): string
    {
        $namespace = $this->argument('namespace')
            ? $this->argument('namespace')
            : Str::studly($this->getVendor()).'\\'.Str::studly($this->getPackage());

        InvalidNamespaceException::validate($namespace);

        return $namespace;
    }

    /**
     * Get the package description with first letter capitalized, or null if not provided.
     *
     * @phpstan-ignore-next-line
     */
    private function getPackageDescription(): ?string
    {
        return $this->argument('description');
    }

    /**
     * Get the author name formatted in Title Case.
     */
    private function getAuthor(): string
    {
        return Str::title($this->argument('author'));
    }

    /**
     * Get the author email in lowercase.
     */
    private function getEmail(): string
    {
        return Str::lower($this->argument('email'));
    }

    /**
     * Get all excluded paths, including user-defined ones from the --exclude option.
     *
     * @return string[]
     */
    private function getExcludedPaths(): array
    {
        $paths = Arr::wrap($this->option('exclude'));

        if (filled($paths)) {
            $customExcludedPaths = array_map(fn (string $path) => trim($path), $paths);

            return array_merge($this->excludedPaths, $customExcludedPaths);
        }

        return $this->excludedPaths;
    }

    /**
     * Display the package configuration table to the user.
     */
    private function displayConfiguration(): void
    {
        $header = ['Vendor', 'Package', 'Namespace'];
        $rows = [[
            $this->getVendor(),
            $this->getPackage(),
            $this->getNamespace(),
        ]];

        if ($description = $this->getPackageDescription()) {
            $header[] = 'Description';
            $rows[0][] = $description;
        }

        $header = [...$header, 'Author', 'Email'];
        $rows[0] = [...$rows[0], $this->getAuthor(), $this->getEmail()];

        table($header, $rows);
    }

    /**
     * Display the list of files that will be processed for placeholder replacement.
     */
    private function displayFilesToProcess(): void
    {
        $files = $this->getFilesToProcess();

        $files = implode(PHP_EOL, $files);

        table(['Files to process'], [[$files]]);
    }

    /**
     * Display the list of excluded paths that will not be processed.
     */
    private function displayExcludedPaths(): void
    {
        $excludedPaths = $this->getExcludedPaths();

        if (empty($excludedPaths)) {
            return;
        }

        $excludedPaths = implode(PHP_EOL, $excludedPaths);

        table(['Excluded Paths'], [[$excludedPaths]]);
    }

    /**
     * Display the success message with the initialized package namespace.
     */
    private function displaySuccessMessage(): void
    {
        outro("Package [{$this->getNamespace()}] initialized successfully!");
    }

    /**
     * Replace placeholders in the files to be processed.
     */
    private function replacePlaceholders(): void
    {
        $files = $this->getFilesToProcess();

        foreach ($files as $file) {
            $filename = basename($file);

            spin(fn () => $this->pipeFileThroughReplacers($file), "Processing file: $filename");
        }

        info('All files processed successfully!');
    }

    /**
     * Pipe the given file through all the replacers to replace the placeholders with the actual values.
     *
     * @param  string  $file  The path of the file to be processed.
     */
    private function pipeFileThroughReplacers(string $file): void
    {
        $content = File::get($file);
        $filename = basename($file);
        $directory = dirname($file);
        $newFilename = $filename;

        foreach ($this->replacers as $replacer => $callback) {
            ['value' => $value, 'skip' => $skip] = $this->resolveReplacerValue($callback);

            if ($skip) {
                continue;
            }

            $replacerInstance = $replacer::make($value);
            $content = $replacerInstance->replace($content);
            $newFilename = $replacerInstance->replace($newFilename);
        }

        File::put($file, $content);

        if ($newFilename !== $filename) {
            File::move($file, $directory.DIRECTORY_SEPARATOR.$newFilename);
        }
    }

    /**
     * Resolve the value from a replacer callback or string.
     *
     * @return array{value: mixed, skip: bool}
     */
    private function resolveReplacerValue(mixed $callback): array
    {
        if (is_callable($callback)) {
            $value = $callback();

            // Skip if callback returns null
            if (is_null($value)) {
                return ['value' => null, 'skip' => true];
            }

            return ['value' => $value, 'skip' => false];
        }

        return ['value' => $callback, 'skip' => false];
    }

    /**
     * Get the list of files to be processed, excluding the ones in the excluded paths.
     *
     * @return string[]
     */
    private function getFilesToProcess(): array
    {
        return collect($this->findFiles($this->getPath()))
            ->map(fn (SplFileInfo $file): string => $file->getRealPath())
            ->values()
            ->all();
    }

    /**
     * Find all files in the given directory, excluding paths defined in getExcludedPaths().
     *
     * @return SplFileInfo[]
     */
    private function findFiles(string $directory): array
    {
        $finder = Finder::create()
            ->in($directory)
            ->files()
            ->ignoreDotFiles(true)
            ->filter(fn (SplFileInfo $file) => ! $this->shouldExcludeFile($file))
            ->sortByName()
            ->getIterator();

        return iterator_to_array($finder);
    }

    /**
     * Determine if a file should be excluded from processing based on excluded paths.
     */
    private function shouldExcludeFile(SplFileInfo $file): bool
    {
        $excludedPaths = $this->getExcludedPaths();

        return Str::contains($file->getRealPath(), $excludedPaths);
    }

    /**
     * Install Composer dependencies for the selected testing framework.
     */
    protected function installDependencies(bool $shouldSkip = false): void
    {
        if ($shouldSkip) {
            warning('Skip composer dependencies installation.');

            return;
        }

        $dependencies = $this->getTestingFrameworkDependencies();

        alert('Installing composer dependencies...');

        tap(
            Composer::getFacadeRoot(),
            fn (ComposerContract $composer) => $composer->cwd = $this->getPath()
        )->require($dependencies, true);
    }

    /**
     * Get the list of Composer dependencies for the selected testing framework.
     *
     * @return string[]
     *
     * @throws Exception If invalid testing framework selected.
     */
    private function getTestingFrameworkDependencies(): array
    {
        $selected = $this->selectTestingFramework();

        return $this->testingFrameworks[$selected]['dependencies'] ?? throw new Exception('Invalid testing framework selected.');
    }

    /**
     * Prompt the user to select a testing framework.
     */
    private function selectTestingFramework(): string
    {
        $choices = collect($this->testingFrameworks)->mapWithKeys(fn ($framework, $key) => [$key => $framework['name']]);

        return select('Which testing framework do you want to use?', $choices->toArray(), default: 'pest');
    }

    /**
     * Fetch user's git global configuration.
     *
     * @return array<string, string>|null
     */
    private function fetchAuthorInformation(): ?array
    {
        $result = Process::run('git config --list');

        if ($result->failed() || ! $result->output()) {
            return null;
        }

        $options = collect(explode(PHP_EOL, $result->output()))
            ->mapWithKeys(function ($line) {
                $parts = explode('=', $line, 2);

                if (count($parts) === 2) {
                    return [trim($parts[0]) => trim($parts[1])];
                }

                return [];
            })
            ->filter(filled(...));

        if ($options->isEmpty() || ! $options->has(['user.name', 'user.email'])) {
            return null;
        }

        return [
            'author' => $options->get('user.name'),
            'email' => $options->get('user.email'),
        ];
    }

    /**
     * Get the path where the package should be initialized.
     */
    private function getPath(): string
    {
        return $this->option('path') ?? getcwd();
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        $info = $this->fetchAuthorInformation();

        return [
            'vendor' => fn (): string => text('Enter the package vendor name', 'Acme', required: true),
            'package' => fn (): string => text('Enter the package name', 'Package', required: true),
            'namespace' => fn (): ?string => text('Enter the package namespace', 'Vendor\\Package', hint: 'Optional, leave empty to auto-generate') ?: null,
            'author' => fn (): string => $info['author'] ?? text('Enter the author name', 'John Doe', required: true),
            'email' => fn (): string => $info['email'] ?? text('Enter the author\'s email', 'john@doe.com', required: true),
            'description' => fn (): ?string => textarea('Enter the package description') ?: null,
        ];
    }
}
