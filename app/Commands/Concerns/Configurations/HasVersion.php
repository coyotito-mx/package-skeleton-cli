<?php

declare(strict_types=1);

namespace App\Commands\Concerns\Configurations;

use App\Replacers\VersionReplacer;

trait HasVersion
{
    protected function bootVersion(): void
    {
        $this->addReplacer(VersionReplacer::class, '0.0.1');
    }
}
