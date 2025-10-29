<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

trait InteractsWithCurrentYear
{
    public function getPackageLicenseDescription(): string
    {
        return file_get_contents(base_path('stubs/license.md.stub'));
    }
}
