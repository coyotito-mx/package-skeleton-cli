<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use App\Dependencies\ComposerDependency;
use Illuminate\Contracts\Container\BindingResolutionException;
use InvalidArgumentException;

trait InteractsWithTestingFramework
{
    const string PEST_DEPENDENCY = 'pest';

    const string PHPUNIT_DEPENDENCY = 'phpunit';

    /**
     * Available testing frameworks
     *
     * @var string[]
     */
    protected array $availableTestingFrameworks = [
        self::PEST_DEPENDENCY => 'Pest',
        self::PHPUNIT_DEPENDENCY => 'PHPUnit',
    ];

    /**
     * Get the testing framework dependency instance based on the provided framework name.
     *
     * @throws InvalidArgumentException If invalid testing framework selected.
     */
    protected function getTestingFrameworkDependency(string $framework): ComposerDependency
    {
        try {
            /** @var ComposerDependency $dependency */
            $dependency = app()->make($framework);

            return $dependency;
        } catch (BindingResolutionException) {
            throw new InvalidArgumentException("Invalid testing framework selected: {$framework}");
        }
    }
}
