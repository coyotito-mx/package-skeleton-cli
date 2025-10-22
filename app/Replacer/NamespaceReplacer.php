<?php

declare(strict_types=1);

namespace App\Replacer;

use Illuminate\Support\Str;

class NamespaceReplacer
{
    use Traits\InteractsWithReplacer;

    protected static string $placeholder = 'namespace';

    protected static array $reversedSeparators = [
        '/' => '\\',
        '\\' => '/',
    ];

    public static function getModifiers(): array
    {
        $modifiedModifiers = collect(Replacer::getDefaultModifiers())
            ->map(function (\Closure $modifier, string $name) {
                return static function ($replacement) use ($modifier) {
                    $separator = static::identifySeparator($replacement);

                    [$vendor, $package] = match (true) {
                        $separator === '\\' => explode('\\', $replacement),
                        default => explode('/', $replacement),
                    };

                    return implode($separator, [$modifier($vendor), $modifier($package)]);
                };
            });

        return [
            ...$modifiedModifiers,
            'escape' => function (string $replacement): string {
                return Str::of($replacement)->replaceMatches('/[\/\\\\]/', function ($matches) {
                    $separator = static::identifySeparator($matches[0]);

                    return match (true) {
                        $separator === '\\' => '\\\\',
                        default => '\\/',
                    };
                })->toString();
            },
            'reverse' => function (string $replacement): string {
                $separator = static::identifySeparator($replacement);

                return $separator ? Str::replace($separator, static::$reversedSeparators[$separator], $replacement) : $replacement;
            },
        ];
    }

    public static function identifySeparator(string $replacement): ?string
    {
        return match (true) {
            Str::contains($replacement, '/') => '/',
            Str::contains($replacement, '\\') => '\\',
            default => null
        };
    }
}
