<?php

namespace App\Commands\Contracts;

interface HasPackageConfiguration
{
    public function getPackageVendor(): string;

    public function getPackageName(): string;

    public function getPackageDescription(): string;

    public function getPackageAuthorName(): string;

    public function getPackageLicense(): string;

    public function getPackageNamespace(): string;

    public function getPackageVersion(): string;

    public function getPackageMinimumStability(): string;

    public function getPackageType(): string;

    public function getPackageReplacers(): array;

    public function addReplacers(array $replacers): void;
}
