<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\AbstractInternalDnsServer;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;
use App\Lib\Interfaces\Integrations\DnsServerInterface;

/**
 * Manages DNS server operations for hosting accounts.
 *
 *  Required when the hosting server provides its own internal dns server.
 *  Controlled by the `$hasInternalDnsServer` flag in the main integration class.
 *  If the hosting server does not support internal dns server, remove this class.
 *
 * @method Account account()
 */
class Dns extends AbstractInternalDnsServer implements DnsServerInterface
{
    /**
     * Returns all DNS zones managed by the hosting account.
     *
     * Retrieves all DNS zones configured on the server for the hosting account.
     * Each zone includes its name and remote server identification details.
     *
     * - name (string): The DNS zone domain name
     * - details (array): Zone metadata including remote server identifiers, eg. `remote_id`
     *   - remote_id (string): The zone identifier on the remote DNS server
     *
     * @return array<array{name: string, details: array{remote_id: string}}> List of DNS zones with their details
     */
    public function listZones(): array
    {
        $username = $this->account()->model()->username;

        return $this->account()->server()->api()->listDnsZones($username);
    }

    /**
     * Finds a specific DNS zone by domain name.
     *
     * Searches for a DNS zone matching the specified domain name and returns
     * its configuration details if found.
     *
     * @param string $name The domain name to search for
     * @return array{name: string, details: array{remote_id: string}}|null Zone details if found, null otherwise
     */
    public function findZone(string $name): ?array
    {
        $username = $this->account()->model()->username;

        return $this->account()->server()->api()->findDnsZone($username, $name);
    }

    /**
     * Returns the authoritative nameservers for the hosting account.
     *
     * Provides the list of nameservers that should be configured at the domain
     * registrar to delegate DNS authority to this hosting server.
     *
     * @return array<string> List of nameserver hostnames
     */
    public function listStaticNameservers(): array
    {
        $username = $this->account()->model()->username;

        return $this->account()->server()->api()->listDnsNameservers($username);
    }
}
