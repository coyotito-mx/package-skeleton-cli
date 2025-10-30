<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use App\Replacer\Concerns\InteractsWithReplacer;
use Illuminate\Support\Carbon;

trait InteractsWithCurrentYear
{
    public function bootInteractsWithCurrentYear(): void
    {
        $obj = new class
        {
            use InteractsWithReplacer;

            protected static string $placeholder = 'year';
        };

        $this->addReplacers([
            $obj::class => (string) Carbon::now()->year,
        ]);
    }
}
