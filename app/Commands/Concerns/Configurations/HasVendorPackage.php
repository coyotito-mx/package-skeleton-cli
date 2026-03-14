<?php

declare(strict_types=1);

namespace App\Commands\Concerns\Configurations;

use App\Placeholders\PackagePlaceholder;
use App\Placeholders\VendorPlaceholder;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

trait HasVendorPackage
{
    protected function bootVendorPackage(): void
    {
        $this->addCommandArguments([
            ['vendor', InputArgument::REQUIRED, 'The vendor name of the package.'],
            ['package', InputArgument::REQUIRED, 'The package name.'],
        ]);

        $this
            ->addPlaceholder(VendorPlaceholder::class, fn () => $this->getVendor())
            ->addPlaceholder(PackagePlaceholder::class, fn () => $this->getPackage());
    }

    /**
     * Get the package vendor name formatted in StudlyCase.
     */
    protected function getVendor(): string
    {
        return Str::studly($this->argument('vendor'));
    }

    /**
     * Get the package name formatted in StudlyCase.
     */
    protected function getPackage(): string
    {
        return Str::studly($this->argument('package'));
    }
}
