<?php

declare(strict_types=1);

namespace App\Commands\Concerns\Configurations;

use App\Placeholders\LicensePlaceholder;

trait HasLicense
{
    protected function bootLicense(): void
    {
        $this->addPlaceholder(LicensePlaceholder::class, 'MIT');
    }
}
