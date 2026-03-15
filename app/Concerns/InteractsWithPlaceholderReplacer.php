<?php

namespace App\Concerns;

use App\PlaceholderReplacer;
use Closure;
use Illuminate\Support\Facades\File;
use SplFileInfo;

trait InteractsWithPlaceholderReplacer
{
    /**
     * The list of placeholder to look
     *
     * @var array<class-string<\App\Placeholders\BasePlaceholder>, null|string|\Closure(): ?string>
     */
    protected array $placeholders = [];

    /**
     * Add a placeholder
     *
     * @param  class-string<\App\Placeholders\BasePlaceholder>  $placeholder  The placeholder class to be added.
     * @param  null|string|Closure  $value  A string, a callback, or null that returns the value to be process for a placeholder.
     */
    protected function addPlaceholder(string $placeholder, null|string|Closure $value = null): self
    {
        $this->placeholders[$placeholder] = $value;

        return $this;
    }

    /**
     * Replace placeholders in the given files
     *
     * @param  SplFileInfo[]  $files  The files to process
     */
    private function replacePlaceholdersInFiles(array $files): void
    {
        foreach ($files as $file) {
            $this->pipeFileThroughPlaceholder($file);
        }
    }

    /**
     * Pipe the given file through all the placeholders and replace them
     *
     * @param  SplFileInfo  $file  The file to be processed.
     */
    private function pipeFileThroughPlaceholder(SplFileInfo $file): void
    {
        $content = File::get($file->getRealPath());
        $directory = dirname($file->getRealPath());
        $newFilename = $file->getFilename();

        $replacer = new PlaceholderReplacer;

        foreach ($this->placeholders as $placeholder => $callback) {
            ['value' => $value, 'skip' => $skip] = $this->resolvePlaceholderValue($callback);

            if ($skip) {
                continue;
            }

            $replacer->registerPlaceholderWithValue($placeholder, $value);
        }

        $content = $replacer->replace($content);
        $newFilename = $replacer->replace($newFilename);

        File::put($file->getRealPath(), $content);

        if ($newFilename !== $file->getFilename()) {
            File::move($file->getRealPath(), $directory.DIRECTORY_SEPARATOR.$newFilename);
        }
    }

    /**
     * Resolve the value from a placeholder$placeholder callback or string.
     *
     * @return array{value: mixed, skip: bool}
     */
    private function resolvePlaceholderValue(mixed $callback): array
    {
        if (is_callable($callback)) {
            $value = $callback();

            return ['value' => $value, 'skip' => $value === null];
        }

        return ['value' => $callback, 'skip' => false];
    }
}
