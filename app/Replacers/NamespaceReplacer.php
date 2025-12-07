<?php

namespace App\Replacers;

use _PHPStan_6597ef616\Nette\IOException;
use App\Replacer;
use App\Replacers\Exceptions\InvalidNamespace;
use Closure;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class NamespaceReplacer extends Builder
{
    protected static string $placeholder = 'namespace';

    public function __construct(string $replacement)
    {
        InvalidNamespace::validate($replacement);

        parent::__construct($replacement);
    }

    protected function configure(Replacer $replacer): void
    {
        parent::configure($replacer);

        tap($replacer)->normalizeReplacementUsing(fn (Stringable $replacement) =>
            static::unwrapNamespace(fn (Stringable $replacement) => $replacement->trim()->headline())($replacement)
        )->transformBeforeReplaceUsing(fn (Stringable $replacement) => $replacement->replace(' ', ''));
    }

    protected function modifiers(): array
    {
        return [
            'upper'   => static::unwrapNamespace(fn (Stringable $replacement) => $replacement->upper()),
            'lower'   => static::unwrapNamespace(fn (Stringable $replacement) => $replacement->lower()),
            'title'   => static::unwrapNamespace(fn (Stringable $replacement) => $replacement->title()),
            'snake'   => static::unwrapNamespace(fn (Stringable $replacement) => $replacement->snake()),
            'kebab'   => static::unwrapNamespace(fn (Stringable $replacement) => $replacement->kebab()),
            'slug'    => static::unwrapNamespace(fn (Stringable $replacement) => $replacement->slug()),
            'camel'   => static::unwrapNamespace(fn (Stringable $replacement) => $replacement->camel()),
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
}
