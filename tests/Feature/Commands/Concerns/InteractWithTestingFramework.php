<?php

use App\Commands\Concerns\InteractsWithTestingFramework;
use App\Dependencies\ComposerDependency;
use App\Dependencies\PestDependency;
use App\Dependencies\PHPUnitDependency;

function callResolveTestingFramework(string $method, array $parameters = [])
{
    $trait = new class
    {
        use InteractsWithTestingFramework;

        public function callProtectedMethod(string $framework): ComposerDependency
        {
            return $this->resolveTestingFramework($framework);
        }

        protected function getPath(): string
        {
            return __DIR__;
        }
    };

    return $trait->callProtectedMethod($method);
}

it('resolves to Pest dependency', function (): void {
    $dependency = callResolveTestingFramework('Pest');

    expect($dependency)->toBeInstanceOf(PestDependency::class);
});

it('resolves to PHPUnit dependency', function (): void {
    $dependency = callResolveTestingFramework('PHPUnit');

    expect($dependency)->toBeInstanceOf(PHPUnitDependency::class);
});

it('throws an exception for invalid testing framework', function (): void {
    callResolveTestingFramework('InvalidFramework');
})->throws(InvalidArgumentException::class, 'Invalid testing framework selected [InvalidFramework]');
