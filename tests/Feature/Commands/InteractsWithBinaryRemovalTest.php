<?php

use App\Commands\Concerns\InteractsWithBinaryRemoval;

use function App\Helpers\rmdir_recursive;

function makeCliRemovalProbe(
    ?\Closure $resolveExecutable = null,
    ?\Closure $isRunningFromPhar = null,
    ?\Closure $resolveRunningPharPath = null,
    ?\Closure $runningPharFromRuntime = null,
): object {
    return new readonly class($resolveExecutable, $isRunningFromPhar, $resolveRunningPharPath, $runningPharFromRuntime)
    {
        use InteractsWithBinaryRemoval {
            resolveExecutablePathFromInvocation as private traitResolveExecutablePathFromInvocation;
            isRunningFromPhar as private traitIsRunningFromPhar;
            resolveRunningPharPath as private traitResolveRunningPharPath;
            runningPharFromRuntime as private traitRunningPharFromRuntime;
        }

        public function __construct(
            private ?\Closure $resolveExecutable,
            private ?\Closure $isRunningFromPhar,
            private ?\Closure $resolveRunningPharPath,
            private ?\Closure $runningPharFromRuntime,
        ) {
            //
        }

        public function deleteExecutable(): bool
        {
            return $this->deleteBinary();
        }

        public function resolveExecutable(): ?string
        {
            return $this->resolveExecutablePathFromInvocation();
        }

        public function runningFromPhar(): bool
        {
            return $this->isRunningFromPhar();
        }

        public function runningPharPath(): ?string
        {
            return $this->resolveRunningPharPath();
        }

        protected function resolveExecutablePathFromInvocation(): ?string
        {
            if ($this->resolveExecutable) {
                return ($this->resolveExecutable)();
            }

            return $this->traitResolveExecutablePathFromInvocation();
        }

        protected function isRunningFromPhar(): bool
        {
            if ($this->isRunningFromPhar) {
                return (bool) ($this->isRunningFromPhar)();
            }

            return $this->traitIsRunningFromPhar();
        }

        protected function resolveRunningPharPath(): ?string
        {
            if ($this->resolveRunningPharPath) {
                return ($this->resolveRunningPharPath)();
            }

            return $this->traitResolveRunningPharPath();
        }

        protected function runningPharFromRuntime(): string
        {
            if ($this->runningPharFromRuntime) {
                return (string) ($this->runningPharFromRuntime)();
            }

            return $this->traitRunningPharFromRuntime();
        }
    };
}

afterAll(fn () => rmdir_recursive(temp_path('cli-removal')));

it('deletes executable using phar running path when running from phar', function (): void {
    $tempExecutable = tempnam(temp_path('cli-removal'), 'phar-');

    expect($tempExecutable)->not->toBeFalse();

    $probe = makeCliRemovalProbe(
        isRunningFromPhar: fn (): bool => true,
        resolveRunningPharPath: fn (): string => $tempExecutable,
    );

    $previousArgv = $_SERVER['argv'] ?? null;
    $_SERVER['argv'] = ['non-existent-executable'];

    try {
        expect($probe->deleteExecutable())->toBeTrue();
        expect(file_exists($tempExecutable))->toBeFalse();
    } finally {
        if ($previousArgv === null) {
            unset($_SERVER['argv']);
        } else {
            $_SERVER['argv'] = $previousArgv;
        }

        if (file_exists($tempExecutable)) {
            unlink($tempExecutable);
        }
    }
});

it('throws when executable path cannot be resolved', function (): void {
    $probe = makeCliRemovalProbe(resolveExecutable: fn (): ?string => null);

    expect(fn () => $probe->deleteExecutable())
        ->toThrow(RuntimeException::class, 'Unable to determine the CLI executable path for removal.');
});

it('throws when resolved path points to a directory', function (): void {
    $probe = makeCliRemovalProbe(resolveExecutable: fn (): string => getcwd());

    expect(fn () => $probe->deleteExecutable())
        ->toThrow(RuntimeException::class, 'Unable to determine the CLI executable path for removal.');
});

it('resolves executable from php plus script invocation', function (): void {
    $tempExecutable = tempnam(temp_path('cli-removal-script'), 'script-');

    expect($tempExecutable)->not->toBeFalse();

    $relativeScript = basename($tempExecutable);
    $cwdExecutable = getcwd().DIRECTORY_SEPARATOR.$relativeScript;
    rename($tempExecutable, $cwdExecutable);

    $probe = makeCliRemovalProbe();

    $previousArgv = $_SERVER['argv'] ?? null;
    $_SERVER['argv'] = [PHP_BINARY, $relativeScript, 'init'];

    try {
        expect($probe->resolveExecutable())->toBe(realpath($cwdExecutable) ?: $cwdExecutable);
    } finally {
        if ($previousArgv === null) {
            unset($_SERVER['argv']);
        } else {
            $_SERVER['argv'] = $previousArgv;
        }

        if (file_exists($cwdExecutable)) {
            unlink($cwdExecutable);
        }
    }
});

it('resolves executable from command name in PATH', function (): void {
    $tempDir = temp_path('cli-removal');

    $binaryName = 'skeleton-cli-test-bin';
    $binaryPath = $tempDir.DIRECTORY_SEPARATOR.$binaryName;
    file_put_contents($binaryPath, "#!/usr/bin/env php\n<?php\n");
    chmod($binaryPath, 0755);

    $probe = makeCliRemovalProbe();

    $previousArgv = $_SERVER['argv'] ?? null;
    $previousPath = getenv('PATH') ?: '';
    $_SERVER['argv'] = [$binaryName, 'init'];
    putenv('PATH='.$tempDir.PATH_SEPARATOR.$previousPath);

    try {
        expect($probe->resolveExecutable())->toBe(realpath($binaryPath) ?: $binaryPath);
    } finally {
        putenv('PATH='.$previousPath);

        if ($previousArgv === null) {
            unset($_SERVER['argv']);
        } else {
            $_SERVER['argv'] = $previousArgv;
        }

        if (file_exists($binaryPath)) {
            unlink($binaryPath);
        }

        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }
    }
})->skipLocally();

it('returns default phar runtime metadata when not running in phar', function (): void {
    $probe = makeCliRemovalProbe();

    expect($probe->runningFromPhar())->toBeFalse();
    expect($probe->runningPharPath())->toBeNull();
});

it('returns null when running phar path does not exist', function (): void {
    $probe = makeCliRemovalProbe(
        runningPharFromRuntime: fn (): string => temp_path('cli-removal').DIRECTORY_SEPARATOR.'missing-cli.phar',
    );

    expect($probe->runningFromPhar())->toBeTrue();
    expect($probe->runningPharPath())->toBeNull();
});
