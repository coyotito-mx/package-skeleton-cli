<?php

use App\Commands\Concerns\InteractsWithAuthor;
use App\Commands\Concerns\InteractsWithNamespace;

dataset('author', [
    'mini',
    'jane',
    'alice',
    'john',
    'jane',
]);

it('replace author', function (string $author) {
    configurable_testing_command('{{author}} Doe', InteractsWithAuthor::class);

    $this->artisan('demo', ['--author' => $author])
        ->expectsOutput(ucfirst($author).' Doe')
        ->assertSuccessful();
})->with('author');

it('replace author using namespace', function (string $author) {
    configurable_testing_command('{{author}} Doe', InteractsWithAuthor::class, InteractsWithNamespace::class);

    $this->artisan('demo', ['--namespace' => "$author/package"])
        ->expectsOutput(ucfirst($author).' Doe')
        ->assertSuccessful();
})->with('author');
