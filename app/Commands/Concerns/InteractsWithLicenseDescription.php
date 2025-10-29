<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use Illuminate\Support\Facades\File;

use function Illuminate\Filesystem\join_paths;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\warning;

trait InteractsWithLicenseDescription
{
    protected string $licenseStub = 'license.md.stub';

    protected function bootPackageInteractsWithLicenseDescription(): void
    {
        $this
            ->addOption('replace-license', description: 'Force replace the `LICENSE.md` file')
            ->addOption('skip-license-generation', description: 'Skip license generation');
    }

    public function getPackageLicenseDescription(): string
    {
        $filepath = base_path(
            join_paths('stubs', $this->licenseStub)
        );

        return File::get($filepath);
    }

    protected function generateLicenseFile(): bool
    {
        if ($this->skipLicenseGeneration()) {
            warning('Skip file generation');

            return false;
        }

        if (File::exists($this->getPackageLicensePath())) {
            warning('The `LICENSE.md` file already exists');

            if (! $this->option('replace-license') && ! confirm('Do you want to replace replace the `LICENSE.md` file?')) {
                return false;
            }
        }

        return $this->replaceLicense();
    }

    public function skipLicenseGeneration(): bool
    {
        return (bool) $this->option('skip-license-generation');
    }

    protected function replaceLicense(): bool
    {
        return (bool) File::put($this->getPackageLicensePath(), $this->getPackageLicenseDescription());
    }

    protected function getPackageLicensePath(): string
    {
        return $this->getPackagePath('LICENSE.md');
    }
}
