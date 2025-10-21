<?php

use function App\Helpers\rmdir_recursive;
use function Illuminate\Filesystem\join_paths;

function makeFolderStructure(array $structure): string
{
    $root = sandbox_path('root-'.uniqid());

    mkdir($root, recursive: true);

    $walk = function (string $folder, array $structure) use (&$walk) {
        foreach ($structure as $child => $inner) {
            if (is_int($child)) {
                file_put_contents(join_paths($folder, $inner), 'Lorem ipsum dolor it');
            } else {
                $folder = join_paths($folder, $child);

                mkdir($folder, recursive: true);

                $walk($folder, $inner);
            }
        }
    };

    $walk($root, $structure);

    return $root;
}

test('remove directory', function () {
    // Arrange
    $folder = sandbox_path('folder');

    // Act
    mkdir($folder, recursive: true);

    // Assert
    expect($folder)->toBeDirectory();
    rmdir_recursive($folder);
    expect($folder)->not->toBeDirectory();
});

test('remove directory with files', function () {
    // Arrange
    $folder = sandbox_path('folder');
    $file1 = join_paths($folder, 'file1.txt');
    $file2 = join_paths($folder, 'file2.txt');

    // Act
    mkdir($folder, recursive: true);
    file_put_contents($file1, 'Hello World');
    file_put_contents($file2, 'Hello Again');

    // Assert
    expect($folder)
        ->toBeDirectory()
        ->and($file1)
        ->toBeFile()
        ->and($file2)
        ->toBeFile();

    rmdir_recursive($folder);

    expect($folder)
        ->not->toBeDirectory()
        ->and($file1)
        ->not->toBeFile()
        ->and($file2)
        ->not->toBeFile();
});

test('remove deeply nested files in a folder and leave root folder', function (array $structure) {
    $root = makeFolderStructure($structure);

    expect($root)->toBeDirectory();

    rmdir_recursive($root, true);

    expect($root)->toBeDirectory();

    $open = opendir($root);

    // Default '.' and '..' folders
    readdir($open);
    readdir($open);

    // At this time should not be any file/folder left
    $entry = readdir($open);
    closedir($open);

    expect($entry)->toBeFalse();
})->with([
    'folder structure' => [
        [
            'file1.txt',
            'file2.txt',
            'folder' => [
                'file1.txt',
                'folder1' => [
                    'file1.txt',
                    'file2.txt',
                    'file3.txt',
                    'folder' => [],
                ],
                'folder2' => [
                    'folder' => [
                        'file1.txt',
                        'file2.txt',
                    ],
                    'file1.txt',
                ],
            ],
        ],
    ],
]);

it('can not delete given path if is a file', function () {
    // Arrange
    $filepath = sandbox_path('file.txt');
    file_put_contents($filepath, 'Lorem ipsum dolor it');

    // Act & Assert
    expect($filepath)
        ->toBeFile()
    ->and(fn () => rmdir_recursive($filepath))
        ->toThrow(InvalidArgumentException::class);
});
