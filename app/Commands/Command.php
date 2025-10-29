<?php

namespace App\Commands;

use App\Commands\Concerns\WithTraitsBootstrap;
use Illuminate\Console\Concerns\PromptsForMissingInput;
use Illuminate\Console\Parser;
use Illuminate\Contracts\Console\PromptsForMissingInput as PromptsForMissingInputContract;
use LaravelZero\Framework\Commands\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\text;

abstract class Command extends BaseCommand implements PromptsForMissingInputContract
{
    use PromptsForMissingInput,
        WithTraitsBootstrap;

    /**
     * @var array<string, array{description: string, missing: string}> The missing arguments to prompt for.
     */
    protected array $promptRequiredArguments = [];

    protected function configureUsingFluentDefinition(): void
    {
        [$name, $arguments, $options] = Parser::parse($this->signature);

        $this->setName($name);

        /**
         * Remove the signature to prevent the parent from calling again the fluent definition
         * from the parent constructor.
         *
         * @phpstan-ignore-next-line
         */
        unset($this->signature);

        parent::__construct();

        $this->bootBootstrapTraits();
        $this->configureCommand($arguments, $options);
    }

    /**
     * @param  InputArgument[]  $arguments
     */
    public function addArguments(array $arguments): self
    {
        $this->getDefinition()->addArguments($arguments);

        return $this;
    }

    /**
     * @param  InputOption[]  $options
     */
    public function addOptions(array $options): self
    {
        $this->getDefinition()->addOptions($options);

        return $this;
    }

    public function addPromptRequiredArgument(string $name, string $description, string|\Closure $missing): self
    {
        $this->promptRequiredArguments[$name] = [
            'description' => $description,
            'missing' => $missing,
        ];

        return $this;
    }

    protected function getPromptRequiredArguments(): array
    {
        return $this->promptRequiredArguments;
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return collect($this->getPromptRequiredArguments())
            ->mapWithKeys(function (array $definition, $name) {
                $missing = $definition['missing'];

                if (! $missing instanceof \Closure) {
                    $missing = fn () => text($missing);
                }

                return [$name => $missing];
            })
            ->toArray();
    }

    private function configureCommand(array $arguments = [], array $options = []): void
    {
        $this->addArguments(
            collect($this->promptRequiredArguments)
                ->map(fn (array $definition, string $name) => new InputArgument($name, InputArgument::REQUIRED, $definition['description']))
                ->merge($arguments)
                ->toArray()
        )->addOptions($options);
    }
}
