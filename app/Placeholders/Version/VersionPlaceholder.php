<?php

declare(strict_types=1);

namespace App\Placeholders\Version;

use App\Placeholders\BasePlaceholder;
use App\Placeholders\Exceptions\InvalidVersionException;

/**
 * Replacer for `author` placeholders
 *
 * @see \App\Placeholders\Modifiers for supported modifiers.
 */
class VersionPlaceholder extends BasePlaceholder
{
    public function __construct(array $modifiers = [])
    {
        if (count($modifiers) > 1) {
            throw new \InvalidArgumentException(static::class.'::class cannot use more than one modifier at the time');
        }

        parent::__construct($modifiers);
    }

    #[\Override]
    protected static function getDefaultModifiers(): array
    {
        return [
            Modifiers\MajorModifier::class,
            Modifiers\MinorModifier::class,
            Modifiers\PatchModifier::class,
            Modifiers\PrefixModifier::class
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidVersionException if the version provided is not valid
     */
    #[\Override]
    public function preProcess(string $replacement): string
    {
        InvalidVersionException::validate($replacement);

        return parent::preProcess($replacement);
    }

    public static function getName(): string
    {
        return 'version';
    }
}
