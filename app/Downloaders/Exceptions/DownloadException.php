<?php

namespace App\Downloaders\Exceptions;

use Throwable;

class DownloadException extends DownloaderException
{
    public static function unableToCreateTempFolder(string $path): self
    {
        return new self("Unable to create skeleton temp folder at: {$path}");
    }

    public static function failToFetchSkeleton(string $url, ?Throwable $previous = null): self
    {
        return new self("Failed to download skeleton from: {$url}", previous: $previous);
    }

    public static function unexpectedZipStructure(string $expectedPath): self
    {
        return new self("Unexpected skeleton ZIP structure. Expected extracted root folder at: {$expectedPath}");
    }
}
