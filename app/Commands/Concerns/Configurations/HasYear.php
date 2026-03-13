<?php

declare(strict_types=1);

namespace App\Commands\Concerns\Configurations;

use App\Placeholders\YearPlaceholder;

trait HasYear
{
    protected function bootYear(): void
    {
        $this->addPlaceholder(YearPlaceholder::class, (string) now()->year);
    }
}
