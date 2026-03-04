<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use App\Replacers\Concerns\InteractsWithReplacers;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\text;

/**
 * A trait that provides common configuration options and arguments for package-related commands.
 *
 * @mixin \Illuminate\Console\Command
 */
trait HasPackageConfiguration
{
    use Configurations\HasAuthorInformation,
        Configurations\HasLicense,
        Configurations\HasNamespace,
        Configurations\HasPackageDescription,
        Configurations\HasVendorPackage,
        Configurations\HasVersion,
        Configurations\HasYear,
        InteractsWithReplacers;

    #[\Override]
    protected function configure(): void
    {
        $this->bootstrapPackageConfiguration();

        $this->addCommandOptions([
            ['class', null, InputOption::VALUE_REQUIRED, 'The class name to use in replacements (defaults to the package name)'],
            ['bootstrap', null, InputOption::VALUE_REQUIRED, 'Initialize a new package (options: laravel, vanilla)'],
            ['force', null, InputOption::VALUE_NONE, 'Force bootstrapping even if the target directory is not empty (use with --bootstrap)'],
            ['proceed', null, InputOption::VALUE_NONE, 'Accept the configuration and proceed without confirmation'],
            ['no-install', null, InputOption::VALUE_NONE, 'Skip installing composer dependencies'],
            ['path', null, InputOption::VALUE_REQUIRED, 'The path to initialize the package in (defaults to current working directory)'],
            ['skip-license', null, InputOption::VALUE_NONE, 'Skip creating a LICENSE file if one does not exist'],
            ['exclude', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Paths to exclude when processing files'],
        ]);
    }

    private function bootstrapPackageConfiguration(): void
    {
        $this->bootVendorPackage();
        $this->bootAuthorInformation();
        $this->bootNamespace();
        $this->bootPackageDescription();
        $this->bootLicense();
        $this->bootVersion();
        $this->bootYear();
    }

    /**
     * Add multiple arguments to the command
     *
     * @param  array  $arguments  The list of arguments to add
     */
    protected function addCommandArguments(array $arguments): void
    {
        foreach ($arguments as $argument) {
            $this->addCommandArgument(...$argument);
        }
    }

    /**
     * Add an argument to the command
     *
     * @param  string  $name  The argument name
     * @param  int-mask-of<InputArgument::*>|null  $mode  The argument mode: a bit mask of self::REQUIRED, self::OPTIONAL and self::IS_ARRAY
     * @param  string  $description  A description text
     * @param  string|bool|int|float|array|null  $default  The default value (for self::OPTIONAL mode only)
     * @param  array|\Closure(\Symfony\Component\Console\Completion\CompletionInput, \Symfony\Component\Console\Completion\CompletionSuggestions):list<string|\Symfony\Component\Console\Completion\Suggestion>  $suggestedValues  The values used for input completion
     */
    protected function addCommandArgument(string $name, ?int $mode = null, string $description = '', $default = null, array|\Closure $suggestedValues = []): void
    {
        $this->getDefinition()->addArgument(new InputArgument(
            name: $name,
            mode: $mode,
            description: $description,
            default: $default,
            suggestedValues: $suggestedValues,
        ));
    }

    /**
     * Get the list of paths to exclude when processing files, combining default and user-provided exclusions.
     *
     * @param  array  $options  The list of options to add
     */
    protected function addCommandOptions(array $options): void
    {
        foreach ($options as $option) {
            $this->addCommandOption(...$option);
        }
    }

    /**
     * Add an option to the command
     *
     * @param  string  $name  The option name
     * @param  string|array|null  $shortcut  The shortcuts, can be null, a string of shortcuts delimited by | or an array of shortcuts
     * @param  int-mask-of<InputOption::*>|null  $mode  The option mode: One of the VALUE_* constants
     * @param  string  $description  A description text
     * @param  string|bool|int|float|array|null  $default  The default value (must be null for self::VALUE_NONE)
     * @param  array|\Closure(\Symfony\Component\Console\Completion\CompletionInput,\Symfony\Component\Console\Completion\CompletionSuggestions):list<string|\Symfony\Component\Console\Completion\Suggestion>  $suggestedValues  The values used for input completion
     */
    protected function addCommandOption(string $name, null|string|array $shortcut = null, ?int $mode = null, string $description = '', $default = null, array|\Closure $suggestedValues = []): void
    {
        $this->getDefinition()->addOption(new InputOption(
            name: $name,
            shortcut: $shortcut,
            mode: $mode,
            description: $description,
            default: $default,
            suggestedValues: $suggestedValues,
        ));
    }

    /**
     * Get the class name
     */
    private function getClass(): string
    {
        return Str::studly($this->option('class') ?? $this->getPackage());
    }

    /**
     * Get the path where the package should be initialized.
     */
    private function getPath(): string
    {
        return $this->option('path') ?? getcwd();
    }

    /**
     * Define interactive prompts for missing required command arguments.
     *
     * @return array<string, \Closure(): string|null>
     */
    #[\Override]
    protected function promptForMissingArgumentsUsing(): array
    {
        $info = $this->getGitUserInformation();

        return [
            'vendor' => fn (): string => text('Enter the package vendor name', 'Acme', required: true),
            'package' => fn (): string => text('Enter the package name', 'Package', required: true),
            'namespace' => fn (): ?string => text('Enter the package namespace', 'Vendor\\Package', hint: 'Optional, leave empty to auto-generate') ?: null,
            'author' => fn (): string => $info['author'] ?? text('Enter the author name', 'John Doe', required: true),
            'email' => fn (): string => $info['email'] ?? text('Enter the author\'s email', 'john@doe.com', required: true),
            'description' => fn (): ?string => text('Enter the package description', 'A short description of the package') ?: null,
        ];
    }
}
