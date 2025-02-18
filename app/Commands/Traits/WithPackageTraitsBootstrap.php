<?php

declare(strict_types=1);

namespace App\Commands\Traits;

use Illuminate\Support\Arr;

trait WithPackageTraitsBootstrap
{
    protected function bootPackageTraits(): void
    {
        $default = [];
        $beginning = [];
        $middle = [];
        $end = [];

        $classes = collect(class_uses_recursive($this));

        tap(
            $classes,
            function ($traits) use (&$default, &$beginning, &$middle, &$end) {
                $traits->each(function (string $trait) use (&$default, &$beginning, &$middle, &$end): void {
                    try {
                        $reflected = new \ReflectionMethod($trait, 'bootPackage'.class_basename($trait));
                    } catch (\ReflectionException) {
                        return;
                    }

                    $attributes = $reflected->getAttributes(Attributes\Order::class);

                    if (empty($attributes)) {
                        $default[] = $trait;

                        return;
                    }

                    $attribute = $attributes[0]->newInstance();

                    if ($attribute->getOrder() === -1) {
                        $default[] = $trait;

                        return;
                    }

                    if ($attribute->getOrder() === Attributes\Order::FIRST) {
                        $beginning[] = $trait;

                        return;
                    }

                    if ($attribute->getOrder() === Attributes\Order::LAST) {
                        $end[] = $trait;

                        return;
                    }

                    if (isset($middle[$attribute->getOrder()])) {
                        $middle[$attribute->getOrder()][] = $trait;

                        return;
                    }

                    $middle[] = $trait;
                });
            }
        );

        collect([
            ...$beginning,
            ...Arr::flatten($middle),
            ...$default,
            ...$end,
        ])->each(fn (string $trait) => $this->bootPackageTraitUsing()($trait));
    }

    protected function bootPackageTraitUsing(?\Closure $using = null): \Closure
    {
        return $using instanceof \Closure ? $using : function (string $trait) {
            $this->{'bootPackage'.class_basename($trait)}();
        };
    }
}
