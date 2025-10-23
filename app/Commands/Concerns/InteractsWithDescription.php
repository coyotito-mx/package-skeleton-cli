<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use App\Replacer;
use Illuminate\Support\Str;

trait InteractsWithDescription
{
    #[Attributes\Order(10)]
    public function bootPackageInteractsWithDescription(): void
    {
        $this->addReplacers([
            Replacer\DescriptionReplacer::class => fn (): string => $this->getPackageDescription(),
        ]);

        $this->addPromptRequiredArgument('description', 'The description of the package', 'What is the package description?');
    }

    public function getPackageDescription(): string
    {
        return Str::lower($this->argument('description'));
    }
}
