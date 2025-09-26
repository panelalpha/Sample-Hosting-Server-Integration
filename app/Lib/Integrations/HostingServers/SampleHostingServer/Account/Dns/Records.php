<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account\Dns;

use App\Lib\Integrations\DnsServers\AbstractDnsServer\AbstractRecords;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;
use App\Lib\Interfaces\Integrations\DnsServer\RecordsInterface;

/**
 * Manages DNS record operations within DNS zones.
 *
 * @method Account account()
 */
class Records extends AbstractRecords implements RecordsInterface
{
    /**
     * Supported DNS record types for this server integration.
     *
     * @var array<string>
     */
    protected static array $recordTypes = ['A', 'AAAA', 'CNAME', 'DS', 'MX', 'NS', 'PTR', 'SRV', 'TXT'];

    /**
     * Returns all DNS records for the specified zone.
     *
     * Retrieves all DNS records configured within a DNS zone, including their
     * names, types, TTL values, and content data.
     *
     * - name (string): The DNS record name (hostname or domain)
     * - type (string): The DNS record type (A, AAAA, CNAME, MX, etc.)
     * - ttl (string): Time-to-live value in seconds
     * - line (string): The record identifier used for updates and deletions
     * - content (string): The record value or target
     * - rdata (string): Raw record data (same as content)
     *
     * @param string $zoneId The zone identifier on the remote DNS server
     * @return array<array{name: string, type: string, ttl: string, line: string, content: string, rdata: string}> List of DNS records
     */
    public function list(string $zoneId): array
    {
        return $this->account()->server()->api()->listDnsRecords($this->account()->model()->username, $zoneId);
    }

    /**
     * Creates a new DNS record in the specified zone.
     *
     * Adds a new DNS record to the zone with the provided configuration parameters.
     * The record will be immediately active on the authoritative nameservers.
     *
     * @param string $zoneId The zone identifier on the remote DNS server
     * @param array{name: string, type: string, ttl?: string, content: string} $params Record configuration:
     *     - 'name' (string) The DNS record name
     *     - 'type' (string) The DNS record type
     *     - 'ttl' (string, optional) Time-to-live in seconds
     *     - 'content' (string) The record value or target
     * @return void
     */
    public function create(string $zoneId, array $params): void
    {
        $this->account()->server()->api()->createDnsRecord($this->account()->model()->username, $zoneId, $params);
    }

    /**
     * Updates an existing DNS record in the specified zone.
     *
     * Modifies the configuration of an existing DNS record identified by its
     * record name or line identifier.
     *
     * @param string $zoneId The zone identifier on the remote DNS server
     * @param int|string $name The record name or line identifier to update
     * @param array{type?: string, ttl?: string, content?: string} $params Update parameters:
     *     - 'type' (string, optional) New DNS record type
     *     - 'ttl' (string, optional) New time-to-live value
     *     - 'content' (string, optional) New record value or target
     * @return void
     */
    public function update(string $zoneId, int|string $name, array $params): void
    {
        $this->account()->server()->api()->updateDnsRecord($this->account()->model()->username, $zoneId, (string)$name, $params);
    }

    /**
     * Deletes a DNS record from the specified zone.
     *
     * Removes the DNS record identified by its record name or line identifier
     * from the zone configuration.
     *
     * @param string $zoneId The zone identifier on the remote DNS server
     * @param int|string $name The record name or line identifier to delete
     * @return void
     */
    public function delete(string $zoneId, int|string $name): void
    {
        $this->account()->server()->api()->deleteDnsRecord($this->account()->model()->username, $zoneId, (string)$name);
    }
}
