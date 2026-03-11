<?php

declare(strict_types=1);

use App\Placeholders\Exceptions\InvalidVersionException;
use App\Placeholders\Version\VersionPlaceholder;

it('process value', function (): void {
    $placeholder = new VersionPlaceholder;

    expect($placeholder)->process('1.0.0')->toBe('1.0.0');
});

it('fail to process non-valid version', function (): void {
    $placeholder = new VersionPlaceholder;

    expect($placeholder)->process('1.0');
})->throws(InvalidVersionException::class);

it('process version and get the major version', function (): void {
    $placeholder = new VersionPlaceholder(['major']);

    expect($placeholder)->process('1.2.3')->toBe('1');
});

it('process version and get the minor version', function (): void {
    $placeholder = new VersionPlaceholder(['minor']);

    expect($placeholder)->process('1.2.3')->toBe('2');
});

it('process version and get the patch version', function (): void {
    $placeholder = new VersionPlaceholder(['patch']);

    expect($placeholder)->process('1.2.3')->toBe('3');
});

it('fail to apply more than one modifier', function (): void {
    $placeholder = new VersionPlaceholder(['major', 'minor']);

    expect($placeholder)->process('1.0');
})->throws(\InvalidArgumentException::class);
