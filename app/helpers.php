<?php

namespace App\Helpers {
    use Illuminate\Support\Collection;
    use SplFileInfo;
    use Symfony\Component\Finder\Finder;

    use function Illuminate\Filesystem\join_paths;

    /**
     * Recursively delete a directory and its contents
     *
     * @param  string  $dir  The directory to delete
     */
    function rmdir_recursive(string $dir): void
    {
        $handler = @opendir($dir);

        if ($handler === false) {
            return;
        }

        while (($file = readdir($handler)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$file;

            if (is_dir($path)) {
                rmdir_recursive($path);
            } else {
                unlink($path);
            }
        }

        closedir($handler);

        rmdir($dir);
    }

    /**
     * Get all the files in a directory recursively
     *
     * @param  string  $directory  The directory to search
     * @param  bool  $ignoreDotFiles  Whether to ignore dotfiles (files starting with a dot)
     * @return Collection<SplFileInfo>
     */
    function allFiles(string $directory, bool $ignoreDotFiles = false): Collection
    {
        $files = Finder::create()
            ->in($directory)
            ->ignoreDotFiles($ignoreDotFiles)
            ->files();

        return collect(iterator_to_array($files));
    }

    /**
     * Get all the files and folders in a directory (non-recursively)
     *
     * @param  string  $directory  The directory to search
     * @param  bool  $ignoreDotFiles  Whether to ignore dotfiles (files starting with a dot)
     * @return Collection<SplFileInfo>
     */
    function entries(string $directory, bool $ignoreDotFiles = false): Collection
    {
        return collect([
            ...(glob(join_paths($directory, '*')) ?: []),
            ...($ignoreDotFiles ? [] : (glob(join_paths($directory, '.*')) ?: [])),
        ])
            ->reject(fn (string $path) => in_array(basename($path), ['.', '..'], true))
            ->map(fn (string $path) => new SplFileInfo($path));
    }
}
