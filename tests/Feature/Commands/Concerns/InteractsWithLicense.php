<?php

declare(strict_types=1);

use App\Commands\Concerns\InteractsWithLicense;

it('replace license', function () {
    configurable_testing_command(<<<'JSON'
    {
      "name": "vendor/package",
      "description": "lorem ipsum dolor it sit amet",
      "license": "{{license}}",
      "require": {
        "vendor/acme": "^1.0.0",
        "vendor/action": "^2.0.0"
      }
    }
    JSON, InteractsWithLicense::class);

    $this->artisan('demo')
        ->expectsOutput(<<<'JSON'
        {
          "name": "vendor/package",
          "description": "lorem ipsum dolor it sit amet",
          "license": "MIT",
          "require": {
            "vendor/acme": "^1.0.0",
            "vendor/action": "^2.0.0"
          }
        }
        JSON)
        ->assertSuccessful();
});
