<?php

declare(strict_types=1);

namespace App\Placeholders\Version\Concerns;

use App\Placeholders\Exceptions\InvalidVersionException;
use Illuminate\Support\Str;

trait InteractsWithVersion
{
    protected function matchVersionSegment(string $semver, string $segment): string
    {
        $matches = Str::of($semver)->matchAllWithGroups($this->getSemVerPattern());

        return (string) ($matches[0] ?? collect())->get($segment, $semver);
    }

    private function getSemVerPattern(): string
    {
        return InvalidVersionException::$pattern;
    }
}
