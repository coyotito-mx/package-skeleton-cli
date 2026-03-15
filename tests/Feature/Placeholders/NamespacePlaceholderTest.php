<?php

declare(strict_types=1);

use App\Placeholders\Exceptions\InvalidNamespaceException;
use App\Placeholders\Namespace\NamespacePlaceholder;

it('process value', function (): void {
    $placeholder = new NamespacePlaceholder;

    expect($placeholder)->process('Acme\\Package')->toBe('Acme\\Package');
});

it('fail to process non-valid namespace', function (): void {
    $placeholder = new NamespacePlaceholder;

    expect($placeholder)->process('Acme/Package');
})->throws(InvalidNamespaceException::class);

it('process value lower modifier', function (): void {
    $placeholder = new NamespacePlaceholder(['lower']);

    expect($placeholder)->process('Acme\\Package')->toBe('acme\\package');
});

it('process value upper modifier', function (): void {
    $placeholder = new NamespacePlaceholder(['upper']);

    expect($placeholder)->process('Acme\\Package')->toBe('ACME\\PACKAGE');
});

it('process value slug modifier', function (): void {
    $placeholder = new NamespacePlaceholder(['slug']);

    expect($placeholder)->process('AcmeVendor\\Package')->toBe('acme-vendor\\package');
});

it('process value escaping namespace', function (): void {
    $placeholder = new NamespacePlaceholder(['escape']);

    expect($placeholder)->process('AcmeVendor\\Package')->toBe('AcmeVendor\\\\Package');
});

it('fail to escape already escaped namespace', function (): void {
    $placeholder = new NamespacePlaceholder(['escape', 'escape']);

    expect($placeholder)->process('AcmeVendor\\Package')->toBe('AcmeVendor\\\\Package');
});

it('process value reversing namespace separator', function (): void {
    $placeholder = new NamespacePlaceholder(['reverse']);

    expect($placeholder)->process('AcmeVendor\\Package')->toBe('AcmeVendor/Package');
});

it('cannot escape reversed namespace separator', function (): void {
    $placeholder = new NamespacePlaceholder(['reverse', 'escape']);

    expect($placeholder)->process('AcmeVendor\\Package')->toBe('AcmeVendor/Package');
});
