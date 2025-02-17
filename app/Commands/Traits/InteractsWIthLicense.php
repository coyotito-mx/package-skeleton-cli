<?php

declare(strict_types=1);

namespace App\Commands\Traits;

use App\Facades\Composer;
use App\Traits\Exceptions\LicenseDefinitionNotFound;
use Symfony\Component\Console\Input\InputOption;

trait InteractsWIthLicense
{
    public function bootPackageInteractsWIthLicense(): void
    {
        $this->addOption('license', mode: InputOption::VALUE_OPTIONAL, description: 'License of the package', default: 'MIT');
    }

    /**
     * @throw LicenseDefinitionNotFound if the license is not found
     */
    public function getPackageLicense(): string
    {
        $license = $this->option('license');

        if (! Composer::validateLicense($license)) {
            throw new LicenseDefinitionNotFound($license);
        }

        return $license;
    }
}
