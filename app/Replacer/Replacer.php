<?php

declare(strict_types=1);

namespace App\Replacer;

use Closure;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Replacer implements Contracts\Replacer
{
    protected array $modifiers = [];

    public function __construct(
        protected string $placeholder,
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

    public function getModifier(string $modifier): Closure
    {
        $error = "Modifier [$modifier] not found.";

        return $this->getModifiers()[$modifier] ?? throw new InvalidArgumentException($error);
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

            if (isset($match[2])) {
                $modifiers = explode(',', $match[2]);

                foreach ($modifiers as $modifier) {
                    $replacement = $this->getModifier($modifier)($replacement);
                }
            }

            $text = Str::of($text)->replace($match[0], $replacement)->toString();
        }

        return $text;
    }

    protected function wrapPlaceholder(string $placeholder): string
    {
        return Str::of(preg_quote($placeholder))->wrap("/{$this->openTag}(", ")(?:\|([\w,]+))?{$this->closeTag}/")->toString();
    }
}
