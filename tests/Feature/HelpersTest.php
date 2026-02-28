<?php

use function Illuminate\Filesystem\join_paths;

function makeHelpersTestDirectory(): string
{
    $directory = join_paths(temp_path('helpers-tests'), uniqid('helpers_', true));

    mkdir($directory, 0755, true);

    return $directory;
}

it('lists entries without dot pseudo directories', function (): void {
    $directory = makeHelpersTestDirectory();

    try {
        mkdir(join_paths($directory, 'nested'), 0755, true);
        file_put_contents(join_paths($directory, 'visible.txt'), 'visible');
        file_put_contents(join_paths($directory, '.env'), 'secret');

        $entries = \App\Helpers\entries($directory)
            ->map(fn (\SplFileInfo $file): string => $file->getFilename())
            ->values()
            ->all();

        expect($entries)
            ->toContain('nested')
            ->toContain('visible.txt')
            ->toContain('.env')
            ->not->toContain('.')
            ->not->toContain('..');
    } finally {
        \App\Helpers\rmdir_recursive($directory);
    }
});

it('ignores dotfiles when requested', function (): void {
    $directory = makeHelpersTestDirectory();

    try {
        file_put_contents(join_paths($directory, 'visible.txt'), 'visible');
        file_put_contents(join_paths($directory, '.env'), 'secret');

        $entries = \App\Helpers\entries($directory, true)
            ->map(fn (\SplFileInfo $file): string => $file->getFilename())
            ->values()
            ->all();

        expect($entries)
            ->toContain('visible.txt')
            ->not->toContain('.env')
            ->not->toContain('.')
            ->not->toContain('..');
    } finally {
        \App\Helpers\rmdir_recursive($directory);
    }
});

it('returns an empty collection for a missing directory', function (): void {
    $directory = join_paths(temp_path('helpers-tests'), uniqid('missing_', true));

    $entries = \App\Helpers\entries($directory);

    expect($entries)->toBeEmpty();
});

it('removes directories recursively including hidden files', function (): void {
    $directory = makeHelpersTestDirectory();

    mkdir(join_paths($directory, 'a', 'b'), 0755, true);
    file_put_contents(join_paths($directory, 'a', 'b', '.hidden'), 'hidden');
    file_put_contents(join_paths($directory, 'a', 'b', 'file.txt'), 'content');

    \App\Helpers\rmdir_recursive($directory);

    expect(file_exists($directory))->toBeFalse();
});

it('does nothing when removing a non-existing directory', function (): void {
    $directory = join_paths(temp_path('helpers-tests'), uniqid('missing-rmdir_', true));

    \App\Helpers\rmdir_recursive($directory);

    expect(file_exists($directory))->toBeFalse();
});
