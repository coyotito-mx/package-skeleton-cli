<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.DIRECTORY_SEPARATOR.'app',
        __DIR__.DIRECTORY_SEPARATOR.'config',
        __DIR__.DIRECTORY_SEPARATOR.'tests',
    ])
    ->withSkip([
        __DIR__.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures',
    ])
    ->withPhpSets()
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0);
