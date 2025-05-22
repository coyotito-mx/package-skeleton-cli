<?php

declare(strict_types=1);

namespace App\Commands\Traits\Attributes\Enums;

enum Order: int
{
    case FIRST = 1;
    case LAST = -1;
    case DEFAULT = 0;

    public function getOrder(): int
    {
        return match ($this) {
            self::FIRST => 1,
            self::LAST => -1,
            default => 0,
        };
    }
}
