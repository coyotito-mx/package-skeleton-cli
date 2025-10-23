<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Exceptions;
use Composer\Spdx\SpdxLicenses;
use Illuminate\Support\Facades\Http;
use RuntimeException;

trait WithLicense
{
    private SpdxLicenses $licenses;

    private function getLicenses(): SpdxLicenses
    {
        if (empty($this->licenses)) {
            $this->licenses = new SpdxLicenses;
        }

        return $this->licenses;
    }

    public function validateLicense(string $identifier): bool
    {
        return $this->getLicenses()->validate($identifier);
    }

    public function getLicenseDefinition(string $identifier): ?string
    {
        if (! $this->validateLicense($identifier)) {
            return null;
        }

        $license = $this->getLicenses()->getLicenseByIdentifier($identifier);

        try {
            $definition = $this->requestLicenseDefinition($license[2]);
        } catch (\Throwable) {
            throw new Exceptions\LicenseDefinitionNotFound($identifier);
        }

        return $definition;
    }

    /**
     * Requests the license definition from the given URL.
     *
     * @throws \Illuminate\Http\Client\RequestException in case the request fails.
     * @throws \RuntimeException in case the license definition could not be found in the HTML.
     */
    private function requestLicenseDefinition(string $url): string
    {
        $html = Http::get($url)->throw()->body();

        $definition = $this->getLicenseDefinitionFromHtml($html);

        return $definition;
    }

    /**
     * @throws RuntimeException in case the license definition could not be found in the HTML.
     */
    private function getLicenseDefinitionFromHtml(string $html): string
    {
        $dom = new \DOMDocument;
        $dom->loadHTML($html);

        $xpath = new \DOMXPath($dom);
        $node = $xpath->query('//*[normalize-space(@class) = "license-text"]')->item(0);

        if ($node === null) {
            throw new \RuntimeException('No license definition found in the HTML');
        }

        return $node->nodeValue;
    }
}
