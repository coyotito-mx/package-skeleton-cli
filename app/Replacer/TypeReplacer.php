<?php

declare(strict_types=1);

namespace App\Replacer;

class TypeReplacer
{
    use Concerns\InteractsWithReplacer;

    protected static string|array $placeholder = 'type';
}
