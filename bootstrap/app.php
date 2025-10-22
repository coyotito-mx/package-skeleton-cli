<?php

use LaravelZero\Framework\Application;

use function Illuminate\Filesystem\join_paths;

if (! defined('APP_DATA')) {
    $appName = '.package-skeleton-cli';

    define('APP_DATA', match (PHP_OS_FAMILY) {
        'Darwin' => join_paths(getenv('HOME'), 'Library', 'Application Support', $appName),
        'Windows' => join_paths(getenv('LOCALAPPDATA'), $appName),
        'Linux' => join_paths(getenv('XDG_DATA_HOME') ?: getenv('HOME'), $appName),
    });
}

return Application::configure(basePath: dirname(__DIR__))->create();
