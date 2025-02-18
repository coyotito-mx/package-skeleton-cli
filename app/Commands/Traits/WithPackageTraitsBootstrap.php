<?php

declare(strict_types=1);

namespace App\Commands\Traits;

use Illuminate\Support\Arr;

trait WithPackageTraitsBootstrap
{
    protected function bootPackageTraits(): void
    {
        $default = $beginning = $middle = $end = [];

        $classes = collect(class_uses_recursive($this));

        $classes->each(function (string $trait) use (&$default, &$beginning, &$middle, &$end): void {
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

            $order = $attribute->getOrder();

            if ($order === -1) {
                $default[] = $trait;
            } elseif ($order === Attributes\Order::FIRST) {
                $beginning[] = $trait;
            } elseif ($order === Attributes\Order::LAST) {
                $end[] = $trait;
            } else {
                $middle[$order][] = $trait;
            }
        });

        collect([
            ...$beginning,
            ...Arr::flatten($middle),
            ...$default,
            ...$end,
        ])
            ->each(fn (string $trait) => $this->bootPackageTraitUsing()($trait));
    }

    protected function bootPackageTraitUsing(?\Closure $using = null): \Closure
    {
        return $using ?? fn (string $trait) => $this->{'bootPackage'.class_basename($trait)}();
    }
}
