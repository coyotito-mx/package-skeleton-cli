<?php

declare(strict_types=1);

namespace App\Commands\Traits;

use App\Commands\Traits\Attributes\Order;
use App\Commands\Traits\Attributes\Enums\Order as OrderEnum;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\NoReturn;
use RuntimeException;
use Symfony\Component\Console\Input\InputOption;

trait InteractsWithTestingDependency
{
    protected array $testingDependencies = [
        'pest' => 'pestphp/pest',
        'phpunit' => 'phpunit/phpunit'
    ];

    #[NoReturn]
    #[Order(OrderEnum::LAST)]
    public function bootPackageInteractsWithTestingDependency(): void
    {
        $this->addOption(
            name: 'testing-dependency',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'The testing dependency to use (pest, phpunit)',
            default: 'pest'
        );
    }

    protected function installTestingDependency(): void
    {
        if ($dep = $this->testingDependencyAlreadyInstalled()) {
            $this->info("Testing dependency '$dep' is already ");

            return;
        }

        $dep = Str::lower($this->option('testing-dependency'));

        match ($dep) {
            'pest' => $this->installPest(),
            'phpunit' => $this->installPHPUnit(),
            default => $this->warn('Invalid testing dependency specified.'),
        };
    }

    protected function installPest(): void
    {
        $package = $this->testingDependencies['pest'];

        $this->composer()->requirePackages(["$package", '-W'], true, $this->getOutput());
    }

    protected function installPHPUnit(): void
    {
        $package = $this->testingDependencies['phpunit'];

        $this->composer()->requirePackages([$package], true, $this->getOutput());
    }

    protected function testingDependencyAlreadyInstalled(): ?string
    {
        foreach ($this->testingDependencies as $dep => $values) {
            if ($this->composer()->hasPackage($dep)) {
                return $dep;
            }
        }

        return null;
    }
}
