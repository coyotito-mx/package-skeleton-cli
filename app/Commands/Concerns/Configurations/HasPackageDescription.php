<?php

declare(strict_types=1);

namespace App\Commands\Concerns\Configurations;

use App\Placeholders\DescriptionPlaceholder;
use Symfony\Component\Console\Input\InputArgument;

trait HasPackageDescription
{
    protected function bootPackageDescription(): void
    {
        $this->addCommandArgument('description', InputArgument::REQUIRED, 'The package description');

        $this->addPlaceholder(DescriptionPlaceholder::class, fn (): string => $this->getPackageDescription() ?? 'A short description of the package');
    }

    /**
     * Get the package description.
     */
    private function getPackageDescription(): ?string
    {
        return $this->argument('description');
    }
}
