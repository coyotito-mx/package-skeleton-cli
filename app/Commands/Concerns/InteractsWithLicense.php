<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use App\Replacer;
use Symfony\Component\Console\Input\InputOption;

trait InteractsWithLicense
{
    public function bootPackageInteractsWithLicense(): void
    {
        $this->addReplacers([
            Replacer\LicenseReplacer::class => fn (): string => $this->getPackageLicense(),
        ]);

        $this->addOption('license', mode: InputOption::VALUE_OPTIONAL, description: 'License of the package', default: 'MIT');
    }

    public function getPackageLicense(): string
    {
        return 'MIT';
    }
}
