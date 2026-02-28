<?php

namespace App\Helpers;

/**
 * Recursively delete a directory and its contents
 *
 * @param  string  $dir  The directory to delete
 */
function rmdir_recursive(string $dir): void
{
    if (! file_exists($dir) || ! is_dir($dir)) {
        return;
    }

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
