<?php



it('init package', function () {
    artisan('init', ['vendor' => 'vendor', 'package' => 'acme', '--proceed' => true])
        ->expectsOutputToContain('Package [Vendor\\Package] initialized successfully!')
        ->assertSuccessful();
})->todo();

it('init package using namespace', function () {
    artisan('init', ['namespace' => 'Vendor\\Acme', '--proceed' => true])
        ->expectsOutputToContain('Package [Vendor\\Acme] initialized successfully!')
        ->assertSuccessful();
})->todo();

it('install package composer dependencies', function () {
    artisan('init', ['vendor' => 'vendor', 'package' => 'acme', '--proceed' => true])
        ->expectsOutputToContain('Installing composer dependencies...')
        ->expectsOutputToContain('Composer dependencies initialized successfully!')
        ->expectsOutputToContain('Package [Vendor\\Acme] initialized successfully!')
        ->assertSuccessful();
})->todo();

test('skip composer dependencies installation', function () {
    artisan('init', ['vendor' => 'vendor', 'package' => 'acme', '--proceed' => true, '--no-install' => true])
        ->doesntExpectOutputToContain('Installing composer dependencies...')
        ->doesntExpectOutputToContain('Composer dependencies initialized successfully!')
        ->expectsOutputToContain('Package [Vendor\\Acme] initialized successfully!')
        ->assertSuccessful();
})->todo();

it('uses git user/email for author by default', function () {
    artisan('init', ['vendor' => 'vendor', 'package' => 'acme', '--proceed' => true])
        ->expectsOutputToContain('Using git user.name and user.email for author information.')
        ->expectsOutputToContain('Package [Vendor\\Acme] initialized successfully!')
        ->assertSuccessful();
})->todo();

it('display summary after package initialization', function () {
    artisan('init', ['vendor' => 'vendor', 'package' => 'acme', '--description' => 'A new package', '--proceed' => true])
        ->expectsTable(
            ['Key', 'Value'],
            [
                ['Vendor', 'Vendor'],
                ['Package', 'Package'],
                ['Namespace', 'Vendor\\Acme'],
                ['Description', 'A new package'],
                ['Author', 'Your Name <'],
                ['License', 'MIT'],
            ],
        )
        ->expectsOutputToContain('Package [Vendor\\Acme] initialized successfully!')
        ->assertSuccessful();
})->todo();

it('ask for confirmation before initializing package', function () {
    artisan('init', ['vendor' => 'vendor', 'package' => 'acme', '--description' => 'A new package'])
        ->expectsTable(
            ['Key', 'Value'],
            [
                ['Vendor', 'Vendor'],
                ['Package', 'Package'],
                ['Namespace', 'Vendor\\Acme'],
                ['Description', 'A new package'],
                ['Author', 'Your Name <'],
                ['License', 'MIT'],
            ],
        )
        ->expectsConfirmation('Do you want to proceed?', 'yes')
        ->expectsOutputToContain('Package [Vendor\\Acme] initialized successfully!')
        ->assertSuccessful();
})->todo();
