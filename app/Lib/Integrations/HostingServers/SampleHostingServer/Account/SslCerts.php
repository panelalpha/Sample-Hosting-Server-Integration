<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\AbstractSslCerts;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;
use App\Lib\Interfaces\Integrations\HostingServer\Account\SslCertsInterface;

/**
 * Manages SSL certificate operations for hosting accounts.
 *
 * @method Account account()
 * @psalm-import-type DomainInstalledCert from SslCertsInterface
 */
class SslCerts extends AbstractSslCerts implements SslCertsInterface
{
    /**
     * Determines if SSL certificate support is available for subdomains.
     *
     * Controls whether SSL certificates can be installed and managed for
     * subdomain entries in addition to primary domains. This affects the
     * availability of SSL features in subdomain management interfaces.
     * By default, this method returns true.
     *
     * @return bool True if subdomain SSL certificates are supported, false otherwise
     */
    public function sslSubdomainsSupported(): bool
    {
        return true;
    }

    /**
     * Retrieves all domains with active SSL certificates on the hosting account.
     *
     * @return array<array{
     *     domain: string,
     *     domains: array<string>,
     *     common_name: string,
     *     issuer_name: string,
     *     not_before: string,
     *     not_after: string,
     *     self_signed: bool,
     *     auto_installed: bool,
     *     name_match: bool
     * }> List of domains with SSL certificates containing:
     *   - domain (string): Primary domain name
     *   - domains (array): All domains covered by the certificate
     *   - common_name (string): Certificate common name
     *   - issuer_name (string): Certificate authority that issued the certificate
     *   - not_before (string): Certificate validity start date
     *   - not_after (string): Certificate expiration date
     *   - self_signed (bool): Whether the certificate is self-signed
     *   - auto_installed (bool): Whether the certificate was automatically provisioned
     *   - name_match (bool): Whether the certificate matches the domain name
     * @throws Exception When SSL domain retrieval fails or account doesn't exist
     */
    public function sslDomains(): array
    {
        $username = $this->account()->model()->username;

        return $this->account()->server()->api()->getSslDomains($username);
    }

    /**
     * Retrieves detailed SSL certificate information for a specific domain.
     *
     * Returns comprehensive certificate data including the full certificate
     * content, validity information, and metadata.
     *
     * @param string $domain The domain name to retrieve SSL certificate information for
     * @return array{common_name: string, domain: string, domains: array<string>, issuer_name: string, not_before: string, not_after: string, self_signed: bool, name_match: bool, certificate_text: string}|null Certificate details containing:
     *   - common_name (string): Certificate common name
     *   - domain (string): Primary domain name
     *   - domains (array): All domains covered by the certificate
     *   - issuer_name (string): Certificate authority that issued the certificate
     *   - not_before (string): Certificate validity start date
     *   - not_after (string): Certificate expiration date
     *   - self_signed (bool): Whether the certificate is self-signed
     *   - name_match (bool): Whether the certificate matches the domain name
     *   - certificate_text (string): Complete certificate content in PEM format
     *   Returns null if no certificate is installed for the domain
     * @throws Exception When certificate retrieval fails or account doesn't exist
     */
    public function getDomainInstalledCert(string $domain): ?array
    {
        $username = $this->account()->model()->username;

        return $this->account()->server()->api()->getDomainSslCertificate($username, $domain);
    }

    /**
     * Retrieves the SSL certificate content in a simplified format.
     *
     * @param int|string $id The domain identifier (name or ID)
     * @return array{certificate: string} Simplified certificate data containing:
     *   - certificate (string): Complete certificate content in PEM format
     * @throws Exception When certificate retrieval fails or domain doesn't exist
     */
    public function get(int|string $id): array
    {
        $cert = $this->getDomainInstalledCert($id);
        return [
            'certificate' => $cert['certificate_text'] ?? ''
        ];
    }

    /**
     * Installs a custom SSL certificate on the specified domain.
     *
     * Deploys a user-provided SSL certificate including the certificate chain,
     * private key, and optional CA bundle to the hosting server.
     *
     * @param string $domain The domain name to install the SSL certificate on
     * @param string $cert The SSL certificate content in PEM format
     * @param string $key The private key content in PEM format
     * @param string $cabundle Optional CA bundle/intermediate certificates in PEM format
     * @return void
     * @throws Exception When certificate installation fails, certificate is invalid, or domain doesn't exist
     */
    public function install(string $domain, string $cert, string $key, string $cabundle = ""): void
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->installSslCertificate($username, $domain, $cert, $key, $cabundle);
    }

    /**
     * Optional. If hosting account supports automatic SSL certificate provisioning.
     * Retries automatic SSL certificate provisioning for a domain.
     *
     * Triggers a new attempt to automatically provision an SSL certificate by hosting server
     * from a certificate authority (typically Let's Encrypt).
     *
     * This method only applies to automatic certificate provisioning and
     * will not affect manually installed custom certificates.
     *
     * @param string $domain The domain name to retry SSL certificate provisioning for
     * @return void
     * @throws Exception When retry operation fails or automatic provisioning is not available
     */
    public function retrySslCertificateProvisioning(string $domain): void
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->retrySslCertificateProvisioning($username, $domain);
    }
}
