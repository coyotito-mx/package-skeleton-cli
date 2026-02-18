<?php

namespace App\Commands;

use App\Commands\Exceptions\InvalidToolException;
use App\DependencyManagers\Composer;
use App\DependencyManagers\DependencyManager;
use App\DependencyManagers\Exceptions\DependencyInstallationFailException;
use App\DependencyManagers\Exceptions\DependencyManagerNotInstalledException;
use App\DependencyManagers\Exceptions\InvalidDependencyFormatException;
use App\DependencyManagers\Npm;
use Exception;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Output\OutputInterface;

class InstallDependenciesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = <<<SIGNATURE
        install:dependencies
        { dependency?* : Install dependency }
        { --tool=composer : Install dependencies using the specified tool (composer|npm) }
        { --dev : Install dev dependencies }
        { --dry-run : Simulate the installation without making any changes }
        SIGNATURE;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install dependencies from composer and npm';

    public function __construct(protected Composer $composer, protected Npm $npm)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $tool = $this->getTool();

            $this->installDependencies(tool: $tool, dependencies: $this->getDependencies(), dev: $this->devMode());
        } catch (DependencyInstallationFailException $exception) {
            $this->error("Trying to install your dependencies failed.");
            $this->warn($exception->getMessage(), OutputInterface::VERBOSITY_DEBUG);

            return self::FAILURE;
        } catch (DependencyManagerNotInstalledException $exception) {
            $this->warn($exception->getMessage().", please install it before continuing.", OutputInterface::VERBOSITY_VERBOSE);
            $this->error("Fail to install dependencies.");

            return self::FAILURE;
        } catch (InvalidToolException $exception) {
            $this->warn($exception->getMessage().'. Please use Composer or NPM.');

            return self::INVALID;
        } catch (InvalidDependencyFormatException $exception) {
            $this->warn("[$exception->dependency] has an invalid format, the correct format is $exception->validFormat.");

            return self::INVALID;
        } catch (Exception $exception) {
            $this->error("An unexpected error occurred, please check the logs for more details.");
            $this->warn($exception->getMessage(), OutputInterface::VERBOSITY_DEBUG);

            return self::FAILURE;
        }

        $this->displaySuccessfulNotification();

        return self::SUCCESS;
    }

    /**
     * Get one of the available dependency manager
     *
     * @throws InvalidToolException if the provided tool name is not a valid one
     * @returns DependencyManager The tool to manage dependencies
     */
    public function getTool(): DependencyManager
    {
        return match ($tool = $this->option('tool')) {
            'composer' => $this->composer,
            'npm' => $this->npm,
            default => throw new InvalidToolException("The provided tool [$tool] does not exist."),
        };
    }

    /**
     * Install the project dependencies and the given dependencies array
     *
     * This method will try to install the project dependencies (`composer.json` or `package.json`), including the given one from the array.
     *
     * @throws DependencyInstallationFailException  if the one of the provided dependencies cannot be installed
     * @throws DependencyManagerNotInstalledException if the given tool is not installed
     */
    protected function installDependencies(DependencyManager $tool, array $dependencies = [], bool $dev = false): void
    {
        $this
            ->configureTool($tool)
            ->install($dependencies, $dev);
    }

    /**
     * Get the dependencies provided by the user
     *
     * @return string[] List of dependencies to install
     */
    protected function getDependencies(): array
    {
        return $this->argument('dependency') ?? [];
    }

    protected function devMode(): bool
    {
        return $this->option('dev') ?? false;
    }

    protected function displaySuccessfulNotification(): void
    {
        $tool = Str::ucfirst($this->option('tool'));

        $this->components->success("$tool successfully installed your project dependencies");

        if (filled($dependencies = $this->getDependencies())) {
            $this->components->info('The provided dependencies got installed:');

            $this->table(['Dependency'], array_map(fn (string $dep) => [$dep], $dependencies));
        }
    }

    protected function configureTool(DependencyManager $tool): DependencyManager
    {
        $tool->output = $this->getOutput();

        return $tool;
    }
}
