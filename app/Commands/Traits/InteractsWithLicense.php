<?php

declare(strict_types=1);

namespace App\Commands\Traits;

use App\Facades\Composer;
use App\Replacer;
use App\Traits\Exceptions\LicenseDefinitionNotFound;
use Symfony\Component\Console\Input\InputOption;

trait InteractsWithLicense
{
    public function bootPackageInteractsWIthLicense(): void
    {
        $this->addReplacers([
            Replacer\LicenseReplacer::class => fn (): string => $this->getPackageLicense(),
        ]);

        $this->addOption('license', mode: InputOption::VALUE_OPTIONAL, description: 'License of the package', default: 'MIT');
    }

    /**
     * @throw LicenseDefinitionNotFound
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
