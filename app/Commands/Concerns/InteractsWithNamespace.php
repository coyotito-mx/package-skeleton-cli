<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use App\Replacer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

trait InteractsWithNamespace
{
    #[Attributes\Order(Attributes\Enums\Order::FIRST)]
    public function bootInteractsWithNamespace(): void
    {
        $this->addReplacers([
            Replacer\VendorReplacer::class => fn (): string => $this->getPackageVendor(),
            Replacer\PackageReplacer::class => fn (): string => $this->getPackageName(),
            Replacer\NamespaceReplacer::class => fn (): string => $this->getPackageNamespace(),
        ]);

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
