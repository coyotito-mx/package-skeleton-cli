<?php

declare(strict_types=1);

namespace App\Commands\Concerns\Configurations;

use App\Replacers\DescriptionReplacer;
use Symfony\Component\Console\Input\InputArgument;

trait HasPackageDescription
{
    protected function bootPackageDescription(): void
    {
        $this->addCommandArgument('description', InputArgument::OPTIONAL, 'The package description');

        $this->addReplacer(DescriptionReplacer::class, fn (): string => $this->getPackageDescription() ?? 'A short description of the package');
    }

    /**
     * Get the package description, or null if not provided.
     *
     * @phpstan-ignore-next-line
     */
    private function getPackageDescription(): ?string
    {
        return $this->argument('description');
    }
}
