<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use App\Replacers\AuthorReplacer;
use App\Replacers\ClassReplacer;
use App\Replacers\Concerns\InteractsWithReplacers;
use App\Replacers\DescriptionReplacer;
use App\Replacers\EmailReplacer;
use App\Replacers\Exceptions\InvalidNamespaceException;
use App\Replacers\LicenseNameReplacer;
use App\Replacers\NamespaceReplacer;
use App\Replacers\PackageReplacer;
use App\Replacers\VendorReplacer;
use App\Replacers\VersionReplacer;
use App\Replacers\YearReplacer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\text;

trait HasPackageConfiguration
{
    use InteractsWithReplacers;

    #[\Override]
    protected function configure(): void
    {
        $this->addCommandArguments([
            ['vendor', InputArgument::REQUIRED, 'The name of the package vendor'],
            ['package', InputArgument::REQUIRED, 'The name of the package'],
            ['namespace', InputArgument::REQUIRED, 'The package namespace (auto-generated as Vendor\Package if not provided)'],
            ['author', InputArgument::REQUIRED, 'The package author'],
            ['email', InputArgument::REQUIRED, 'The package author email'],
            ['description', InputArgument::REQUIRED, 'The package description'],
        ]);

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

        $this
            ->addReplacer(VendorReplacer::class, fn () => $this->getVendor())
            ->addReplacer(PackageReplacer::class, fn () => $this->getPackage())
            ->addReplacer(NamespaceReplacer::class, fn () => $this->getNamespace())
            ->addReplacer(DescriptionReplacer::class, fn () => $this->getPackageDescription())
            ->addReplacer(AuthorReplacer::class, fn () => $this->getAuthor())
            ->addReplacer(EmailReplacer::class, fn () => $this->getEmail())
            ->addReplacer(LicenseNameReplacer::class, fn () => 'MIT')
            ->addReplacer(VersionReplacer::class, fn () => '0.0.1')
            ->addReplacer(YearReplacer::class) // This will replace the year with the current year
            ->addReplacer(ClassReplacer::class, fn () => $this->getClass());
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
     * Get the package vendor name formatted in StudlyCase.
     */
    private function getVendor(): string
    {
        return Str::studly($this->argument('vendor'));
    }

    /**
     * Get the package name formatted in StudlyCase.
     */
    private function getPackage(): string
    {
        return Str::studly($this->argument('package'));
    }

    /**
     * Get the package namespace, either from user input or auto-generated from vendor/package.
     *
     * @throws InvalidNamespaceException
     */
    private function getNamespace(): string
    {
        $namespace = $this->argument('namespace') ?: Str::studly($this->getVendor()).'\\'.Str::studly($this->getPackage());

        InvalidNamespaceException::validate($namespace);

        return $namespace;
    }

    /**
     * Get the package description, or null if not provided.
     *
     * @phpstan-ignore-next-line
     */
    private function getPackageDescription(): ?string
    {
        return $this->argument('description');
    }

    /**
     * Get the author name formatted in Title Case.
     */
    private function getAuthor(): string
    {
        return Str::title($this->argument('author'));
    }

    /**
     * Get the author email in lowercase.
     */
    private function getEmail(): string
    {
        return Str::lower($this->argument('email'));
    }

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
     * Fetch user's git global configuration.
     *
     * @return array<string, string>|null
     */
    private function getGitUserInformation(): ?array
    {
        $result = $this->makeProcess(['git', 'config'], '--list')->run();

        if ($result->failed() || ! $result->output()) {
            return null;
        }

        $options = collect(explode(PHP_EOL, $result->output()))
            ->mapWithKeys(function ($line) {
                $parts = explode('=', $line, 2);

                if (count($parts) === 2) {
                    return [trim($parts[0]) => trim($parts[1])];
                }

                return [];
            })
            ->filter(filled(...));

        if ($options->isEmpty() || ! $options->has(['user.name', 'user.email'])) {
            return null;
        }

        return [
            'author' => $options->get('user.name'),
            'email' => $options->get('user.email'),
        ];
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
