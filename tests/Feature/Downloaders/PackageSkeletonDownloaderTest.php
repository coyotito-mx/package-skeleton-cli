<?php

use App\Downloaders\Exceptions\DecompressException;
use App\Downloaders\Exceptions\DownloadException;
use App\Downloaders\Exceptions\UnsupportedSkeletonException;
use App\Downloaders\PackageSkeletonDownloader;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;

use function Illuminate\Filesystem\join_paths;

function makeDownloaderTestDirectory(): string
{
    $directory = join_paths(temp_path('downloader-tests'), uniqid('downloader_', true));

    mkdir($directory, 0755, true);

    return $directory;
}

function invokeDownloaderMethod(PackageSkeletonDownloader $downloader, string $method, mixed ...$arguments): mixed
{
    return \Closure::bind(
        fn (mixed ...$args): mixed => $this->{$method}(...$args),
        $downloader,
        PackageSkeletonDownloader::class
    )(...$arguments);
}

it('resolves skeleton metadata for supported skeletons', function (string $skeleton, string $repository): void {
    $downloader = new PackageSkeletonDownloader;

    $resolved = invokeDownloaderMethod($downloader, 'resolvePackageSkeleton', $skeleton);

    expect($resolved)
        ->toHaveKey('url')
        ->toHaveKey('filename')
        ->and($resolved['url'])->toContain("coyotito-mx/{$repository}/archive/refs/heads/main.zip")
        ->and($resolved['filename'])->toBe("{$repository}-main.zip");
})->with([
    'laravel skeleton' => ['laravel', 'laravel-package-skeleton'],
    'vanilla skeleton' => ['vanilla', 'package-skeleton'],
]);

it('throws for unsupported skeleton type', function (): void {
    $downloader = new PackageSkeletonDownloader;

    expect(fn (): mixed => invokeDownloaderMethod($downloader, 'resolvePackageSkeleton', 'unknown'))
        ->toThrow(UnsupportedSkeletonException::class, 'Unsupported skeleton type: unknown');
});

it('fetches skeleton zip using curl process', function (): void {
    $downloader = new PackageSkeletonDownloader;
    $directory = makeDownloaderTestDirectory();

    try {
        $process = Process::fake();

        $path = invokeDownloaderMethod($downloader, 'fetch', PackageSkeletonDownloader::SKELETON_VANILLA, $directory);

        $expectedPath = join_paths($directory, 'package-skeleton-main.zip');
        $expectedUrl = 'https://github.com/coyotito-mx/package-skeleton/archive/refs/heads/main.zip';

        expect($path)->toBe($expectedPath);

        expect($process)->assertRanTimes(function (PendingProcess $pendingProcess) use ($expectedPath, $expectedUrl): bool {
            $command = is_array($pendingProcess->command)
                ? implode(' ', $pendingProcess->command)
                : (string) $pendingProcess->command;

            return str_contains($command, 'curl')
                && str_contains($command, $expectedUrl)
                && str_contains($command, $expectedPath);
        }, 1);
    } finally {
        \App\Helpers\rmdir_recursive($directory);
    }
});

it('fetch removes existing zip before downloading', function (): void {
    $downloader = new PackageSkeletonDownloader;
    $directory = makeDownloaderTestDirectory();

    try {
        $expectedPath = join_paths($directory, 'package-skeleton-main.zip');
        file_put_contents($expectedPath, 'stale-content');

        Process::fake();

        invokeDownloaderMethod($downloader, 'fetch', PackageSkeletonDownloader::SKELETON_VANILLA, $directory);

        expect(file_exists($expectedPath))->toBeFalse();
    } finally {
        \App\Helpers\rmdir_recursive($directory);
    }
});

it('fetch throws domain exception when curl fails', function (): void {
    $downloader = new PackageSkeletonDownloader;
    $directory = makeDownloaderTestDirectory();

    try {
        Process::fake(fn (): \Illuminate\Process\FakeProcessResult => Process::result(exitCode: 1));

        expect(fn (): mixed => invokeDownloaderMethod($downloader, 'fetch', PackageSkeletonDownloader::SKELETON_VANILLA, $directory))
            ->toThrow(DownloadException::class, 'Failed to download skeleton from');
    } finally {
        \App\Helpers\rmdir_recursive($directory);
    }
});

it('cleans target directory without deleting parent directory', function (): void {
    $downloader = new PackageSkeletonDownloader;

    $parent = makeDownloaderTestDirectory();
    $target = join_paths($parent, 'target');

    mkdir(join_paths($target, 'nested'), 0755, true);
    file_put_contents(join_paths($target, '.env'), 'secret');
    file_put_contents(join_paths($target, 'nested', 'file.txt'), 'payload');
    file_put_contents(join_paths($parent, 'sentinel.txt'), 'do-not-delete');

    try {
        invokeDownloaderMethod($downloader, 'cleanup', $target);

        $remaining = array_values(array_diff(scandir($target) ?: [], ['.', '..']));

        expect($remaining)->toBe([])
            ->and(file_exists(join_paths($parent, 'sentinel.txt')))->toBeTrue()
            ->and(file_exists($target))->toBeTrue();
    } finally {
        \App\Helpers\rmdir_recursive($parent);
    }
});

it('cleanup handles missing directory gracefully', function (): void {
    $downloader = new PackageSkeletonDownloader;

    $missing = join_paths(temp_path('downloader-tests'), uniqid('missing_', true));

    invokeDownloaderMethod($downloader, 'cleanup', $missing);

    expect(file_exists($missing))->toBeFalse();
});

it('decompresses a valid zip into destination directory', function (): void {
    expect(extension_loaded('zip'))->toBeTrue();

    $downloader = new PackageSkeletonDownloader;
    $directory = makeDownloaderTestDirectory();
    $zipPath = join_paths($directory, 'package-skeleton-main.zip');

    try {
        createZipWithFile($zipPath, 'package-skeleton-main/README.md', 'ok');

        invokeDownloaderMethod($downloader, 'decompress', $zipPath);

        expect(file_exists(join_paths($directory, 'package-skeleton-main', 'README.md')))->toBeTrue();
    } finally {
        \App\Helpers\rmdir_recursive($directory);
    }
});

it('throws when zip cannot be opened', function (): void {
    expect(extension_loaded('zip'))->toBeTrue();

    $downloader = new PackageSkeletonDownloader;
    $directory = makeDownloaderTestDirectory();
    $zipPath = join_paths($directory, 'invalid-main.zip');

    try {
        file_put_contents($zipPath, 'this-is-not-a-zip');

        expect(fn (): mixed => invokeDownloaderMethod($downloader, 'decompress', $zipPath))
            ->toThrow(DecompressException::class, 'Failed to open ZIP file');
    } finally {
        \App\Helpers\rmdir_recursive($directory);
    }
});

it('throws when zip cannot be extracted due to destination permissions', function (): void {
    expect(extension_loaded('zip'))->toBeTrue();

    $downloader = new PackageSkeletonDownloader;
    $directory = makeDownloaderTestDirectory();
    $zipPath = join_paths($directory, 'readonly-main.zip');

    try {
        createZipWithFile($zipPath, 'readonly-main/README.md', 'ok');

        chmod($directory, 0555);

        expect(fn (): mixed => invokeDownloaderMethod($downloader, 'decompress', $zipPath))
            ->toThrow(DecompressException::class, 'Failed to decompress skeleton');
    } finally {
        chmod($directory, 0755);
        \App\Helpers\rmdir_recursive($directory);
    }
});

it('downloads and decompresses skeleton', function (): void {
    expect(extension_loaded('zip'))->toBeTrue();

    $directory = makeDownloaderTestDirectory();
    $downloader = new PackageSkeletonDownloader($directory);

    try {
        Process::fake(function (PendingProcess $process): \Illuminate\Process\FakeProcessResult {
            $command = is_array($process->command) ? $process->command : [(string) $process->command];
            $outputPath = $command[array_search('-o', $command, true) + 1] ?? null;

            if (is_string($outputPath)) {
                createZipWithFile($outputPath, 'package-skeleton-main/README.md', 'ok');
            }

            return Process::result();
        });

        file_put_contents(join_paths($directory, 'stale-file.txt'), 'stale');
        mkdir(join_paths($directory, 'old-dir'), 0755, true);

        $extractedPath = $downloader->download(PackageSkeletonDownloader::SKELETON_VANILLA);

        expect($extractedPath)->toBe(join_paths($directory, 'package-skeleton-main'))
            ->and(file_exists(join_paths($extractedPath, 'README.md')))->toBeTrue()
            ->and(file_exists(join_paths($directory, 'stale-file.txt')))->toBeFalse()
            ->and(file_exists(join_paths($directory, 'old-dir')))->toBeFalse();
    } finally {
        \App\Helpers\rmdir_recursive($directory);
    }
});

it('download throws exception when zip root folder does not match github naming convention', function (): void {
    expect(extension_loaded('zip'))->toBeTrue();

    $directory = makeDownloaderTestDirectory();
    $downloader = new PackageSkeletonDownloader($directory);

    try {
        Process::fake(function (PendingProcess $process): \Illuminate\Process\FakeProcessResult {
            $command = is_array($process->command) ? $process->command : [(string) $process->command];
            $outputPath = $command[array_search('-o', $command, true) + 1] ?? null;

            if (is_string($outputPath)) {
                createZipWithFile($outputPath, 'unexpected-root/README.md', 'ok');
            }

            return Process::result();
        });

        expect(fn (): mixed => $downloader->download(PackageSkeletonDownloader::SKELETON_VANILLA))
            ->toThrow(DownloadException::class, 'Unexpected skeleton ZIP structure');

        expect(file_exists(join_paths($directory, 'unexpected-root', 'README.md')))->toBeTrue();
    } finally {
        \App\Helpers\rmdir_recursive($directory);
    }
});

it('throws exception when temp folder cannot be created', function (): void {
    $root = makeDownloaderTestDirectory();
    $parent = join_paths($root, 'readonly-parent');
    $target = join_paths($parent, 'child-folder');

    mkdir($parent, 0755, true);

    try {
        chmod($parent, 0555);

        $downloader = new PackageSkeletonDownloader($target);

        expect(fn (): mixed => $downloader->download(PackageSkeletonDownloader::SKELETON_VANILLA))
            ->toThrow(DownloadException::class, 'Unable to create skeleton temp folder');
    } finally {
        chmod($parent, 0755);
        \App\Helpers\rmdir_recursive($root);
    }
});
