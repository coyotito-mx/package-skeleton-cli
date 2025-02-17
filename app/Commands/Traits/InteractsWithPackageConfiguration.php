<?php

declare(strict_types=1);

namespace App\Commands\Traits;

use Illuminate\Console\Parser;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

trait InteractsWithPackageConfiguration
{
    use InteractsWithAuthor;
    use InteractsWithNamespace;
    use InteractsWithDescription;

    /**
     * @var array<string, string|\Closure> The missing arguments to prompt for.
     */
    protected array $promptRequiredArguments = [];

    protected function configureUsingFluentDefinition(): void
    {
        if (isset($this->signature)) {
            [$name, $arguments, $options] = Parser::parse($this->signature);

            $this->setName($name);

            /**
             * Remove the signature to prevent the parent from calling again the fluent definition
             * from the parent constructor.
             */
            unset($this->signature);
        }

        parent::__construct();
        $this->bootPackageTraits();

        $this->addArguments(
            collect($this->promptRequiredArguments)
                ->map(fn (array $definition, string $name) => new InputArgument($name, InputArgument::REQUIRED, $definition['description']))
                ->merge($arguments ?? [])
                ->toArray()
        )
            ->addOptions($options ?? []);
    }

    /**
     * Add the package configuration arguments.
     *
     * @param  InputArgument[]  $arguments
     */
    public function addArguments(array $arguments): self
    {
        $this->getDefinition()->addArguments($arguments);

        return $this;
    }

    /**
     * Add the package configuration options.
     *
     * @param  InputOption[]  $options
     */
    public function addOptions(array $options): self
    {
        $this->getDefinition()->addOptions($options);

        return $this;
    }

    protected function getPromptRequiredArguments(): array
    {
        return $this->promptRequiredArguments;
    }

    public function addPromptRequiredArgument(string $name, string $description, string|\Closure $missing): self
    {
        $this->promptRequiredArguments[$name] = [
            'description' => $description,
            'missing' => $missing,
        ];

        return $this;
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return collect($this->getPromptRequiredArguments())
            ->mapWithKeys(function (array $definition, $name) {
                return [$name => $definition['missing']];
            })
            ->toArray();
    }

    protected function bootPackageTraits(): void
    {
        collect(class_uses_recursive($this))
            ->filter(fn (string $trait) => method_exists($this, 'bootPackage'.class_basename($trait)))
            ->each(fn (string $trait) => $this->{'bootPackage'.class_basename($trait)}());
    }
}
