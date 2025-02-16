<?php

namespace App\Commands\Traits;

use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

trait InteractsWithNamespace
{
    public function bootPackageInteractsWithNamespace(): void
    {
        $this
            ->addPromptRequiredArgument('vendor', 'Vendor name', 'What is the vendor name?')
            ->addPromptRequiredArgument('package', 'Package name', 'What is the package name?')
            ->addOption('namespace', null, InputOption::VALUE_OPTIONAL, 'The namespace of the package');
    }

    public function getPackageVendor(): string
    {
        return Str::lower($this->argument('vendor'));
    }

    public function getPackageName(): string
    {
        return Str::lower($this->argument('package'));
    }

    public function getPackageNamespace(): string
    {
        return Str::title($this->option('namespace') ?? "{$this->getPackageVendor()}\\{$this->getPackageName()}");
    }
}
