<?php

namespace App\Helpers {

    use InvalidArgumentException;

    /**
     * Create a directory
     *
     * Works the same as `\mkdir` but if the directory already exists, will not display an error
     */
    function mkdir(string $path, int $permissions = 0777, bool $recursive = false, $context = null): bool
    {
        if (is_file($path)) {
            throw new InvalidArgumentException('The path should be a directory, not a file');
        }

        if (file_exists($path)) {
            return false;
        }

        return \mkdir($path, $permissions, $recursive, $context);
    }

    /**
     * @throw InvalidArgumentException if the given path is a file.
     */
    function rmdir_recursive(string $path, bool $preserveRoot = false): void
    {
        $root = $path;

        $walk = function ($path) use (&$walk, $root, $preserveRoot) {
            if (! file_exists($path)) {
                return;
            }

            if (is_file($path)) {
                throw new InvalidArgumentException("The given path is a file: $path");
            }

            foreach (scandir($path) as $file) {
                if (in_array($file, ['.', '..'])) {
                    continue;
                }

                $file = $path.DIRECTORY_SEPARATOR.$file;

                if (is_dir($file)) {
                    $walk($file);
                } else {
                    unlink($file);
                }
            }

            if (! $preserveRoot || $path !== $root) {
                rmdir($path);
            }
        };

        $walk($path);
    }
}
