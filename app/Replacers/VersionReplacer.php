<?php

declare(strict_types=1);

namespace App\Replacers;

use App\Replacer;
use App\Replacers\Exceptions\InvalidVersionException;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Override;

/**
 * Replacer for `version` placeholders
 *
 * A `version` is composed of up to five parts: `major`, `minor`, `patch`, `pre-release`, and `metadata`, following the Semantic Versioning specification.
 *
 * @see https://semver.org/
 *
 * Examples of valid versions:
 * - 1.0.0
 * - 2.1.3-alpha
 * - 3.0.0+build.123
 * - 4.2.1-beta+exp.sha.5114f85
 *
 * Modifiers supported:
 * - `major`: The major version number (e.g., `1` in `1.0.0`)
 * - `minor`: The minor version number (e.g., `0` in `1.0.0`)
 * - `patch`: The patch version number (e.g., `0` in `1.0.0`)
 * - `pre`: The pre-release identifier (e.g., `alpha` in `2.1.3-alpha`)
 * - `meta`: The build metadata (e.g., `build.123` in `3.0.0+build.123`)
 * - `prefix`: Adds a 'v' prefix if not already present (e.g., `v1.0.0`)
 */
class VersionReplacer extends Builder
{
    protected static string $placeholder = 'version';

    protected static ?string $invalidFormatException = InvalidVersionException::class;

    #[Override]
    protected function configure(): Replacer
    {
        $this->replacer
            ->normalizeReplacementUsing(fn (Stringable $replacement) => $replacement->ltrim('v'))
            ->only(['major', 'minor', 'patch', 'pre', 'meta', 'prefix']);

        return parent::configure();
    }

    #[Override]
    public function modifiers(): array
    {
        $getSegment = function (Stringable $replacement, string $segment): Stringable {
            $pattern = InvalidVersionException::$pattern;
            $matches = $replacement->matchAllWithGroups($pattern);

            return Str::of(($matches[0] ?? collect())->get($segment, $replacement));
        };

        return [
            'major' => fn (Stringable $replacement) => $getSegment($replacement, 'major'),
            'minor' => fn (Stringable $replacement) => $getSegment($replacement, 'minor'),
            'patch' => fn (Stringable $replacement) => $getSegment($replacement, 'patch'),
            'pre' => fn (Stringable $replacement) => $getSegment($replacement, 'prerelease'),
            'meta' => fn (Stringable $replacement) => $getSegment($replacement, 'buildmetadata'),
            'prefix' => fn (Stringable $replacement) => $replacement->ltrim('v')->prepend('v'),
        ];
    }
}
