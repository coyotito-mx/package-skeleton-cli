<?php

declare(strict_types=1);

namespace App\Commands\Concerns\Configurations;

use App\Replacers\LicenseNameReplacer;

trait HasLicense
{
    protected function bootLicense(): void
    {
        $this->addReplacer(LicenseNameReplacer::class, 'MIT');
    }
}
