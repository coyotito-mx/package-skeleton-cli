<?php

namespace App\Commands\Traits;

use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

trait InteractsWithAuthor
{
    public function bootPackageInteractsWithAuthor(): void
    {
        $this->addOption('author', mode: InputOption::VALUE_OPTIONAL, description: 'The author of the package');
    }

    public function getPackageAuthorName(): string
    {
        return Str::title($this->option('author') ?? $this->getPackageVendor());
    }
}
