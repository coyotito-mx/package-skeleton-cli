<?php

declare(strict_types=1);

namespace Tests\Feature\Commands\Concerns;

use App\Commands\Concerns\InteractsWithDescription;

it('replace description', function () {
    testingReplacersInCommand(<<<'JSON'
    {
      "name": "vendor/package",
      "description": "{{description}}",
      "require": {
        "vendor/acme": "^1.0.0",
        "vendor/action": "^2.0.0"
      }
    }
    JSON, InteractsWithDescription::class);

    $description = 'Lorem ipsum dolor Sit amet, consectetur Adipiscing elit.';

    $this->artisan('demo', ['description' => $description])
        ->expectsOutput(<<<JSON
        {
          "name": "vendor/package",
          "description": "lorem ipsum dolor sit amet, consectetur adipiscing elit.",
          "require": {
            "vendor/acme": "^1.0.0",
            "vendor/action": "^2.0.0"
          }
        }
        JSON)->assertSuccessful();
});

it('ask for description', function () {
    testingReplacersInCommand(<<<'JSON'
    {
      "name": "vendor/package",
      "description": "{{description}}",
      "require": {
        "vendor/acme": "^1.0.0",
        "vendor/action": "^2.0.0"
      }
    }
    JSON, InteractsWithDescription::class);

    $description = 'Lorem ipsum dolor Sit amet, consectetur Adipiscing elit.';

    $this->artisan('demo')
        ->expectsQuestion("What is the package description?", $description)
        ->expectsOutput(<<<JSON
        {
          "name": "vendor/package",
          "description": "lorem ipsum dolor sit amet, consectetur adipiscing elit.",
          "require": {
            "vendor/acme": "^1.0.0",
            "vendor/action": "^2.0.0"
          }
        }
        JSON)->assertSuccessful();
});
