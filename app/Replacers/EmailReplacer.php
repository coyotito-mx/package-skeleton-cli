<?php

declare(strict_types=1);

namespace App\Replacers;

use App\Replacer;
use App\Replacers\Exceptions\InvalidEmailException;
use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

/**
 * Replacer for `email` placeholders
 *
 * @see \App\Replacer for supported modifiers.
 */
class EmailReplacer extends Builder
{
    protected static string $placeholder = 'email';

    protected static ?string $invalidFormatException = InvalidEmailException::class;

    protected function configure(): Replacer
    {
        $this->replacer
            ->normalizeReplacementUsing(fn (Stringable $replacement) => $replacement->lower())
            ->only(['upper']);

        return parent::configure();
    }

    public function modifiers(): array
    {
        return [
            'upper' => self::unwrapEmail(fn (Stringable $part) => $part->upper()),
        ];
    }

    protected static function unwrapEmail(Closure $both): Closure
    {
        return function (Stringable $replacement) use ($both): Stringable {
            [$localPart, $domain] = $replacement->explode('@')->map(fn ($part) => Str::of($part)->trim())->all();

            return Str::of('@')->trim()->wrap($both($localPart), $both($domain));
        };
    }
}
