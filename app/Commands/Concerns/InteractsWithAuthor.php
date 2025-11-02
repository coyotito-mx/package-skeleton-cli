<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use App\Replacer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

trait InteractsWithAuthor
{
    public function bootInteractsWithAuthor(): void
    {
        $this->addReplacers([
            Replacer\AuthorReplacer::class => fn (): string => $this->getPackageAuthorName(),
        ]);

        $this->addOption('author', mode: InputOption::VALUE_OPTIONAL, description: 'The author of the package');
    }

    public function getPackageAuthorName(): string
    {
        return Str::of($this->option('author') ?? $this->getPackageVendor())
            ->snake(' ')
            ->title()
            ->toString();
    }
}
