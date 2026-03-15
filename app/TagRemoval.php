<?php

declare(strict_types=1);

namespace App;

use Illuminate\Support\Str;

/**
 * PlaceholderB Builder class
 */
class TagRemoval extends Replacer
{
    /**
     * Get the placeholder pattern
     */
    protected function getPattern(): string
    {
        return '/<remove>(?:[\s\S]*?)<\/remove>\s*/';
    }

    /**
     * Replace the placeholders in the give $content
     */
    public function replace(string $content): string
    {
        return Str::replaceMatches($this->getPattern(), '', $content);
    }
}
