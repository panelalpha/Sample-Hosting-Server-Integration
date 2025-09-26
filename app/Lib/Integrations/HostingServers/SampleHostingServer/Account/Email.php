<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\AbstractInternalEmailServer;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;
use App\Lib\Interfaces\Integrations\InternalEmailServerInterface;

/**
 * Manages internal email server functionality for hosting accounts.
 *
 * Required when the hosting server provides its own internal email server.
 * Controlled by the `$hasInternalEmailServer` flag in the main integration class.
 * If the hosting server does not support internal email, remove this class.
 *
 * @method Account account()
 */
class Email extends AbstractInternalEmailServer implements InternalEmailServerInterface
{
    /**
     * Returns email domains configured for the hosting account.
     *
     * - domain (string): The email domain name
     * - details (array): Additional domain configuration and status information
     *
     * @return array<array{domain: string, details: array}> List of email domains with their configuration details
     */
    public function listDomains(): array
    {
        $domains = $this->account()->server()->api()->listEmailDomains($this->account()->model()->username);

        return array_map(function ($domain) {
            return [
                'domain' => $domain['domain'],
                'details' => $domain['details'] ?? []
            ];
        }, $domains);
    }
}
