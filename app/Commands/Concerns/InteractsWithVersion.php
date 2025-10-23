<?php

namespace App\Commands\Concerns;

use App\Replacer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

trait InteractsWithVersion
{
    /**
     * The semver pattern.
     *
     * @see https://semver.org/#is-there-a-suggested-regular-expression-regex-to-check-a-semver-string
     */
    protected string $semverPattern = '/^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

    public function bootPackageInteractsWithVersion(): void
    {
        $this->addReplacers([
            Replacer\VersionReplacer::class => fn (): string => $this->getPackageVersion(),
        ]);

        $this->addOption('package-version', mode: InputOption::VALUE_OPTIONAL, description: 'The package version', default: '0.0.1');
    }

    public function getPackageVersion(): string
    {
        $version = $this->option('package-version');

        if (! Str::isMatch($this->semverPattern, $version)) {
            throw new \RuntimeException('Invalid package version format, please follow the semver pattern.');
        }

        return Str::lower($version);
    }
}
