<?php

use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Process\FakeProcessResult;
use Illuminate\Support\Facades\Process;

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

it('can clone a repository without git', function () {

    // Arrange
    $git = app('git');
    $repo = 'git@github.com:asciito/repo.git';

    Process::fake([
        "'git' '--version'" => Process::result(exitCode: 1),
    ]);

    // Act & Assert
    expect(
        fn () => $git->cloneRepository($repo, sandbox_path('repo'))
    )->toThrow(RuntimeException::class, 'Git is not available on this system.');
});
