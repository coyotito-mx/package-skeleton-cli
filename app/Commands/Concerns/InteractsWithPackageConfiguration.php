<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

trait InteractsWithPackageConfiguration
{
    use InteractsWithAuthor,
        InteractsWithAuthorEmail,
        InteractsWithCurrentYear,
        InteractsWithDescription,
        InteractsWithLicense,
        InteractsWithMinimumStability,
        InteractsWithNamespace,
        InteractsWithReplacers,
        InteractsWithTemplate,
        InteractsWithType,
        InteractsWithVersion;
}
