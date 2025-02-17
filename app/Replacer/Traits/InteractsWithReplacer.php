<?php

declare(strict_types=1);

namespace App\Replacer\Traits;

use App\Replacer\Replacer;

trait InteractsWithReplacer
{
    public static function make(string $replacement): \App\Replacer\Contracts\Replacer
    {
        $class = new static;

        return (new Replacer($class->getPlaceholder(), $replacement))->modifierUsing($class->getModifiers());
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    public function getModifiers(): array
    {
        return [];
    }
}
