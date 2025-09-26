<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\AbstractDomains;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;
use App\Lib\Interfaces\Integrations\HostingServer\Account\DomainsInterface;
use Exception;

/**
 * Manages domain operations for hosting server accounts.
 *
 * @method Account account()
 */
class Domains extends AbstractDomains implements DomainsInterface
{
    /**
     * Indicates supported domain types:
     * - `addon` - addon domain
     * - `sub` - subdomain
     * - `alias` - alias
     *
     * @var array|string[]
     */
    protected static array $availableDomainTypes = [
        'addon',
        'sub',
        'alias'
    ];

    /**
     * Indicates supported actions on domains in hosting server:
     * - `set_primary_domain` - allows setting one of the domains as primary domain
     * - `redirect_domain` - allows setting redirect for domain
     * - `update_document_root` - allows updating `document_root` on domain
     *
     * If hosting server doesn't support one of these actions it can remove it.
     *
     * @var array|string[]
     */
    protected static array $supportedActions = [
        'set_primary_domain',
        'redirect_domain',
        'update_document_root'
    ];

    /**
     * Retrieves all domains associated with the hosting account.
     *
     * @return array<array{
     *     domain: string,
     *     type: string,
     *     document_root: string,
     *     redirect_enabled: bool,
     *     redirect_url: null,
     *     aliases: array,
     * }> List of domains containing:
     *   - domain (string): The domain name
     *   - type (string): Domain type (main, addon, sub, alias)
     *   - document_root (string): File system path to domain's document root
     *   - redirect_enabled (bool): Whether redirect is configured
     *   - redirect_url (null): Target URL for redirects (currently not implemented)
     *   - aliases (array): List of domain aliases
     * @throws Exception When domain retrieval fails or server is unreachable
     */
    public function list(): array
    {
        $username = $this->account()->model()->username;
        $apiDomains = $this->account()->server()->api()->listDomains($username);

        $domains = [];

        // For demonstration purposes only
        $existingDomains  = $this->account()->model()->applications->pluck('domain')->toArray();
        foreach ($existingDomains as $domain) {
            $domains[] = [
                'domain' => $domain,
                'type' => 'addon',
                'document_root' => '/public_html',
                'redirect_enabled' => false,
                'redirect_url' => null,
                'aliases' => [],
            ];
        }
        // ---------

        foreach ($apiDomains as $apiDomain) {
            $domains[] = [
                'domain' => $apiDomain['domain'],
                'type' => $apiDomain['type'],
                'document_root' => $apiDomain['document_root'],
                'redirect_enabled' => false,
                'redirect_url' => null,
                'aliases' => [],
            ];
        }

        return $domains;
    }

    /**
     * Retrieves detailed information for a specific domain.
     *
     * @param string $domain The domain name to search for
     * @return array{
     *     domain: string,
     *     type: string,
     *     document_root: string,
     *     redirect_enabled: bool,
     *     redirect_url: null,
     *     aliases: array,
     * }|bool Domain configuration array containing:
     *   - domain (string): The domain name
     *   - type (string): Domain type (main, addon, sub, alias)
     *   - document_root (string): File system path to domain's document root
     *   - redirect_enabled (bool): Whether redirect is configured
     *   - redirect_url (null): Target URL for redirects (currently not implemented)
     *   - aliases (array): List of domain aliases
     *   Returns false if domain is not found
     * @throws Exception When domain search fails or server is unreachable
     */
    public function find(string $domain): array|bool
    {
        $list = $this->list();

        foreach ($list as $item) {
            if ($item['domain'] === $domain) {
                return $item;
            }
        }
        return false;
    }

    /**
     * Verifies if a domain exists on the hosting server.
     *
     * @param string $domain The domain name to verify
     * @return bool True if domain exists, false otherwise
     * @throws Exception When domain verification fails or server is unreachable
     */
    public function exists(string $domain): bool
    {
        return $this->account()->server()->api()->domainExists($domain);
    }


    /**
     * Creates an addon domain on the hosting server.
     *
     * @param string $domain The domain name to add as addon domain
     * @return void
     * @throws Exception When addon domain creation fails, domain already exists, or server error occurs
     */
    public function createAddonDomain(string $domain): void
    {
        $username = $this->account()->model()->username;
        $this->account()->server()->api()->addDomain($username, $domain, 'addon');
    }

    /**
     * Creates a subdomain under an existing parent domain.
     *
     * @param string $domain The subdomain name to create (e.g., "blog")
     * @param string $parentDomain The parent domain for the subdomain (e.g., "example.com")
     * @return void
     * @throws Exception When subdomain creation fails, parent domain doesn't exist, or server error occurs
     */
    public function createSubdomain(string $domain, string $parentDomain): void
    {
        $username = $this->account()->model()->username;
        $this->account()->server()->api()->addDomain($username, $domain, 'subdomain');
    }

    /**
     * Creates an alias domain pointing to an existing target domain.
     *
     * @param string $domain The alias domain name to create
     * @param string $targetDomain The target domain that the alias points to
     * @return void
     * @throws Exception When alias creation fails, target domain doesn't exist, or server error occurs
     */
    public function createAlias(string $domain, string $targetDomain): void
    {
        $username = $this->account()->model()->username;
        $this->account()->server()->api()->addDomain($username, $domain, 'alias');
    }

    /**
     * Updates domain configuration settings on the hosting server.
     *
     * @param string $domain The domain name to update
     * @param array{
     *   document_root: string,
     *   redirect_enabled: boolean,
     *   redirect_url: ?string
     * } $params Configuration parameters containing:
     *   - document_root (string): New file system path for domain's document root
     *   - redirect_enabled (boolean): Whether to enable URL redirection
     *   - redirect_url (?string): Target URL for redirections (nullable)
     * @return void
     * @throws Exception When domain update fails, domain doesn't exist, or invalid parameters provided
     */
    public function update(string $domain, array $params): void
    {
        $username = $this->account()->model()->username;
        $this->account()->server()->api()->updateDomain($username, $domain, $params);
    }

    /**
     * Removes a domain from the hosting server.
     *
     * @param string $domain The domain name to delete
     * @return void
     * @throws Exception When domain deletion fails, domain doesn't exist, or server error occurs
     */
    public function delete(string $domain): void
    {
        $username = $this->account()->model()->username;
        $this->account()->server()->api()->removeDomain($username, $domain);
    }

    /**
     * Updates the document root directory for a specific domain.
     *
     * @param string $domainName The domain name to update
     * @param string $documentRoot The new document root path relative to account root
     * @return void
     * @throws Exception When document root update fails, domain doesn't exist, or path is invalid
     */
    public function updateDocumentRoot(string $domainName, string $documentRoot): void
    {
        $username = $this->account()->model()->username;
        $this->account()->server()->api()->updateDomainDocumentRoot($username, $domainName, $documentRoot);
    }

    /**
     * Retrieves SSL certificate information and configuration for a domain.
     *
     * @param string $domain The domain name to check SSL information for
     * @return array{
     *     certificate_installed: bool,
     *     certificate: array|null,
     *     force_https_redirect: bool,
     *     can_https_redirect: bool
     * } SSL information containing:
     *   - certificate_installed (bool): Whether SSL certificate is installed
     *   - certificate (array|null): Certificate details or null if not installed
     *   - force_https_redirect (bool): Whether HTTPS redirect is enforced
     *   - can_https_redirect (bool): Whether HTTPS redirect is available
     * @throws Exception When SSL information retrieval fails or domain doesn't exist
     */
    public function getSslInfo(string $domain): array
    {
        $apiInfo = $this->account()->server()->api()->getDomainSslInfo($domain);

        // Merge with local SSL certificate info if available
        $certificate = $this->account()->sslCerts()->getDomainInstalledCert($domain);
        if ($certificate !== null) {
            $apiInfo['certificate_installed'] = true;
            $apiInfo['certificate'] = $certificate;
        }

        return $apiInfo;
    }

    /**
     * Enables or disables automatic HTTPS redirect for a domain.
     *
     * @param string $domain The domain name to configure SSL redirect for
     * @param bool $enabled Whether to enable SSL redirect (default: true)
     * @return void
     * @throws Exception When SSL redirect toggle fails, domain doesn't exist, or SSL not available
     */
    public function toggleSslRedirect(string $domain, bool $enabled = true): void
    {
        $username = $this->account()->model()->username;
        $this->account()->server()->api()->toggleDomainSslRedirect($username, $domain, $enabled);
    }


    /**
     * Determines if a domain name can be modified.
     *
     * Checks domain type restrictions to determine whether the domain name
     * can be changed. Only main domains typically support name changes,
     * while addon domains, aliases, and subdomains have fixed names.
     *
     * @param string $domainName The domain name to check modification capability for
     * @return bool True if domain name can be changed, false otherwise
     * @throws Exception When domain lookup fails or domain doesn't exist
     */
    public function canDomainNameBeChanged(string $domainName): bool
    {
        $domain = $this->find($domainName);
        return match ($domain['type']) {
            'main' => true,
            'addon', 'alias', 'sub' => false
        };
    }

    /**
     * Changes the name of an existing domain.
     *
     * @param string $oldDomainName The current domain name to change
     * @param string $newDomainName The new domain name to assign
     * @return void
     * @throws Exception When domain name change fails, domain doesn't exist, or operation not supported
     */
    public function changeDomainName(string $oldDomainName, string $newDomainName): void
    {
        $username = $this->account()->model()->username;
        $this->account()->server()->api()->changeDomainName($username, $oldDomainName, $newDomainName);
    }

    /**
     * Retrieves the document root path for a specific domain.
     *
     * @param string $domainName The domain name to get document root for
     * @return string The document root path relative to account root
     * @throws Exception When domain doesn't exist or document root cannot be determined
     */
    public function getDocumentRoot(string $domainName): string
    {
        $domain = $this->find($domainName);
        if (!$domain) {
            throw new Exception(__('api/messages.domain_not_found'));
        }
        return $domain['document_root'];
    }

    /**
     * Determines if a domain's document root can be modified.
     *
     * @param string $domainName The domain name to check document root modification capability for
     * @return bool True if document root can be changed, false otherwise
     * @throws Exception When domain lookup fails or domain doesn't exist
     */
    public function canDocumentRootBeChanged(string $domainName): bool
    {
        $domain = $this->find($domainName);
        return match ($domain['type']) {
            'main', 'addon' => true,
            'alias', 'sub' => false
        };
    }

    /**
     * Optional. If hosting server supports this action.
     * Configures the specified domain as the primary domain for the account.
     *
     * Changes the account's primary domain, which affects default mail routing,
     * account identification, and may impact other account-level settings.
     * Only one domain can be primary at a time.
     *
     * @param string $domainName The domain name to set as primary domain
     * @return void
     * @throws Exception When primary domain change fails, domain doesn't exist, or operation not supported
     */
    public function setAsPrimaryDomain(string $domainName): void
    {
        $username = $this->account()->model()->username;
        $this->account()->server()->api()->setPrimaryDomain($username, $domainName);
    }


    /**
     * Prepares a domain for staging environment setup.
     *
     * @param string $domainName The domain name to prepare for staging
     * @return string The document root path for staging operations
     * @throws Exception When domain preparation fails or domain doesn't exist
     */
    public function prepare(string $domainName): string
    {
        $domain = $this->find($domainName);
        if (!$domain) {
            $this->createAddonDomain($domainName);
            $domain = $this->find($domainName);
        }

        return $domain['document_root'];
    }
}
