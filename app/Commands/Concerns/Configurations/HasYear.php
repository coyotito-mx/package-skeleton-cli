<?php

declare(strict_types=1);

namespace App\Commands\Concerns\Configurations;

use App\Replacers\YearReplacer;

trait HasYear
{
    protected function bootYear(): void
    {
        $this->addReplacer(YearReplacer::class, (string) now()->year);
    }
}
