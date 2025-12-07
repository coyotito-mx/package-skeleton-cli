<?php

declare(strict_types=1);

namespace App\Replacers;

use App\Replacer;
use App\Replacers\Exceptions\InvalidNamespace;
use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

/**
 * Replacer for `namespace` placeholders
 *
 * A `namespace` is composed of two parts: `vendor` and `package`, separated by either a backslash (`\`).
 *
 * @see InvalidNamespace::$namespacePattern for the regex pattern used to validate the namespace format.
 *
 * Examples of valid namespaces:
 * - Acme\Utils
 * - FooBar\BazQux
 * - Vendor\Package
 * - Vendor123\Package456
 * - Coyotito\PackageSkeleton
 *
 * Modifiers supported:
 * - upper
 * - lower
 * - title
 * - snake
 * - kebab
 * - slug
 * - camel
 * - escape
 * - reverse
 *
 * > Note:
 * >
 * > 1. The `escape` modifier will escape single backslashes only, not already escaped ones or forward slashes.
 * > 2. The `reverse` modifier will switch single backslashes to forward slashes and vice versa, but not escaped backslashes or double slashes.
 * > 3. Modifiers are applied to both parts of the namespace (`vendor` and `package`) separately.
 */
class NamespaceReplacer extends Builder
{
    protected static string $placeholder = 'namespace';

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
    protected static string $singleSeparatorPattern = '/(?<separator>(?:(?<!\\\\)\\\\(?!\\\\)|(?<!\/)\/(?!\/)))/';

    public function __construct(string $replacement)
    {
        InvalidNamespace::validate($replacement);

        parent::__construct($replacement);
    }

    protected function configure(Replacer $replacer): void
    {
        parent::configure($replacer);

        tap($replacer)->normalizeReplacementUsing(fn (Stringable $replacement) => static::unwrapNamespace(fn (Stringable $replacement) => $replacement->trim()->headline())($replacement)
        )->transformBeforeReplaceUsing(fn (Stringable $replacement): string => (string) $replacement->replace(' ', ''));
    }

    protected function modifiers(): array
    {
        return [
            'upper' => static::unwrapNamespace(static fn (Stringable $replacement) => $replacement->upper()),
            'lower' => static::unwrapNamespace(static fn (Stringable $replacement) => $replacement->lower()),
            'title' => static::unwrapNamespace(static fn (Stringable $replacement) => $replacement->title()),
            'snake' => static::unwrapNamespace(static fn (Stringable $replacement) => $replacement->snake()),
            'kebab' => static::unwrapNamespace(static fn (Stringable $replacement) => $replacement->kebab()),
            'slug' => static::unwrapNamespace(static fn (Stringable $replacement) => $replacement->slug()),
            'camel' => static::unwrapNamespace(static fn (Stringable $replacement) => $replacement->camel()),
            'escape' => static function (Stringable $replacement): Stringable {
                return static::handleNamespaceSeparator(
                    (string) $replacement,
                    fn (string $namespace) => str_replace('\\', '\\\\', $namespace),
                );
            },
            'reverse' => static function (Stringable $replacement): Stringable {
                return static::handleNamespaceSeparator(
                    (string) $replacement,
                    fn (string $namespace) => str_replace('\\', '/', $namespace),
                    fn (string $namespace) => str_replace('/', '\\', $namespace)
                );
            },
        ];
    }

    protected function getExcludedModifiers(): array
    {
        return ['acronym'];
    }

    protected static function unwrapNamespace(Closure $both): Closure
    {
        return function (Stringable $replacement) use ($both): Stringable {
            $separator = $replacement->contains('\\') ? '\\' : '/';

            [$vendor, $package] = $replacement->explode($separator)->map(fn ($part) => Str::of($part)->trim())->all();

            return Str::of($separator)->trim()->wrap($both($vendor), $both($package));
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
     * </code>
     *
     * @param string $namespace The namespace string
     * @param ?Closure(string $namespace): string $backslash The closure to run if the namespace uses backslash
     * @param ?Closure(string $namespace): string $slash The closure to run if the namespace uses forward slash
     */
    public static function handleNamespaceSeparator(string $namespace, ?Closure $backslash = null, ?Closure $slash = null): Stringable
    {
        $namespaceForCheck = str_replace(' ', '', $namespace);

        if (blank($separator = static::identifySingleSeparator($namespaceForCheck))) {
            return Str::of($namespace);
        }

        $backslash ??= fn ($namespace) => $namespace;
        $slash ??= fn ($namespace) => $namespace;

        return Str::of($separator === '\\' ? $backslash(namespace: $namespace) : $slash(namespace: $namespace));
    }

    /**
     * Identify if the namespace uses a single type of separator (either backslash or forward slash)
     *
     * @param string $namespace The namespace string
     * @return ?string Returns the separator if found, null otherwise
     */
    public static function identifySingleSeparator(string $namespace): ?string
    {
        return Str::of($namespace)->matchAllWithGroups(static::$singleSeparatorPattern)->get(0, [])['separator'] ?? null;
    }
}
