<?php

declare(strict_types=1);

namespace App\Replacer;

class AuthorReplacer
{
    use Concerns\InteractsWithReplacer;

    protected static string|array $placeholder = 'author';
}
