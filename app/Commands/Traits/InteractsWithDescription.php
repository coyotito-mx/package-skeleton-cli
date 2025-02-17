<?php

declare(strict_types=1);

namespace App\Commands\Traits;

use Illuminate\Support\Str;

trait InteractsWithDescription
{
    public function bootPackageInteractsWithDescription(): void
    {
        $this->addPromptRequiredArgument('description', 'The description of the package', 'What is the package description?');
    }

    public function getPackageDescription(): string
    {
        return Str::lower($this->argument('description'));
    }
}
