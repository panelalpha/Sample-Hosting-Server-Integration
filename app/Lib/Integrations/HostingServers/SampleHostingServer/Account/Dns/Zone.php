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
}