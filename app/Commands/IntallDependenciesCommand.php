<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Output\OutputInterface;

class IntallDependenciesCommand extends Command
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
        //
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $tool = $this->getTool();

            $this->installDependencies(tool: $tool, dependencies: $this->getDependencies(), dev: $this->devMode());
        } catch (DependendencyInstallationFailException $exception) {
            $dependency = $exception->failDependency();

            $this->error("[$dependency] fail to install");
            $this->warn($exception->getMessage(), OutputInterface::VERBOSITY_DEBUG);

            return self::INVALID;
        } catch (DependencyManagerNotInstallException) {
            $this->warn("[{$this->option('tool')}] is not available in your system, please install it before continuing", OutputInterface::VERBOSITY_VERBOSE);
            $this->error("Fail to install dependencies");

            return self::FAILURE;
        } catch (NonValidToolException $exception) {
            $this->warn("[$exception->tool] is not a valid dependency manager, please use Composer or NPM");

            return self::FAILURE;
        }

        $this->displaySuccessfulNotification();

        return self::SUCCESS;
    }

    /**
     * Get one of the available dependency manager
     *
     * @throws NonInvalidToolException if the provided tool name is not a valid one
     * @returns DependencyeManager The tool to manage dependencies
     */
    public function getTool(): DependencyManager
    {
        return match ($this->option('tool')) {
            'composer' => null,
            'npm' => null,
            default => throw new NonValidToolException()
        };
    }

    /**
     * Install the project dependencies and the given dependencies array
     *
     * This method will try to install the project dependencies (`composer.json` or `package.json`), including the given one from the array.
     *
     * @throws DependencyInstallationFailException  if the one of the provided dependencies cannot be installed
     * @throws DependencyManagerNotInstallException if the given tool is not installed
     */
    protected function installDependencies(DependencyManager $tool, array $dependencies = [], bool $dev = false): void
    {
        tap(
            $tool,
            fn (DependencyManager $tool) => $tool->setInput($this->input)->setOutput($this->output)
        )->add($dependencies, $dev)->install();
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
}
