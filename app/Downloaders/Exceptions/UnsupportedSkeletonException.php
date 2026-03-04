<?php

namespace App\Downloaders\Exceptions;

class UnsupportedSkeletonException extends DownloaderException
{
    public static function make(string $skeleton): self
    {
        return new self("Unsupported skeleton type: {$skeleton}");
    }
}
