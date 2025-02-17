<?php

declare(strict_types=1);

namespace App\Replacer;

use Illuminate\Support\Str;

class NamespaceReplacer
{
    use Traits\InteractsWithReplacer;

    protected string $placeholder = 'namespace';

    protected array $reversedSeparators = [
        '/' => '\\',
        '\\' => '/',
    ];

    public function getModifiers(): array
    {
        return [
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

                return $separator ? Str::replace($separator, $this->reversedSeparators[$separator], $replacement) : $replacement;
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
