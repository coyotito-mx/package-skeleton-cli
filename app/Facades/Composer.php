<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;


/**
 * Facade for Composer operations that includes license validation functionality.
 *
 * This facade provides a simple interface for running Composer commands and handling
 * license validations through the associated trait.
 *
 * @method static void install() Installs the project dependencies using Composer.
 * @method static void require(string $package, bool $dev = false) Adds a package dependency with an option for development.
 * @method static void remove(string $package, bool $dev = false) Removes a package dependency with an option for development.
 * @method static void update(string $package, bool $dev = false) Updates a package dependency with an option for development.
 * @method static void dumpAutoload(bool $optimize = false) Regenerates the Composer autoloader, with an option to optimize.
 * @method static void runProcess(string|array $command) Executes a given Composer command using the process facade.
 *
 * @method static bool validateLicense(string $identifier) Validates if the provided license identifier is valid.
 * @method static ?string getLicenseDefinition(string $identifier) Retrieves the detailed license definition from the SPDX source.
 * @method static string requestLicenseDefinition(string $url) Makes an HTTP request to obtain the license definition from the given URL.
 * @method static string getLicenseDefinitionFromHtml(string $html) Extracts the license definition text from retrieved HTML content.
 */
class Composer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'composer';
    }
}
