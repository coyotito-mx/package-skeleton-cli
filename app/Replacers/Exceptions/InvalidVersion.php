<?php

namespace App\Replacers\Exceptions;

use Illuminate\Support\Str;

/**
 * Exception thrown when a version string is not in a valid semantic versioning format.
 */
final class InvalidVersion extends InvalidFormatException
{
    /*
     * The regex pattern for validating semantic versioning (SemVer) format.
     *
     * @link https://semver.org/#is-there-a-suggested-regular-expression-regex-to-check-a-semver-string
     */
    public static string $pattern = '/^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

    /**
     * Validate the given version string.
     *
     * @param  string  $value  The version string to validate.
     *
     * @throws self If the version string is not valid format based on the SemVer specification.
     */
    public static function validate(string $value): void
    {
        if (! Str::isMatch(self::$pattern, $value)) {
            throw new self("The version '$value' is not a valid semantic version.");
        }
    }
}
