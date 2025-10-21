<?php

namespace App\Helpers {

    use InvalidArgumentException;

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
