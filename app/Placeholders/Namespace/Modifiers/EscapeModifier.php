<?php

declare(strict_types=1);

namespace App\Placeholders\Namespace\Modifiers;

use App\Placeholders\Modifiers\UpperModifier as Modifier;
use App\Placeholders\Namespace\Modifiers\Concerns\InteractsWithNamespace;
use Illuminate\Support\Str;

class EscapeModifier extends Modifier
{
    use InteractsWithNamespace;
    
    public function apply(string $value): string
    {
        return $this->handleNamespaceSeparator(
            $value,
            fn (string $namespace): string => Str::replace('\\', '\\\\', $namespace),
        );
    }
}
