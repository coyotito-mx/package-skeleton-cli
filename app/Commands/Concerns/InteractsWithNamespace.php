<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use App\Replacer;
use Closure;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\text;

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

        $missing = function (string $message, string $key): string|Closure {
            return function () use ($message, $key) {
                if ($this->option('namespace')) {
                    $matches = [];
                    $namespace = $this->getPackageNamespace();

                    preg_match('/^(?<vendor>\S+)\/(?<package>\S+)$/', $namespace, $matches);

                    if (empty($matches)) {
                        throw new RuntimeException("The provided namespace [$namespace] does not match <vendor>/<package> format");
                    }

                    return $matches[$key];
                } else {
                    return text($message);
                }
            };
        };

        $this
            ->addPromptRequiredArgument('vendor', 'Vendor name', $missing('What is the vendor name?', 'vendor'))
            ->addPromptRequiredArgument('package', 'Package name', $missing('What is the package name?', 'package'))
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
