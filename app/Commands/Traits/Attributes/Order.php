<?php

declare(strict_types=1);

namespace App\Commands\Traits\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Order
{
    public function __construct(protected int|Enums\Order $order = Enums\Order::DEFAULT)
    {
        if (is_int($this->order) && $this->order < 0) {
            $this->order = Enums\Order::DEFAULT;
        }
    }

    public function getOrder(): int|Enums\Order
    {
        return $this->order;
    }
}
