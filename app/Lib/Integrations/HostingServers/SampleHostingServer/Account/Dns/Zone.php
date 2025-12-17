<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account\Dns;

use App\Lib\Integrations\DnsServers\AbstractDnsServer\AbstractZone;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account\Dns;
use App\Lib\Interfaces\Integrations\DnsServer\ZoneInterface;

/**
 * @method Dns dnsServer()
 */
class Zone extends AbstractZone implements ZoneInterface
{
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
     * @return array<array{name: string, type: string, ttl: string, line: string, content: string, rdata: string}> List of DNS records
     */
    public function listRecords(): array
    {
        $zoneName = $this->model()->name;
        $zoneId = $this->model()->getRemoteId();

        return $this->dnsServer()->account()->server()->api()->listDnsRecords($this->dnsServer()->account()->model()->username, $zoneId);
    }

    /**
     * Returns DNS records that define the zone configuration.
     *
     * This method returns a list of records that should exist in every newly
     * created DNS zone to ensure it is valid and operational on the
     * nameserver. The exact set can vary per provider,
     * but the structure is always the same:
     *
     * - name (string): Fully-qualified record name (e.g. "example.com." or "@").
     * - type (string): Record type (e.g. "SOA", "NS").
     * - value (string): Record value/content (e.g. target nameserver, SOA rdata).
     *
     * @return array<array{name: string, type: string, value: string}> List of configuration records to apply to the zone
     */
    public function getConfigurationRecords(): array
    {
        $zoneName = $this->model()->name;
        $zoneId = $this->model()->getRemoteId();

        return $this->dnsServer()->account()->server()->api()->listZoneConfigurationRecords($this->dnsServer()->account()->model()->username, $zoneId);
    }
}