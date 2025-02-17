<?php

declare(strict_types=1);

namespace App\Commands\Traits;

use App\Replacer\AuthorReplacer;
use App\Replacer\DescriptionReplacer;
use App\Replacer\LicenseReplacer;
use App\Replacer\MinimumStabilityReplacer;
use App\Replacer\NamespaceReplacer;
use App\Replacer\PackageReplacer;
use App\Replacer\TypeReplacer;
use App\Replacer\VendorReplacer;
use App\Replacer\VersionReplacer;
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
    use InteractsWithMinimumStability;
    use InteractsWithType;

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
        )->addOptions($options ?? []);
    }

    public function getPackageReplacers(): array
    {
        return [
            $this->pipeThroughReplacer($this->getPackageVendor(), VendorReplacer::class),
            $this->pipeThroughReplacer($this->getPackageName(), PackageReplacer::class),
            $this->pipeThroughReplacer($this->getPackageAuthorName(), AuthorReplacer::class),
            $this->pipeThroughReplacer($this->getPackageDescription(), DescriptionReplacer::class),
            $this->pipeThroughReplacer($this->getPackageNamespace(), NamespaceReplacer::class),
            $this->pipeThroughReplacer($this->getPackageVersion(), VersionReplacer::class),
            $this->pipeThroughReplacer($this->getPackageMinimumStability(), MinimumStabilityReplacer::class),
            $this->pipeThroughReplacer($this->getPackageType(), TypeReplacer::class),
            $this->pipeThroughReplacer($this->getPackageLicense(), LicenseReplacer::class),
        ];
    }

    /**
     * @param  class-string  $replacer
     */
    protected function pipeThroughReplacer(string $replacement, string $replacer): \Closure
    {
        return function (string $subject, \Closure $next) use ($replacement, $replacer): string {
            return $next($replacer::make($replacement)->replace($subject));
        };
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
