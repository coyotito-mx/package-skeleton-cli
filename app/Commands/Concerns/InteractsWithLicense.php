<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use App\Replacer;

trait InteractsWithLicense
{
    use InteractsWithLicenseDescription;

    public function bootPackageInteractsWithLicense(): void
    {
        $this->addReplacers([
            Replacer\LicenseReplacer::class => fn (): string => $this->getPackageLicense(),
        ]);
    }

    public function getPackageLicense(): string
    {
        return 'MIT';
    }
}
