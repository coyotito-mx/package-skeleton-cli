<?php

namespace App\Replacers\Concerns;

use Closure;
use Illuminate\Support\Facades\File;
use SplFileInfo;

trait InteractsWithReplacers
{
    /**
     * The list of replacers to be used for replacing placeholders in files.
     *
     * @var array<class-string<Builder>, null|string|\Closure(): ?string>
     */
    protected array $replacers = [];

    /**
     * Add a replacer to the list of replacers.
     *
     * @param  class-string<Builder>  $replacer  The replacer class to be added.
     * @param  null|string|Closure  $value  A string, a callback, or null that returns the value to be used for replacement when this replacer is applied.
     */
    protected function addReplacer(string $replacer, null|string|Closure $value = null): self
    {
        $this->replacers[$replacer] = $value;

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
            $this->pipeFileThroughReplacers($file);
        }
    }

    /**
     * Pipe the given file through all the replacers to replace the placeholders with the actual values.
     *
     * @param  SplFileInfo  $file  The file to be processed.
     */
    private function pipeFileThroughReplacers(SplFileInfo $file): void
    {
        $content = File::get($file);
        $directory = dirname($file->getRealPath());
        $newFilename = $file->getFilename();

        foreach ($this->replacers as $replacer => $callback) {
            ['value' => $value, 'skip' => $skip] = $this->resolveReplacerValue($callback);

            if ($skip) {
                continue;
            }

            $replacerInstance = $replacer::make($value);
            $content = $replacerInstance->replace($content);
            $newFilename = $replacerInstance->replace($newFilename);
        }

        File::put($file, $content);

        if ($newFilename !== $file->getFilename()) {
            File::move($file, $directory.DIRECTORY_SEPARATOR.$newFilename);
        }
    }

    /**
     * Resolve the value from a replacer callback or string.
     *
     * @return array{value: mixed, skip: bool}
     */
    private function resolveReplacerValue(mixed $callback): array
    {
        if (is_callable($callback)) {
            $value = $callback();

            return ['value' => $value, 'skip' => $value === null];
        }

        return ['value' => $callback, 'skip' => false];
    }
}
