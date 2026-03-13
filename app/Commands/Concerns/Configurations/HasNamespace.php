<?php

declare(strict_types=1);

namespace App\Commands\Concerns\Configurations;

use App\Placeholders\Exceptions\InvalidNamespaceException;
use App\Placeholders\Namespace\NamespacePlaceholder;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

trait HasNamespace
{
    protected function bootNamespace(): void
    {
        $this->addCommandArgument(name: 'namespace', mode: InputArgument::REQUIRED, description: 'The root namespace of the package');

        $this->addPlaceholder(NamespacePlaceholder::class, fn (): string => $this->getNamespace());
    }

    /**
     * Get the package namespace, either from user input or auto-generated from vendor/package.
     *
     * @throws InvalidNamespaceException
     */
    protected function getNamespace(): string
    {
        $namespace = $this->argument('namespace') ?: Str::studly($this->getVendor()).'\\'.Str::studly($this->getPackage());

        InvalidNamespaceException::validate($namespace);

        return $namespace;
    }
}
