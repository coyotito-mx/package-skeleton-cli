<?php

use App\DependencyManagers\DependencyManager;
use App\DependencyManagers\Exceptions\DependencyInstallationFailException;
use App\DependencyManagers\Exceptions\InvalidDependencyFormatException;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class DummyManager extends DependencyManager
{
    protected static string $patternDependency = '/^(?<name>[a-z]+)(?:\:(?<version>[0-9.]+))?$/';

    public function add(array $dependencies, bool $dev = false): static
    {
        return $this;
    }

    public function install(array $dependencies = [], bool $dev = false): static
    {
        return $this;
    }

    protected function getValidFormatDescription(): string
    {
        return '<name>[:<version>]';
    }

    public function exposeEnsureProjectFileExists(string $filename): string
    {
        return $this->ensureProjectFileExists($filename);
    }

    public function exposeRunInstallCommand(string|array $command = 'install', array $dependencies = []): ProcessResult
    {
        return $this->runInstallCommand($command, $dependencies);
    }
}

it('parses a dependency or returns null', function () {
    $manager = new DummyManager(sys_get_temp_dir(), tty: false);

    expect($manager->parseDependency('foo:1.2'))
        ->toMatchArray(['name' => 'foo', 'version' => '1.2']);

    expect($manager->parseDependency('foo'))
        ->toMatchArray(['name' => 'foo']);

    expect($manager->parseDependency('Foo:1.2'))->toBeNull();
});

it('validates a dependency and throws on invalid', function () {
    $manager = new DummyManager(sys_get_temp_dir(), tty: false);

    $manager->validateDependency('foo:1.2');

    expect(fn () => $manager->validateDependency('Foo:1.2'))
        ->toThrow(InvalidDependencyFormatException::class);
});

it('validates multiple dependencies', function () {
    $manager = new DummyManager(sys_get_temp_dir(), tty: false);

    $manager->validateDependencies(['foo:1.2', 'bar:2.0']);

    expect(fn () => $manager->validateDependencies(['foo:1.2', 'Bar:2.0']))
        ->toThrow(InvalidDependencyFormatException::class);
});

it('ensures a project file exists', function () {
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'dep-manager-'.uniqid();
    File::ensureDirectoryExists($path);

    $manager = new DummyManager($path, tty: false);

    try {
        expect(fn () => $manager->exposeEnsureProjectFileExists('missing.json'))
            ->toThrow(RuntimeException::class);
    } finally {
        File::deleteDirectory($path);
    }
});

it('runInstallCommand throws when install fails', function () {
    Process::fake([
        "'--version' '--quiet'" => Process::result(output: '1.0.0', exitCode: 0),
        "'install'" => Process::result(output: '', errorOutput: 'fail', exitCode: 1),
    ]);

    $manager = new DummyManager(sys_get_temp_dir(), tty: false);

    expect(fn () => $manager->exposeRunInstallCommand('install', ['foo:1.0']))
        ->toThrow(DependencyInstallationFailException::class);
});

it('runInstallCommand succeeds when install passes', function () {
    $process = Process::fake([
        "'--version' '--quiet'" => Process::result(output: '1.0.0', exitCode: 0),
        "'install'" => Process::result(output: 'ok', exitCode: 0),
    ]);

    $manager = new DummyManager(sys_get_temp_dir(), tty: false);

    $manager->exposeRunInstallCommand('install', ['foo:1.0']);

    expect($process)
        ->assertRanTimes(fn (PendingProcess $process) => $process->command === ['--version', '--quiet'])
        ->assertRanTimes(fn (PendingProcess $process) => $process->command === ['install']);
});
