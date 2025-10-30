<?php

declare(strict_types=1);

namespace App\Commands\Concerns;

use App\Replacer;
use Symfony\Component\Console\Input\InputOption;

trait InteractsWithType
{
    protected array $packageTypes = [
        'library',
        'project',
        'metapackage',
        'composer-plugin',
        'symfony-bundle',
        'wordpress-plugin',
        'wordpress-theme',
        'drupal-module',
        'drupal-theme',
        'drupal-profile',
        'magento-module',
        'magento-theme',
        'typo3-cms-extension',
    ];

    public function bootInteractsWithType(): void
    {
        $this->addReplacers([
            Replacer\TypeReplacer::class => fn (): string => $this->getPackageType(),
        ]);

        $this->addOption('type', mode: InputOption::VALUE_OPTIONAL, description: 'The package type', default: 'library');
    }

    public function getPackageType(): string
    {
        $type = $this->option('type');

        if (! in_array($type, $this->packageTypes)) {
            throw new \RuntimeException('Invalid package type.');
        }

        return $type;
    }
}
