<?php

namespace App\Concerns;

use App\PlaceholderReplacer;
use App\Replacer;
use App\TagRemoval;
use Closure;
use Illuminate\Support\Facades\File;
use SplFileInfo;

trait InteractsWithReplacers
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

        $placeholderReplacer = new PlaceholderReplacer;

        foreach ($this->placeholders as $placeholder => $callback) {
            ['value' => $value, 'skip' => $skip] = $this->resolvePlaceholderValue($callback);

            if ($skip) {
                continue;
            }

            $placeholderReplacer->registerPlaceholderWithValue($placeholder, $value);
        }

        $this->replace($placeholderReplacer, $content, $newFilename);
        $this->replace(new TagRemoval, $content, $newFilename);

        File::put($file->getRealPath(), $content);

        if ($newFilename !== $file->getFilename()) {
            File::move($file->getRealPath(), $directory.DIRECTORY_SEPARATOR.$newFilename);
        }
    }

    private function replace(Replacer $replacer, string &...$content): void
    {
        foreach ($content as &$value) {
            $value = $replacer->replace($value);
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
