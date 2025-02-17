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
    use InteractsWIthLicense;
    use InteractsWithVersion;

    /**
     * @var array<string, string|\Closure> The missing arguments to prompt for.
     */
    protected array $promptRequiredArguments = [];

    protected array $packageTypes = [
        'library',
        'project',
        'metapackage',
        'composer-plugin',
        'symfony-bundle',
        'wordpress-plugin',
        'wordpress-theme',
        'drupal-module',
        'drupal-theme',
        'drupal-profile',
        'magento-module',
        'magento-theme',
        'typo3-cms-extension',
    ];

    protected array $minimumStabilityAvailable = [
        'stable',
        'rc' => 'RC',
        'beta',
        'alpha',
        'dev',
    ];

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
            ->addOption('minimum-stability', mode: InputOption::VALUE_OPTIONAL, description: 'The minimum stability allowed for the package', default: 'dev')
            ->addOption('type', mode: InputOption::VALUE_OPTIONAL, description: 'The package type', default: 'library')
            ->addOptions($options ?? []);
    }

    public function getPackageMinimumStability(): string
    {
        return $this->option('minimum-stability');
    }

    public function getPackageMinimumStabilityType(): string
    {
        $minimumStability = collect($this->minimumStabilityAvailable)
            ->map(fn (string $value, mixed $key) => [
                ctype_digit($key) ? $value : $key => $value,
            ])->firstWhere($this->option('minimum-stability'));

        if (is_null($minimumStability)) {
            throw new \RuntimeException('Invalid minimum stability.');
        }

        return $minimumStability;
    }

    public function getPackageType(): string
    {
        $type = $this->option('type');

        if (! in_array($type, ['library', 'project', 'metapackage'])) {
            throw new \RuntimeException('Invalid package type.');
        }

        return $type;
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
