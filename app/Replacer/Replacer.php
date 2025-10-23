<?php

declare(strict_types=1);

namespace App\Replacer;

use Closure;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Replacer implements Contracts\Replacer
{
    protected array $modifiers = [];

    /**
     * Replacer class constructor
     *
     * The open and close tags will wrap the placeholder(s) to avoid matching only the string from the
     * placeholder(s)
     *
     * @param  string  $placeholder  The placeholder(s) to search for
     * @param  string  $replacement  The value from which the placeholder will be replaced
     * @param  string  $openTag  The opening tag
     * @param  string  $closeTag  The closing tag
     */
    public function __construct(
        protected string|array $placeholder,
        protected string $replacement,
        protected string $openTag = '{{',
        protected string $closeTag = '}}',
    ) {
        //
    }

    public function getPlaceholder(): string|array
    {
        return $this->placeholder;
    }

    public function getReplacement(): string
    {
        return $this->replacement;
    }

    public function modifierUsing(string|array $modifier, ?\Closure $callback = null): static
    {
        if (is_array($modifier)) {
            foreach ($modifier as $mf => $cb) {
                $this->modifierUsing($mf, $cb);
            }

            return $this;
        }

        if (! $callback instanceof \Closure) {
            throw new InvalidArgumentException('You must provide a valid callback.');
        }

        $this->modifiers[$modifier] = $callback;

        return $this;
    }

    public function getModifier(string $name): Closure
    {
        $error = "Modifier [$name] not found.";

        return $this->getModifiers()[$name] ?? throw new InvalidArgumentException($error);
    }

    public function getModifiers(): array
    {
        return [...static::getDefaultModifiers(), ...$this->modifiers];
    }

    public static function getDefaultModifiers(): array
    {
        return [
            'upper' => fn (string $replacement) => Str::upper($replacement),
            'lower' => fn (string $replacement) => Str::lower($replacement),
            'ucfirst' => fn (string $replacement) => Str::ucfirst($replacement),
            'title' => fn (string $replacement) => Str::title($replacement),
            'studly' => fn (string $replacement) => Str::studly($replacement),
            'camel' => fn (string $replacement) => Str::camel($replacement),
            'slug' => fn (string $replacement) => Str::slug($replacement),
            'snake' => fn (string $replacement) => Str::snake($replacement),
            'kebab' => fn (string $replacement) => Str::kebab($replacement),
            'plural' => fn (string $replacement) => Str::plural($replacement),
            'reverse' => fn (string $replacement) => Str::reverse($replacement),
        ];
    }

    public function replace(string $text): string
    {
        $placeholder = $this->wrapPlaceholder($this->getPlaceholder());

        $matches = [];
        preg_match_all($placeholder, $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $replacement = $this->getReplacement();

            if (isset($match[1])) {
                $modifiers = explode(',', $match[1]);

                foreach ($modifiers as $modifier) {
                    $replacement = $this->getModifier($modifier)($replacement);
                }
            }

            $text = Str::of($text)->replace($match[0], $replacement)->toString();
        }

        return $text;
    }

    protected function wrapPlaceholder(string|array $placeholder): string
    {
        // Wrap placeholder if not is an array
        $placeholders = is_null($placeholder) ? [] : (is_array($placeholder) ? $placeholder : [$placeholder]);

        $placeholders = array_map(fn (string $placeholder) => preg_quote($placeholder), $placeholders);
        $placeholders = implode('|', $placeholders);

        return Str::of($placeholders)->wrap("/{$this->openTag}(?:", ")(?:\|([\w,]+))?{$this->closeTag}/")->toString();
    }
}
