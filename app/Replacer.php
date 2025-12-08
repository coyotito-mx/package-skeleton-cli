<?php

declare(strict_types=1);

namespace App;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

/**
 * Replacer class to handle placeholder replacements with optional modifiers.
 *
 * Modifiers supported by default:
 * - upper: Converts the replacement string to uppercase.
 * - lower: Converts the replacement string to lowercase.
 * - title: Converts the replacement string to title case.
 * - snake: Converts the replacement string to snake_case.
 * - kebab: Converts the replacement string to kebab-case.
 * - camel: Converts the replacement string to camelCase.
 * - slug: Converts the replacement string to a URL-friendly slug.
 * - acronym: Converts the replacement string to an acronym in uppercase (e.g. "The United Mexican States" -> "UMS"), using common English stop words.
 *
 * > **Note**:
 * >
 * > Modifiers can be chained in using a comma as a separator, e.g. `{{placeholder|upper,slug}}`. Also,
 * > keep in mind, if you use the same modifier multiple times, the first one will take precedence, and the rest will be ignored.
 */
final class Replacer
{
    /**
     * The list of custom modifiers.
     *
     * @var array <string, Closure>
     */
    protected array $modifiers = [];

    /**
     * The list of modifiers to exclude.
     *
     * @var string[]
     */
    protected array $excludeModifiers = [];

    /**
     * The list of modifiers to only allow.
     *
     * @var string[]
     */
    protected ?array $onlyWith = [];

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
    protected function __construct(protected(set) string $placeholder, public string $replacement)
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
                /** @var Stringable $replacement */
                $replacement = $modifier(replacement: $replacement);
            }

            $replacement = $this->transformBeforeReplace($replacement);

            $content = str_replace($placeholder, $replacement, $content);
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
        $availableModifiers = collect($this->getDefaultModifiers())->merge($this->modifiers);

        if ($this->onlyWith === null) {
            $availableModifiers = collect();
        } elseif ($this->onlyWith !== []) {
            $allowed = array_flip($this->onlyWith);
            $availableModifiers = $availableModifiers->intersectByKeys($allowed);
        } elseif ($this->excludeModifiers !== []) {
            $excluded = array_flip($this->excludeModifiers);
            $availableModifiers = $availableModifiers->diffKeys($excluded);
        }

        $resolved = [];

        foreach ($modifiers as $name) {
            if (isset($availableModifiers[$name])) {
                $resolved[$name] = $availableModifiers[$name];
            }
        }

        return $resolved;
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

    /**
     * Exclude specific modifiers from being used.
     *
     * @param string[] $modifiers List of modifier names to exclude
     */
    public function excludeModifiers(array $modifiers): self
    {
        $this->excludeModifiers = [...$this->excludeModifiers, ...$modifiers];

        return $this;
    }

    /**
     * Filter modifiers to only allow specific ones.
     *
     * This will override any previously set excluded modifiers
     *
     * @param array|null $modifiers
     * @return $this
     */
    public function onlyWith(?array $modifiers = []): self
    {
        if (is_array($modifiers)) {
            $modifiers = [...$modifiers, ...($this->modifiers ?? [])];
        }

        $this->onlyWith = $modifiers;

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

    /**
     * Set the replacement normalizer closure.
     *
     * @param ?Closure(Stringable $replacement): Stringable $closure
     * @return $this
     */
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
        return ($this->transformBeforeReplaceUsing ?? fn (Stringable $replacement) => (string) $replacement)($replacement);
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
