<?php

declare(strict_types=1);

namespace App\Commands\Traits;

use App\Commands\Traits\Attributes\Enums\Order as OrderEnum;
use App\Commands\Traits\Attributes\Order;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Component\Console\Input\InputOption;

trait InteractsWithTestingDependency
{
    private const string DEFAULT_TESTING_DEPENDENCY = 'pest';

    protected array $testingDependencies = [
        'pest' => ['pestphp/pest' => '^4.1.2'],
        'phpunit' => ['phpunit/phpunit' => '^12.4.1'],
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

    protected function getTestingDependency(): array
    {
        if ($dep = $this->testingDependencyAlreadyInstalled()) {
            $this->info("Testing dependency '$dep' is already installed.");

            return [];
        }

        $dep = Str::lower($this->option('testing-dependency'));

        return match ($dep) {
            'pest' => $this->getPest(),
            'phpunit' => $this->getPHPUnit(),
            default => $this->getDefault(),
        };
    }

    protected function getPest(): array
    {
        return $this->testingDependencies['pest'];
    }

    protected function getPHPUnit(): array
    {
        return $this->testingDependencies['phpunit'];
    }

    protected function getDefault(): array
    {
        return $this->testingDependencies[self::DEFAULT_TESTING_DEPENDENCY];
    }

    protected function testingDependencyAlreadyInstalled(): ?string
    {
        foreach ($this->testingDependencies as $dep) {
            if ($this->composer()->hasPackage($dep)) {
                return $dep;
            }
        }

        return null;
    }
}
