<?php

declare(strict_types=1);

namespace App\Commands;

use App\Commands\Concerns\HasPackageConfiguration;
use App\Commands\Concerns\InteractsWithBinaryRemoval;
use App\Commands\Concerns\InteractsWithTestingFramework;
use App\Concerns\InteractsWithProcess;
use App\Downloaders\Exceptions\DownloaderException;
use App\Downloaders\Exceptions\DownloadException;
use App\Downloaders\PackageSkeletonDownloader;
use App\Placeholders\Exceptions\InvalidFormatException;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
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
use function Laravel\Prompts\warning;

class PackageCommand extends Command implements PromptsForMissingInput
{
    use HasPackageConfiguration;
    use InteractsWithBinaryRemoval;
    use InteractsWithProcess;
    use InteractsWithTestingFramework;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize package structure';

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

    public function __construct(protected PackageSkeletonDownloader $downloader)
    {
        parent::__construct();
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

            $this->displayPackageConfiguration();

            $this->displayPaths('Files to process', $this->getFilesToProcess());
            $this->displayPaths('Excluded Paths', $this->getExcludedPaths());

            if (! $this->option('proceed') && ! confirm('Do you want to proceed with this configuration?')) {
                error('Package initialization cancelled.');

                return self::FAILURE;
            }

            $this->ensureLicenseFileExists();

            $this->processFiles(
                $this->getFilesToProcess()
            );

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
     * Display the success message with the initialized package namespace.
     */
    private function displaySuccessMessage(): void
    {
        outro("Package [{$this->getNamespace()}] initialized successfully!");
    }

    /**
     * Get the list of files to be processed, excluding the ones in the excluded paths.
     *
     * @return SplFileInfo[]
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

        $availableTestingFrameworks = array_combine(
            $keys = array_keys($this->availableTestingFrameworks),
            $keys
        );

        $dependency = $this->resolveTestingFramework(
            select('Which testing framework do you want to use?', $availableTestingFrameworks, required: true)
        );

        alert('Installing composer dependencies...');

        $dependency->install();
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
}
