<?php

declare(strict_types=1);

namespace App\Placeholders\Namespace\Modifiers;

use App\Placeholders\Modifiers\SlugModifier as Modifier;
use App\Placeholders\Namespace\Modifiers\Concerns\InteractsWithNamespace;
use Illuminate\Support\Str;

class SlugModifier extends Modifier
{
    use InteractsWithNamespace;

    #[\Override]
    public function apply(string $value): string
    {
        return $this->unwrapNamespace(fn (string $segment): string => parent::apply(Str::kebab($segment)))($value);
    }
}
