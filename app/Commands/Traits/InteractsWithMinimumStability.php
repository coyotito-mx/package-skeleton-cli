<?php

declare(strict_types=1);

namespace App\Commands\Traits;

use App\Replacer;
use Symfony\Component\Console\Input\InputOption;

trait InteractsWithMinimumStability
{
    protected array $minimumStabilityAvailable = [
        'stable',
        'rc' => 'RC',
        'beta',
        'alpha',
        'dev',
    ];

    public function bootPackageInteractsWithMinimumStability(): void
    {
        $this->addReplacers([
            Replacer\MinimumStabilityReplacer::class => fn (): string => $this->getPackageMinimumStability(),
        ]);

        $this->addOption('minimum-stability', mode: InputOption::VALUE_OPTIONAL, description: 'The minimum stability allowed for the package', default: 'dev');
    }

    public function getPackageMinimumStability(): string
    {
        /** @var ?string $minimumStability */
        $minimumStability = collect($this->minimumStabilityAvailable)
            ->mapWithKeys(fn (string $value, mixed $key) => [
                (is_int($key) ? $value : "$key") => $value,
            ])->first(fn (string $value) => $value === $this->option('minimum-stability'));

        if (is_null($minimumStability)) {
            throw new \RuntimeException('Invalid minimum stability.');
        }

        return $minimumStability;
    }
}
