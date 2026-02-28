<?php

namespace App\Downloaders\Exceptions;

class DecompressException extends DownloaderException
{
    public static function zipExtensionNotLoaded(): self
    {
        return new self('The Zip extension is not installed or enabled.');
    }

    public static function failToOpenZip(string $skeletonPath): self
    {
        $skeleton = pathinfo($skeletonPath, PATHINFO_FILENAME);

        return new self("Failed to open ZIP file for skeleton: $skeleton");
    }

    public static function failToExtractZip(string $skeletonPath, string $destination): self
    {
        $skeleton = pathinfo($skeletonPath, PATHINFO_FILENAME);

        return new self("Failed to decompress skeleton: $skeleton to destination: $destination");
    }
}
