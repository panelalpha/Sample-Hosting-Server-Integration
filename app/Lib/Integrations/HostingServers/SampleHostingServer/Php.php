<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\AbstractPhp;
use App\Lib\Integrations\HostingServers\SampleHostingServer;
use Exception;

/**
 * Manages PHP version operations for hosting servers.
 *
 * @method SampleHostingServer server()
 */
class Php extends AbstractPhp
{
    /**
     * Retrieves all available PHP versions supported by the hosting server.
     *
     * @return array<array{
     *     label: string,
     *     value: string
     * }> List of available PHP versions containing:
     *   - label (string): Human-readable PHP version name (e.g., "PHP 8.2")
     *   - value (string): Technical version identifier (e.g., "8.2")
     * @throws Exception When PHP version retrieval fails or server is unreachable
     */
    public function getAvailableVersions(): array
    {
        $list = $this->server()->api()->getPhpVersions();

        return array_map(function ($version) {
            return [
                'label' => $version['name'],
                'value' => $version['version'],
            ];
        }, $list);
    }

    /**
     * Retrieves the currently configured PHP version for a specific domain.
     *
     * @param string $domainName The domain name to check PHP version for
     * @return array{
     *     label: string,
     *     value: string
     * }|null PHP version information containing:
     *   - label (string): Human-readable PHP version name (e.g., "PHP 8.2")
     *   - value (string): Technical version identifier (e.g., "8.2")
     *   Returns null if domain doesn't exist or PHP version cannot be determined
     * @throws Exception When version retrieval fails or domain is inaccessible
     */
    public function getDomainPhpVersion(string $domainName): ?array
    {
        $result = $this->server()->api()->getDomainPhpVersion($domainName);

        return [
            'label' => $result['name'],
            'value' => $result['version'],
        ];
    }

    /**
     * Configures the PHP version for a specific domain.
     *
     * @param string $domainName The domain name to update PHP version for
     * @param string $version The PHP version identifier to set (e.g., "8.2")
     * @return void
     * @throws Exception When version update fails, version is invalid, or domain doesn't exist
     */
    public function setDomainPhpVersion(string $domainName, string $version): void
    {
        $this->server()->api()->setDomainPhpVersion($domainName, $version);
    }
}
