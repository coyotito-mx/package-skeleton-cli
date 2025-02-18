<?php

declare(strict_types=1);

namespace App\Commands\Traits\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Order
{
    public const string FIRST = 'first';

    public const string LAST = 'last';

    public function __construct(
        protected int|string $order,
        protected ?string $key = null,
        protected ?string $before = null,
        protected ?string $after = null
    ) {
        if (is_int($this->order) && $this->order < 1) {
            $this->order = -1;
        }
    }

    public function getOrder(): int|string
    {
        return $this->order;
    }

    public function shouldBeFirst(): bool
    {
        return $this->order === self::FIRST;
    }

    public function shouldBeLast(): bool
    {
        return $this->order === self::LAST;
    }

    public function shouldBeBefore(): string
    {
        return $this->before;
    }

    public function shouldBeAfter(): string
    {
        return $this->after;
    }
}
