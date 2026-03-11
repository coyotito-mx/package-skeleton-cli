<?php

declare(strict_types=1);

namespace App\Placeholders\Namespace\Modifiers\Concerns;

use Illuminate\Support\Str;

trait InteractsWithNamespace
{
    /**
     * Pattern to match an unescaped backslash, or a single slash (forward slash), but not escaped backslashes or double slashes
     *
     * Raw pattern: `/(?<separator>(?:(?<!\\)\\(?!\\)|(?<!\/)\/(?!\/)))/`
     *
     * Pattern breakdown:
     * - `/.../` : Delimiters for the regex pattern
     * - `(?<separator>...)` : Named capturing group "separator"
     * - `(?:...)` : Non-capturing group for alternation
     * - `(?<!\\)\\(?!\\)` : Matches a single backslash not preceded or followed by another
     * - `|` : Alternation (OR)
     * - `(?<!\/)\/(?!\/)` : Matches a single forward slash not preceded or followed by another
     */
    protected string $singleSeparatorPattern = '/(?<separator>(?:(?<!\\\\)\\\\(?!\\\\)|(?<!\/)\/(?!\/)))/';

    protected function unwrapNamespace(\Closure $both): \Closure
    {
        return function (string $replacement) use ($both): string {
            $separator = Str::contains($replacement, '\\') ? '\\' : '/';

            [$vendor, $package] = Str::of($replacement)->explode($separator)->map(fn ($part): string => Str::of($part)->trim()->toString())->all();

            return Str::of($separator)->trim()->wrap($both($vendor), $both($package))->toString();
        };
    }

    /**
     * Handle a namespace with either backslash or forward slash
     *
     * Keep in mind this only works if the namespace uses a single type of separator, not escaped ones.
     *
     * <code>
     *     NamespaceReplacer::handleNamespaceSeparator('Coyotito\\PackageSkeleton',
     *         backslash: fn ($namespace) => str_replace('\\', '\\\\', $namespace),
     *     ); // returns Stringable('Coyotito\\\\PackageSkeleton')
     *
     *    NamespaceReplacer::handleNamespaceSeparator('Coyotito/PackageSkeleton',
     *        slash: fn ($namespace) => str_replace('/', '\\', $namespace),
     *    ); // returns Stringable('Coyotito\\PackageSkeleton')
     *
     *    NamespaceReplacer::handleNamespaceSeparator('Coyotito\\PackageSkeleton',
     *        slash: fn ($namespace) => str_replace('\\', '/', $namespace), // This won't run
     *    ); // returns Stringable('Coyotito\\PackageSkeleton') unchanged
     *
     *    NamespaceReplacer::handleNamespaceSeparator('CoyotitoPackageSkeleton',
     *        backslash: fn ($namespace) => str_replace('\\', '\\\\', $namespace),
     *
     *        slash: fn ($namespace) => str_replace('/', '\\', $namespace),
     *    ); // returns Stringable('CoyotitoPackageSkeleton') unchanged because no separator is found
     *
     * </code>
     *
     * @param  string  $namespace  The namespace string
     * @param  ?\Closure(string $namespace): string  $backslash  The closure to run if the namespace uses backslash
     * @param  ?\Closure(string $namespace): string  $slash  The closure to run if the namespace uses forward slash
     */
    protected function handleNamespaceSeparator(string $namespace, ?\Closure $backslash = null, ?\Closure $slash = null): string
    {
        $namespaceForCheck = str_replace(' ', '', $namespace);

        if (filled($separator = $this->identifySingleSeparator($namespaceForCheck))) {
            $backslash ??= fn ($namespace) => $namespace;
            $slash ??= fn ($namespace) => $namespace;

            $namespace = Str::of($separator === '\\' ? $backslash(namespace: $namespace) : $slash(namespace: $namespace))->toString();
        }

        return $namespace;
    }

    /**
     * Identify if the namespace uses a single type of separator (either backslash or forward slash)
     *
     * @param  string  $namespace  The namespace string
     * @return ?string Returns the separator if found, null otherwise
     */
    protected function identifySingleSeparator(string $namespace): ?string
    {
        $match = Str::of($namespace)
            ->matchAllWithGroups($this->singleSeparatorPattern)
            ->first();

        return $match?->get('separator') ?? null;
    }
}
