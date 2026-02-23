<?php

namespace App\Commands;

use App\Facades\Composer;
use Illuminate\Console\Concerns\PromptsForMissingInput as PromptsForMissingInputConcern;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use LaravelZero\Framework\Commands\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\alert;
use function Laravel\Prompts\clear;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\text;

class PackageCommand extends Command implements PromptsForMissingInput
{
    use PromptsForMissingInputConcern;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init
                            { vendor : The name of the package vendor }
                            { package : The name of the package }
                            { author : The package author }
                            { email : The package author email }
                            { --namespace= : The package namespace (optional, defaults to <vendor>\<package>) }
                            { --description= : The package description }
                            { --proceed : Accept the configuration and proceed without confirmation }
                            { --no-install : Skip installing composer dependencies }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize a new package structure';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        intro('Initializing package...');

        while (true) {
            $config = $this->getConfiguration();

            $this->displayConfiguration($config);

            if ($this->option('proceed')) {
                break;
            }

            if (confirm('Do you want to proceed with this configuration?')) {
                break;
            }

            warning("Let's try again. Please provide the correct information.");

            Sleep::for(2)->seconds();

            clear();
        }

        try {
            $this->replacePlaceholders($config);

            $this->installDependencies(shouldSkip: $this->option('no-install') ?? false);
        } catch (\Exception $e) {
            error('An error occurred while initializing the package, please read the log for more details.');

            logger()->error('Error initializing package', [
                'exception' => $e->getMessage(),
                'config' => $config,
            ]);

            return self::FAILURE;
        }

        $this->displaySuccessMessage();

        return self::SUCCESS;
    }

    public function displayConfiguration(array $config): void
    {
        $header = ['Vendor', 'Package', 'Namespace'];
        $rows   = [[
            $config['vendor'],
            $config['package'],
            $config['namespace'],
        ]];

        if (isset($config['description'])) {
            $header[] = 'Description';
            $rows[0][] = $config['description'];
        }

        $header = [...$header, 'Author', 'Email'];
        $rows[0] = [...$rows[0], $config['author'], $config['email']];

        table($header, $rows);
    }

    public function displaySuccessMessage(): void
    {
        outro("Package [{$this->getNamespace()}] initialized successfully!");
    }

    public function replacePlaceholders(array $config): void
    {
        //
    }

    public function installDependencies(bool $shouldSkip = false): void
    {
        if ($shouldSkip) {
            warning('Skip composer dependencies installation.');

            return;
        }

        $testing = $this->askForTestingFramework();

        $dependencies = $this->getDependencies($testing['identifier']);

        alert('Installing composer dependencies...');

        Composer::install($dependencies);
    }

    public function askForTestingFramework(): array
    {
        $choices = [
            'phpunit' => 'PHPUnit',
            'pest' => 'Pest',
        ];

        $testing = select('Which testing framework do you want to use?', $choices, default: 'phpunit');

        return [
            'identifier' => $testing,
            'name' => $choices[$testing],
        ];
    }

    protected function getDependencies(string $testing): array
    {
        $dependencies = [
            'phpunit' => ['phpunit/phpunit'],
            'pest'    => ['pestphp/pest', 'pestphp/pest-plug'],
        ];

        return $dependencies[$testing] ?? throw new \Exception('Dependency not found for the selected testing framework.');
    }

    public function getVendor(): string
    {
        return Str::studly($this->argument('vendor'));
    }

    public function getPackage(): string
    {
        return Str::studly($this->argument('package'));
    }

    public function getNamespace(): string
    {
        if ($this->option('namespace')) {
            [$vendor, $package] = explode('\\', $this->option('namespace'));

            return Str::studly($vendor) . '\\' . Str::studly($package);
        }

        return Str::studly($this->getVendor()) . '\\' . Str::studly($this->getPackage());
    }

    public function getPackageDescription(): ?string
    {
        if (! $this->option('description')) {
            return null;
        }

        return Str::ucfirst($this->option('description'));
    }

    public function getAuthor(): string
    {
        return Str::title($this->argument('author'));
    }

    public function getEmail(): string
    {
        return Str::lower($this->argument('email'));
    }

    public function getAuthorInformation(): ?array
    {
        // Attempt to get git user.name and user.email from global configuration, and transform it to JSON for easier parsing
        $result = Process::run("git config --list --global | jq -Rn '[inputs | split(\"=\") | { (.[0]): .[1] } ] | add'");

        if ($result->failed()) {
            return null;
        }

        $data = json_decode($result->output(), true);

        return [
            'author' => $data['user.name'] ?? null,
            'email' => $data['user.email'] ?? null,
        ];
    }

    public function getConfiguration(): array
    {
        $config = [
            'vendor' => $this->getVendor(),
            'package' => $this->getPackage(),
            'namespace' => $this->getNamespace(),
            'author' => $this->getAuthor(),
            'email' => $this->getEmail(),
        ];

        if ($description = $this->getPackageDescription()) {
            $config['description'] = $description;
        }

        return $config;
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        $namespaceDefined = $this->option('namespace') !== null;

        $inputs = [
            'vendor'  => function () use ($namespaceDefined): string {
                if ($namespaceDefined) {
                    return explode('\\', $this->option('namespace'))[0];
                }

                return text('Enter the package vendor name', 'acme');
            },
            'package' => function () use ($namespaceDefined): string {
                if ($namespaceDefined) {
                    return explode('\\', $this->option('namespace'))[1];
                }

                return text('Enter the package name', 'blog');
            },
        ];

        $information = $this->getAuthorInformation();

        if (blank($information)) {
            $inputs = [
                ...$inputs,
                'author' => fn (): string => text('Enter the author name', 'John Doe'),
                'email'  => fn (): string => text('Enter the author email', 'john@doe.com'),
            ];
        } else {
            ['author' => $author, 'email' => $email] = $information;

            $inputs = [
                ...$inputs,
                'author' => fn (): string => $author ?? text('Enter the author name', 'John Doe'),
                'email'  => fn (): string => $email  ?? text('Enter the author email', 'john@doe.com'),
            ];
        }


        return $inputs;
    }
}
