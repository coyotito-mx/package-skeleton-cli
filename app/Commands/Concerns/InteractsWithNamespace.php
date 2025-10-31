<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use App\Commands\Exceptions\InvalidFormatException;
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
                    return $this->getNamespaceComponents()[$key];
                } else {
                    return text($message);
                }
            };
        };

        $this
            ->addPromptRequiredArgument('vendor', 'Vendor name', $missing('What is the vendor name?', 'vendor'))
            ->addPromptRequiredArgument('package', 'Package name', $missing('What is the package name?', 'package'))
            ->addOption('namespace', null, InputOption::VALUE_REQUIRED, 'The namespace of the package');
    }

    public function getPackageVendor(): string
    {
        return Str::slug($this->argument('vendor'));
    }

    public function getPackageName(): string
    {
        return Str::slug($this->argument('package'));
    }

    public function getPackageNamespace(): string
    {
        if ($this->option('namespace')) {
            ['vendor' => $vendor, 'package' => $package] = $this->getNamespaceComponents();
        } else {
            $vendor = $this->getPackageVendor();
            $package = $this->getPackageName();
        }

        return sprintf("%s\\%s", Str::pascal($vendor), Str::pascal($package));
    }

    private function getNamespaceComponents(): array
    {
        $namespace = Str::lower($this->option('namespace'));

        preg_match('/^(?<vendor>[a-z0-9]+(?:-[a-z0-9]+)*)\/(?<package>[a-z0-9]+(?:-[a-z0-9]+)*)$/', $namespace, $matches);

        if (empty($matches)) {
            throw new InvalidFormatException($namespace);
        }

        return [
            'vendor' => $matches['vendor'],
            'package' => $matches['package'],
        ];
    }
}
