<?php

use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Process\FakeProcessResult;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Exception\CommandNotFoundException;

function getConfigUsing (array $data) {
    return fn (string $key, mixed $default = null) => data_get($data, $key, $default);
};

it('can clone a repository', closure: function () {
    // Arrange
    $git = app('git');
    $repo = 'git@github.com:asciito/repo.git';
    Process::fake();

    // Act & Assert
    expect(
        fn () => $git->cloneRepository($repo, sandbox_path('repo'))
    )->not->toThrow(ProcessFailedException::class);

    Process::assertRan(static function ($_, FakeProcessResult $result) use ($repo) {
        $path = sandbox_path('repo');

        return $result->command() === "'git' '--version'" || str_starts_with($result->command(), "'git' 'clone' '$repo' '$path'");
    });
});

it("can't clone a repository without git", function () {
    // Arrange
    $git = app('git');
    $repo = 'git@github.com:asciito/repo.git';

    Process::fake([
        "'git' '--version'" => Process::result(errorOutput: 'command not found', exitCode: 1),
    ]);

    // Act & Assert
    expect(
        fn () => $git->cloneRepository($repo, sandbox_path('repo'))
    )->toThrow(CommandNotFoundException::class);
});

test('config int value', function () {
    // Arrange
    $git = app('git');

    Process::fake([
        "'git' '--version'" => 'git version',
        "'git' 'config' 'key'" => '1000',
        "'git' 'config' 'another'" => '100000',
    ]);

    // Act & Assert
    expect($git->getConfig('key'))
        ->toBeInt()
        ->toBe(1_000);
});

test('config does not exists', function () {
    // Arrange
    $git = app('git');

    Process::fake([
        "'git' '--version'" => 'git version',
        "'git' 'config' 'key'" => Process::result(errorOutput: 'key does not contain a section: key', exitCode: 1),
    ]);

    // Act & Assert
    expect($git->getConfig('key'))->toBeNull();
});

it('get user information', function () {
    // Arrange
    $git = app('git');

    Process::fake([
        "'git' '--version'" => 'git version',
        "'git' 'config' 'user.name'" => 'asciito',
        "'git' 'config' 'user.email'" => 'test@test.com',
    ]);

    // Act & Assert
    expect($git->getConfig('user.name'))
        ->toBeString()
        ->toBe("asciito", 'User name not return')
        ->and($git->getConfig('user.email'))
        ->toBeString()
        ->toBe("test@test.com", 'User email not return');
});
