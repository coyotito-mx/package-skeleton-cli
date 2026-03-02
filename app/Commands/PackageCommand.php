<?php

declare(strict_types=1);

namespace App\Commands;

use App\Commands\Concerns\InteractsWithBinaryRemoval;
use App\Contracts\ComposerContract;
use App\Downloaders\Exceptions\DownloaderException;
use App\Downloaders\Exceptions\DownloadException;
use App\Downloaders\PackageSkeletonDownloader;
use App\Facades\Composer;
use App\Replacers\AuthorReplacer;
use App\Replacers\Concerns\InteractsWithReplacers;
use App\Replacers\DescriptionReplacer;
use App\Replacers\EmailReplacer;
use App\Replacers\Exceptions\InvalidFormatException;
use App\Replacers\Exceptions\InvalidNamespaceException;
use App\Replacers\LicenseNameReplacer;
use App\Replacers\NamespaceReplacer;
use App\Replacers\PackageReplacer;
use App\Replacers\VendorReplacer;
use App\Replacers\VersionReplacer;
use App\Replacers\YearReplacer;
use Exception;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use SplFileInfo;

use function App\Helpers\entries;
use function Illuminate\Filesystem\join_paths;
use function Laravel\Prompts\alert;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;
use function Laravel\Prompts\warning;

class PackageCommand extends Command implements PromptsForMissingInput
{
    use InteractsWithBinaryRemoval;
    use InteractsWithReplacers;

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
                            { --bootstrap= : Initialize a new package (options: laravel, vanilla) }
                            { --force : Force bootstrapping even if the target directory is not empty (use with --bootstrap) }
                            { --proceed : Accept the configuration and proceed without confirmation }
                            { --no-install : Skip installing composer dependencies }
                            { --path= : The path to initialize the package in (defaults to current working directory) }
                            { --skip-license : Skip creating a LICENSE file if one does not exist }
                            { --exclude=* : Paths to exclude when processing files }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize a new package structure';

    protected PackageSkeletonDownloader $downloader;

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

    public function __construct(Application $app)
    {
        $this->downloader = $app->make(PackageSkeletonDownloader::class);

        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addReplacer(VendorReplacer::class, fn () => $this->getVendor())
            ->addReplacer(PackageReplacer::class, fn () => $this->getPackage())
            ->addReplacer(NamespaceReplacer::class, fn () => $this->getNamespace())
            ->addReplacer(DescriptionReplacer::class, fn () => $this->getPackageDescription())
            ->addReplacer(AuthorReplacer::class, fn () => $this->getAuthor())
            ->addReplacer(EmailReplacer::class, fn () => $this->getEmail())
            ->addReplacer(LicenseNameReplacer::class, fn () => 'MIT')
            ->addReplacer(VersionReplacer::class, fn () => '0.0.1')
            ->addReplacer(YearReplacer::class); // This will replace the year with the current year
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        intro('Initializing package...');

        try {
            if ($skeleton = $this->option('bootstrap')) {
                $this->bootstrapPackage($skeleton, $this->option('force'));
            }

            $files = $this->getFilesToProcess();

            $this->displayConfiguration();
            $this->displayFilesToProcess($files);
            $this->displayExcludedPaths();

            if (! $this->option('proceed') && ! confirm('Do you want to proceed with this configuration?')) {
                error('Package initialization cancelled.');

                return self::FAILURE;
            }

            $this->ensureLicenseFileExists();

            $this->replacePlaceholdersInFiles($files);

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
        } catch (DownloaderException $e) {
            error($e->getMessage());

            logger()->error('Downloader exception occurred', [
                'exception' => $e,
                'skeleton' => $this->option('bootstrap'),
            ]);

            return self::FAILURE;
        }

        $this->displaySuccessMessage();

        $this->promptForCliRemoval();

        return self::SUCCESS;
    }

    /**
     * Prompt the user to confirm if they want to remove the CLI executable
     */
    protected function promptForCliRemoval(): void
    {
        if (! confirm('Do you want to remove this CLI now?', default: false)) {
            return;
        }

        try {
            if ($this->deleteBinary()) {
                info('CLI executable removed successfully.');
            } else {
                warning('CLI executable could not be removed. Please remove it manually.');
            }
        } catch (\RuntimeException $e) {
            warning($e->getMessage());
        }
    }

    protected function bootstrapPackage(string $skeleton, bool $force = false): void
    {
        $skeleton = Str::lower($skeleton);

        alert("Bootstrapping package using skeleton: {$skeleton}...");

        if (! File::isEmptyDirectory($this->getPath())) {
            if (! $force && ! confirm('The target directory is not empty. Do you want to proceed and overwrite existing files?', false)) {
                throw new DownloaderException('Package bootstrapping cancelled by user due to non-empty target directory.');
            }

            alert('Proceeding with bootstrapping and overwriting existing files...');
        }

        $destination = $this->getPath();

        $moved = entries($this->downloader->download($skeleton))
            ->filter(static function (SplFileInfo $entry) use ($destination): bool {
                $newPath = join_paths($destination, $entry->getFilename());

                if ($entry->isDir()) {
                    return ! File::moveDirectory($entry->getRealPath(), $newPath, true);
                } else {
                    if (File::exists($newPath)) {
                        File::delete($newPath);
                    }

                    return ! File::move($entry->getRealPath(), $newPath);
                }
            });

        if ($moved->isNotEmpty()) {
            throw new DownloadException('Some files could not be moved during the bootstrapping process.');
        }
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
        $namespace = $this->argument('namespace') ?: Str::studly($this->getVendor()).'\\'.Str::studly($this->getPackage());

        InvalidNamespaceException::validate($namespace);

        return $namespace;
    }

    /**
     * Get the package description, or null if not provided.
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
            $customExcludedPaths = array_values(array_filter(
                array_map(trim(...), $paths),
                fn (string $path) => $path !== ''
            ));

            return array_values(array_unique(array_merge($this->excludedPaths, $customExcludedPaths)));
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
     *
     * @param  SplFileInfo[]  $files  The list of files to be processed.
     */
    private function displayFilesToProcess(array $files): void
    {
        $listOfFiles = implode(PHP_EOL, $files);

        table(['Files to process'], [[$listOfFiles]]);
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
     * Get the list of files to be processed, excluding the ones in the excluded paths.
     *
     * @return string[]
     */
    private function getFilesToProcess(): array
    {
        return \App\Helpers\allFiles($this->getPath())
            ->filter(fn (SplFileInfo $file) => ! $this->shouldExcludeFile($file))
            ->values()
            ->all();
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

    private function ensureLicenseFileExists(): void
    {
        $licensePath = join_paths($this->getPath(), 'LICENSE.md');

        if ($this->option('skip-license') || File::exists($licensePath) || ! confirm('No LICENSE file found. Do you want to create one with the MIT license?', default: true)) {
            return;
        }

        File::copy(join_paths(app()->basePath(), 'stubs', 'LICENSE.stub'), $licensePath);

        info('LICENSE file created successfully!');
    }

    /**
     * Get the path where the package should be initialized.
     */
    private function getPath(): string
    {
        return $this->option('path') ?? getcwd();
    }

    /**
     * Define interactive prompts for missing required command arguments.
     *
     * @return array<string, \Closure(): string|null>
     */
    #[\Override]
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
