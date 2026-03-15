<?php

declare(strict_types=1);

namespace App;

abstract class Replacer
{
    /**
     * The pattern expression to search for
     */
    abstract protected function getPattern(): string;

    /**
     * Replace the content with the expression
     */
    abstract public function replace(string $content): string;
}
