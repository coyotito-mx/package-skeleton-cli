<?php

declare(strict_types=1);

namespace App\Replacer;

class AuthorReplacer
{
    use Traits\InteractsWithReplacer;

    protected string $placeholder = 'author';
}
