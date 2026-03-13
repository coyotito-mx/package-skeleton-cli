<?php

declare(strict_types=1);

namespace App\Commands\Concerns\Configurations;

use App\Placeholders\Version\VersionPlaceholder;

trait HasVersion
{
    protected function bootVersion(): void
    {
        $this->addPlaceholder(VersionPlaceholder::class, '0.0.1');
    }
}
