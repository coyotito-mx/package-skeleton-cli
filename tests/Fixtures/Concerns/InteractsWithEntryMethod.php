<?php

namespace Tests\Fixtures\Concerns;

use Closure;

trait InteractsWithEntryMethod
{
    public ?Closure $usingEntry = null;

    public function hasEntry(): bool
    {
        return $this->usingEntry instanceof Closure;
    }

    public function entry(): int
    {
        if (! $this->hasEntry()) {
            return $this->__handle();
        }

        $entry = $this->usingEntry;

        return $entry();
    }

    public function setEntryUsing(Closure $callable): void
    {
        $this->usingEntry = Closure::bind($callable, $this, get_class($this));
    }
}
