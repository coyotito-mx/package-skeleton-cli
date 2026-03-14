<?php

declare(strict_types=1);

namespace App\Placeholders\Modifiers;

use App\Placeholders\Modifiers\Concerns\HasName;
use Illuminate\Support\Str;

class AcronymModifier implements Contracts\ModifierContract
{
    use HasName;

    /**
     * Non-essential words to ignore when generating acronyms.
     *
     * @var string[]
     */
    protected static array $stopWords = [
        // Conjunctions
        'and',
        'or',
        'nor',
        'but',
        'yet',
        'so',

        // Articles
        'a',
        'an',
        'the',

        // Prepositions (common, short)
        'about',
        'above',
        'across',
        'after',
        'against',
        'along',
        'amid',
        'among',
        'around',
        'as',
        'at',
        'before',
        'behind',
        'below',
        'beneath',
        'beside',
        'besides',
        'between',
        'beyond',
        'by',
        'despite',
        'down',
        'during',
        'except',
        'for',
        'from',
        'in',
        'inside',
        'into',
        'like',
        'near',
        'of',
        'off',
        'on',
        'onto',
        'out',
        'outside',
        'over',
        'past',
        'per',
        'plus',
        'round',
        'since',
        'than',
        'through',
        'throughout',
        'till',
        'to',
        'toward',
        'towards',
        'under',
        'underneath',
        'unlike',
        'until',
        'up',
        'upon',
        'via',
        'with',
        'within',
        'without',
    ];

    public function apply(string $value): string
    {
        return Str::of($value)
            ->headline()
            ->split('/\s/')
            ->filter(fn (string $str): bool => ! in_array(strtolower($str), self::$stopWords))
            ->map(fn (string $acronym) => Str::substr(Str::ucfirst($acronym), 0, 1))
            ->join('');
    }
}
