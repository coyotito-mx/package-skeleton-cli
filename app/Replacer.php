<?php

namespace App;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

final class Replacer
{
    /**
     * The list of custom modifiers.
     *
     * @var array <string, Closure>
     */
    protected array $modifiers = [];

    protected array $excludeModifiers = [];

    /**
     * The opening pattern for placeholders.
     */
    protected static string $openPattern = '{{(?:';

    /**
     * The closing pattern for placeholders.
     */
    protected static string $closePattern = ')(?:\|(?<modifiers>[^|,}\s]+(?:,[^|,}\s]+)*))?}}';

    /**
     * The placeholder normalizer closure.
     *
     * @var ?Closure(Stringable): Stringable
     */
    protected ?Closure $replacementNormalizer = null;

    protected ?Closure $transformBeforeReplaceUsing = null;

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

    /**
     * Constructor
     *
     * @param  string  $placeholder  The placeholder to be replaced
     * @param  string  $replacement  The replacement string
     */
    protected function __construct(public string $placeholder, public string $replacement)
    {
        //
    }

    /**
     * Create a new Replacer instance.
     *
     * @param  string  $placeholder  The placeholder to be replaced
     * @param  string  $replacement  The replacement string
     */
    public static function make(string $placeholder, string $replacement): self
    {
        return new self($placeholder, $replacement);
    }

    /**
     * Replace the placeholder in the given content with the replacement string,
     *
     * @param  string  $content  The content in which to perform the replacement
     */
    public function replace(string $content): string
    {
        $matchedPlaceholders = $this->matchPlaceholders($content);

        foreach ($matchedPlaceholders as $placeholder => $modifiers) {
            $replacement = $this->normalizedReplacement();

            foreach ($this->getModifiers($modifiers) as $modifier) {
                $replacement = $modifier(replacement: $replacement);
            }

            $replacement = $this->transformBeforeReplace($replacement);

            $content = str_replace($placeholder, (string) $replacement, $content);
        }

        return $content;
    }

    /**
     * Get the default modifiers.
     *
     * @return array<string, Closure>
     */
    protected function getDefaultModifiers(): array
    {
        return [
            'upper' => fn (Stringable $replacement) => $replacement->upper(),
            'lower' => fn (Stringable $replacement) => $replacement->lower(),
            'title' => fn (Stringable $replacement) => $replacement->title(),
            'snake' => fn (Stringable $replacement) => $replacement->snake(),
            'kebab' => fn (Stringable $replacement) => $replacement->kebab(),
            'camel' => fn (Stringable $replacement) => $replacement->camel(),
            'slug' => fn (Stringable $replacement) => $replacement->slug(),
            'acronym' => function (Stringable $replacement): Stringable {
                $acronym = $replacement
                    ->headline()
                    ->split('/\s/')
                    ->filter(function (string $str): bool {
                        return ! in_array(strtolower($str), self::$stopWords);
                    })
                    ->map(fn (string $acronym) => substr(ucfirst($acronym), 0, 1))
                    ->join('');

                return Str::of($acronym);
            },
        ];
    }

    /**
     * Get the available modifiers.
     *
     * @param  array  $modifiers  The modifiers to filter
     */
    public function getModifiers(array $modifiers = []): array
    {
        $defaultModifiers = [...$this->getDefaultModifiers(), ...$this->modifiers];

        return collect($modifiers)
            ->mapWithKeys(fn (string $modifier) => [$modifier => $defaultModifiers[$modifier] ?? null])
            ->filter(fn (?Closure $modifier, string $name) => $modifier !== null && ! in_array($name, $this->excludeModifiers))
            ->all();
    }

    /**
     * Add a custom modifier.
     *
     * @param  string  $name  The name of the modifier
     * @param  Closure  $closure  The closure that defines the modifier
     */
    public function addModifier(string $name, Closure $closure): self
    {
        $this->modifiers[$name] = $closure;

        return $this;
    }

    public function excludeModifiers(array $modifiers): self
    {
        $this->excludeModifiers = $modifiers;

        return $this;
    }

    /**
     * Normalize the replacement string.
     */
    public function normalizedReplacement(): Stringable
    {
        return (
            $this->replacementNormalizer ??
            fn (Stringable $replacement) => $replacement->headline()
        )(Str::of($this->replacement));
    }

    public function normalizeReplacementUsing(?Closure $closure = null): self
    {
        $this->replacementNormalizer = $closure;

        return $this;
    }

    public function matchPlaceholders(string $content): array
    {
        $placeholder = self::wrapPlaceholder($this->placeholder);

        return Str::matchAll($placeholder, $content)
            ->unique()
            ->mapWithKeys(function (string $modifiers) {
                $placeholder = (string) Str::of($this->placeholder)->prepend('{{')->append($modifiers ? '|'.$modifiers : '', '}}');
                $modifiersList = explode(',', $modifiers);

                return [$placeholder => $modifiersList];
            })->toArray();
    }

    protected function transformBeforeReplace(Stringable $replacement): string
    {
        return ($this->transformBeforeReplaceUsing ?? fn (Stringable $replacement) => $replacement)($replacement);
    }

    public function transformBeforeReplaceUsing(?Closure $closure = null): self
    {
        $this->transformBeforeReplaceUsing = $closure;

        return $this;
    }

    public static function wrapPlaceholder(string $placeholder): string
    {
        return (string) Str::of($placeholder)->wrap('/'.self::$openPattern, self::$closePattern.'/');
    }
}
