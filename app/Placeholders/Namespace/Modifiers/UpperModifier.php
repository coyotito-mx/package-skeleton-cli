<?php

declare(strict_types=1);

namespace App\Placeholders\Namespace\Modifiers;

use App\Placeholders\Modifiers\UpperModifier as Modifier;
use App\Placeholders\Namespace\Modifiers\Concerns\InteractsWithNamespace;

class UpperModifier extends Modifier
{
    use InteractsWithNamespace;
    
    public function apply(string $value): string
    {
        return $this->unwrapNamespace(fn (string $segment): string => parent::apply($segment))($value);
    }
}
