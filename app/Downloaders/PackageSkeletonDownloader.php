<?php

declare(strict_types=1);

namespace App\Downloaders;

use App\Concerns\InteractsWithProcess;
use App\Downloaders\Exceptions\DecompressException;
use App\Downloaders\Exceptions\DownloadException;
use App\Downloaders\Exceptions\UnsupportedSkeletonException;
use Illuminate\Process\Exceptions\ProcessFailedException;

use function Illuminate\Filesystem\join_paths;

final class PackageSkeletonDownloader
{
    use InteractsWithProcess;

    public function __construct(private readonly ?string $tempFolder = null)
    {
        //
    }

    /**
     * Identifier for the Laravel package skeleton
     */
    const string SKELETON_LARAVEL = 'laravel';

    /**
     * Identifier for the vanilla package skeleton
     */
    const string SKELETON_VANILLA = 'vanilla';

    /**
     * The available package skeletons and their corresponding GitHub repositories
     *
     * @var array<string, string>
     */
    private array $skeletons = [
        self::SKELETON_LARAVEL => 'laravel-package-skeleton',
        self::SKELETON_VANILLA => 'package-skeleton',
    ];

    /**
     * Download the package skeleton
     *
     * @param  string  $skeleton  The type of skeleton to download (e.g., 'laravel', 'vanilla')
     * @return string The path to the extracted root folder (derived from the ZIP filename).
     *                This assumes GitHub archive structure where the extracted root folder
     *                matches `<repository>-main` for `.../refs/heads/main.zip`
     */
    public function download(string $skeleton): string
    {
        $temp = $this->getSkeletonTempFolder();

        $this->cleanup($temp);

        $skeletonZip = $this->fetch($skeleton, $temp);

        $this->decompress($skeletonZip);

        $extractedPath = join_paths($temp, pathinfo($skeletonZip, PATHINFO_FILENAME));

        if (! is_dir($extractedPath)) {
            throw DownloadException::unexpectedZipStructure($extractedPath);
        }

        return $extractedPath;
    }

    /**
     * Decompress the downloaded skeleton right into the same directory of the zip file
     */
    protected function decompress(string $skeletonPath): void
    {
        $destination = dirname($skeletonPath);

        if (! extension_loaded('zip')) {
            throw DecompressException::zipExtensionNotLoaded();
        }

        $zip = new \ZipArchive;
        $isOpen = false;

        try {
            if ($zip->open($skeletonPath) !== true) {
                throw DecompressException::failToOpenZip($skeletonPath);
            }

            $isOpen = true;

            if (! @$zip->extractTo($destination)) {
                throw DecompressException::failToExtractZip($skeletonPath, $destination);
            }
        } finally {
            if ($isOpen) {
                $zip->close();
            }
        }
    }

    /**
     * Fetch the skeleton repo from GitHub
     *
     * This method will overwrite any already existing skeleton file in the `$destination` directory
     *
     * @param  string  $skeleton  The type of package to fetch
     * @param  string  $destination  The directory where the skeleton should be saved
     *
     * @throws DownloadException if cURL fails to download the skeleton
     */
    private function fetch(string $skeleton, string $destination): string
    {
        $skeleton = $this->resolvePackageSkeleton($skeleton);
        $skeletonPath = join_paths($destination, $skeleton['filename']);

        if (file_exists($skeletonPath)) {
            unlink($skeletonPath);
        }

        try {
            $this->makeProcess('curl', ['-L', $skeleton['url'], '-o', $skeletonPath])->run()->throw();
        } catch (ProcessFailedException $exception) {
            throw DownloadException::failToFetchSkeleton($skeleton['url'], previous: $exception);
        }

        return $skeletonPath;
    }

    /**
     * Resolve the URL and filename for the given skeleton type
     *
     * @return array{url: string, filename: string}
     *
     * @throws UnsupportedSkeletonException if the skeleton type is not supported
     */
    protected function resolvePackageSkeleton(string $skeleton): array
    {
        $repo = $this->skeletons[$skeleton] ?? throw UnsupportedSkeletonException::make($skeleton);

        return [
            'url' => "https://github.com/coyotito-mx/$repo/archive/refs/heads/main.zip",
            'filename' => "$repo-main.zip",
        ];
    }

    /**
     * Get the temporary folder path for storing downloaded skeletons
     *
     * @return string The path to the temporary folder for skeletons
     *
     * @throws DownloadException if the temporary folder cannot be created or is not writable
     */
    protected function getSkeletonTempFolder(): string
    {
        $skeletonTempFolder = $this->tempFolder ?? join_paths(APP_DATA, 'skeletons');

        if (! file_exists($skeletonTempFolder)
            && ! @mkdir($skeletonTempFolder, 0755, true)
            && ! is_dir($skeletonTempFolder)) {
            throw DownloadException::unableToCreateTempFolder($skeletonTempFolder);
        }

        return $skeletonTempFolder;
    }

    /**
     * Clean up the given directory by removing all files and subdirectories within it.
     */
    private function cleanup(string $directory): void
    {
        if (! file_exists($directory)) {
            return;
        }

        $files = \App\Helpers\entries($directory);

        if (blank($files)) {
            return;
        }

        foreach ($files as $file) {
            $path = $file->getPathname();

            if (in_array(basename($path), ['.', '..'], true)) {
                continue;
            }

            if ($file->isFile() || $file->isLink()) {
                unlink($path);

                continue;
            }

            \App\Helpers\rmdir_recursive($path);
        }
    }
}
