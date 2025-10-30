<?php

declare(strict_types=1);

namespace App\Replacer;

class AuthorEmailReplacer
{
    use Concerns\InteractsWithReplacer;

    protected static string|array $placeholder = 'email';
}
