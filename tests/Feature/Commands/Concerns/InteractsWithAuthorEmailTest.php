<?php

use App\Commands\Concerns\InteractsWithAuthorEmail;
use Illuminate\Support\Facades\Process;

dataset('email', [
    'test@example.com',
    'john.doe@example.com',
    'jane.doe@example.com',
    'fulanito@example.com',
    'fulanita@example.com',
]);

it('replace author\'s email', function (string $email) {
    configurable_testing_command('{{email}}', InteractsWithAuthorEmail::class);

    $this->artisan('demo', ['--email' => $email])
        ->expectsOutputToContain($email)
        ->assertSuccessful();
})->with('email');

it('replace author\'s email using git config value', function (string $email) {
    configurable_testing_command('{{email}}', InteractsWithAuthorEmail::class);

    Process::fake([
        "'git' 'config' 'user.email'" => $email,
    ]);

    $this->artisan('demo')
        ->expectsOutput($email)
        ->assertSuccessful();
})->with('email');

it('ask for email', function (string $email) {
    configurable_testing_command('{{email}}', InteractsWithAuthorEmail::class);

    Process::fake([
        "'git' '--version'" => Process::result(errorOutput: 'command not found', exitCode: 1),
    ]);

    $this->artisan('demo')
        ->expectsQuestion("Author's Email", $email)
        ->expectsOutput($email)
        ->assertSuccessful();
})->with('email');

it('failed with non valid emails', function ($email) {
    $brokeEmail = explode('.', $email)[0];

    configurable_testing_command('{{email}}', InteractsWithAuthorEmail::class);

    $this->artisan('demo', ['--email' => $brokeEmail])->assertFailed();
})->throws(InvalidArgumentException::class)->with('email');
