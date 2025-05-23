<?php

declare(strict_types=1);

namespace App\Commands\Traits\Attributes\Enums;

enum Order: int
{
    case FIRST = 1;
    case LAST = -1;
    case DEFAULT = 0;
}
