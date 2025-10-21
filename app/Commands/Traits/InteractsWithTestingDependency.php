<?php

declare(strict_types=1);

namespace App\Commands\Traits;

use App\Commands\Traits\Attributes\Enums\Order as OrderEnum;
use App\Commands\Traits\Attributes\Order;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

trait InteractsWithTestingDependency
{
    private const string DEFAULT_TESTING_DEPENDENCY = 'pest';

    protected array $testingDependencies = [
        'pest' => [
            'name' => 'pestphp/pest',
            'version' => 'pestphp/pest',
        ],
        'phpunit' => [
            'name' => 'phpunit/phpunit',
            'version' => '^12.4.1'
        ],
    ];

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

    /**
     * Get the dependency based on the option `testing-dependency` value
     *
     * @return ?array{name: string, version: string}
     */
    protected function getTestingDependency(): ?array
    {
        if ($this->testingDependencyAlreadyInstalled()) {
            $this->info("Testing dependency is already installed.");

            return null;
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

    protected function testingDependencyAlreadyInstalled(): bool
    {
        foreach ($this->testingDependencies as $_ => $config) {
            if ($this->composer()->hasPackage($config['name'])) {
                return true;
            }
        }

        return false;
    }
}
