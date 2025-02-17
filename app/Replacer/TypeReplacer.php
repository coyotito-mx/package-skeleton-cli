<?php

declare(strict_types=1);

namespace App\Replacer;

class TypeReplacer
{
    use Traits\InteractsWithReplacer;

    protected string $placeholder = 'type';
}
