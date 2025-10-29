<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

trait InteractsWithReplacers
{
    /** @var array class-string */
    protected array $replacers = [];

    /**
     * @param  array<class-string, string|\Closure>  $replacers
     */
    public function addReplacers(array $replacers): void
    {
        foreach ($replacers as $replacer => $replacement) {
            $this->replacers[$replacer] = $replacement;
        }
    }

    public function getPackageReplacers(): array
    {
        return collect($this->replacers)
            ->map(function (string|\Closure $replacement, string $replacer) {
                if ($replacement instanceof \Closure) {
                    $replacement = $replacement();
                }

                return $this->pipeThroughReplacer($replacement, $replacer);
            })
            ->toArray();
    }

    /**
     * @param  class-string  $replacer
     */
    protected function pipeThroughReplacer(string $replacement, string $replacer): \Closure
    {
        return function (string $subject, \Closure $next) use ($replacement, $replacer): string {
            return $next($replacer::make($replacement)->replace($subject));
        };
    }
}
