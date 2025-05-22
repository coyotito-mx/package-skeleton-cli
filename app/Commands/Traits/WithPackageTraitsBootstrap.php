<?php

declare(strict_types=1);

namespace App\Commands\Traits;

use Illuminate\Support\Arr;

trait WithPackageTraitsBootstrap
{
    protected function bootPackageTraits(): void
    {
        // Prepare arrays to hold traits by their boot order
        $beginning = [];
        $middle = [];
        $default = [];
        $end = [];

        foreach (class_uses_recursive($this) as $trait) {
            $method = 'bootPackage' . class_basename($trait);

            try {
                $reflected = new \ReflectionMethod($trait, $method);
            } catch (\ReflectionException) {
                continue;
            }

            $attribute = $reflected->getAttributes(Attributes\Order::class)[0] ?? null;

            if (! $attribute) {
                $default[] = $trait;
                continue;
            }

            $order = $attribute->newInstance()->getOrder();

            switch ($order) {
                case Attributes\Enums\Order::DEFAULT:
                    $default[] = $trait;
                    break;
                case Attributes\Enums\Order::FIRST:
                    $beginning[] = $trait;
                    break;
                case Attributes\Enums\Order::LAST:
                    $end[] = $trait;
                    break;
                default:
                    $middle[$order][] = $trait;
                    break;
            }
        }

        // Boot traits in the order: beginning, middle, default, end
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
