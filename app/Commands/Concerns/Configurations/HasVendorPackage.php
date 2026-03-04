<?php

declare(strict_types=1);

namespace App\Commands\Concerns\Configurations;

use App\Replacers\PackageReplacer;
use App\Replacers\VendorReplacer;
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
            ->addReplacer(VendorReplacer::class, fn () => $this->getVendor())
            ->addReplacer(PackageReplacer::class, fn () => $this->getPackage());
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
