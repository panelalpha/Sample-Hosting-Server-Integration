<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\AbstractPhp;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;
use App\Lib\Interfaces\Integrations\HostingServer\Account\PhpInterface;

/**
 * Manages PHP configuration settings for hosting accounts.
 *
 * @method Account account()
 */
class Php extends AbstractPhp implements PhpInterface
{
    /**
     * Indicates for which domain types PHP settings can be applied.
     *
     * Determines whether PHP configuration is available for the specified domain type.
     * By default returns `true` for all domain types, but this implementation restricts
     * it to main domains only.
     *
     * @param string $domainType The domain type ('main_domain', 'addon', 'sub')
     * @return bool True if PHP settings can be applied to this domain type, false otherwise
     */
    public function shouldBeSet(string $domainType): bool
    {
        return $domainType === 'main_domain';
    }

    /**
     * Sets PHP configuration settings for a domain.
     *
     * Applies the specified PHP directives and their values to the domain.
     *
     * @param string $domain The domain name to configure
     * @param array<array{
     *   directive: string,
     *   value: string
     * }> $phpSettings PHP directives to set, where each entry contains:
     *     - 'directive' (string) The PHP directive name
     *     - 'value' (string) The value to set for the directive
     * @return void
     */
    public function setConfiguration(string $domain, array $phpSettings): void
    {
        $this->account()->server()->api()->setDomainPhpSettings($domain, $phpSettings);
    }

    /**
     * Indicates if PHP settings can be configured for a domain by type.
     *
     * Determines whether PHP configuration interface should be available for the
     * specified domain type. By default returns `true` for all domain types.
     *
     * @param string $domainType The domain type ('main_domain', 'addon', 'sub')
     * @return bool True if PHP settings are configurable for this domain type, false otherwise
     */
    public function isConfigurable(string $domainType): bool
    {
        return true;
    }

    /**
     * Returns PHP configuration settings for a domain.
     *
     * Retrieves current PHP directives and their values for the specified domain,
     * including available options for select-type directives.
     *
     * This method is optional.
     * If it is not implemented, it will not be possible to change the PHP settings in the Client Area.
     *
     * Field descriptions for returned settings:
     * - directive (string): The PHP directive name (e.g., 'display_errors', 'memory_limit')
     * - value (string): The current value of the directive
     * - type (string): The input type ('select' or 'text')
     * - options (array, optional): Available options for select-type directives
     *
     * @param string $domain The domain name to get settings for
     * @return array<array{
     *     directive: string,
     *     value: string,
     *     type: string,
     *     options?: array<string>
     * }> List of PHP settings with their current values and configuration options
     */
    public function getPhpSettings(string $domain): array
    {
        return $this->account()->server()->api()->getDomainPhpSettings($domain);
    }
}
