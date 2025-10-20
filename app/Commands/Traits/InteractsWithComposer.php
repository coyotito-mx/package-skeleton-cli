<?php

declare(strict_types=1);

namespace App\Commands\Traits;

use App\Composer;

trait InteractsWithComposer
{
    protected ?Composer $composer = null;

    public function composer(): Composer
    {
        if ($this->composer === null) {
            $this->composer = app('composer');

            $this->composer->setWorkingPath(
                $this->getPackagePath()
            );
        }

        return $this->composer;
    }

}
