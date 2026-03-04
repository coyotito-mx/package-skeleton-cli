<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use App\Dependencies\ComposerDependency;
use App\Dependencies\PestDependency;
use App\Dependencies\PHPUnitDependency;
use App\Facades\Composer;
use InvalidArgumentException;

trait InteractsWithTestingFramework
{
    const PEST_DEPENDENCY = 'Pest';

    const PHPUNIT_DEPENDENCY = 'PHPUnit';

    /**
     * Available testing frameworks
     *
     * @var string[]
     */
    protected array $availableTestingFrameworks = [
        self::PEST_DEPENDENCY => PestDependency::class,
        self::PHPUNIT_DEPENDENCY => PHPUnitDependency::class,
    ];

    /**
     * Resolve the selected testing framework dependency.
     *
     * @throws InvalidArgumentException If invalid testing framework provided.
     */
    protected function resolveTestingFramework(string $framework): ComposerDependency
    {
        if (blank($this->availableTestingFrameworks[$framework] ?? null)) {
            throw new InvalidArgumentException('Invalid testing framework selected [' . $framework . ']');
        }

        $framework = $this->availableTestingFrameworks[$framework];

        /** @var ComposerDependency $dependency */
        $dependency = new $framework(
            Composer::setPath($this->getPath())
        );

        return $dependency;
    }
}
