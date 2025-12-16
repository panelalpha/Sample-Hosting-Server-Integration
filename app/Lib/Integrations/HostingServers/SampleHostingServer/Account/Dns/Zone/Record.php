<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account\Dns\Zone;

use App\Lib\Helpers\DnsRecordHelper;
use App\Lib\Integrations\DnsServers\AbstractDnsServer\Zone\AbstractRecord;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account\Dns;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account\Dns\Zone;
use App\Lib\Interfaces\Integrations\DnsServer\Zone\RecordInterface;
use Exception;

/**
 * @method Dns dnsServer()
 * @method Zone zone()
 */
class Record extends AbstractRecord implements RecordInterface
{
    /**
     * Supported DNS record types for this server integration.
     *
     * @var string[]
     */
    protected static array $supportedTypes = ['A', 'AAAA', 'CNAME', 'DS', 'MX', 'NS', 'PTR', 'SRV', 'TXT'];

    /**
     * Optional.
     * DNS server supports setting proxy mode on DNS records.
     *
     * @var bool
     */
    protected static bool $allowProxy = false;

    /**
     * Creates a new DNS record in the specified zone.
     *
     * Adds a new DNS record to the zone with the provided configuration parameters.
     * The record will be immediately active on the authoritative nameservers.
     *
     * @param array{name: string, type: string, ttl?: string, content: string} $params Record configuration:
     *     - 'name' (string) The DNS record name
     *     - 'type' (string) The DNS record type
     *     - 'ttl' (string, optional) Time-to-live in seconds
     *     - 'content' (string) The record value or target
     * @return void
     * @throws Exception
     */
    public function create(array $params): void
    {
        $zoneName = $this->model()->name;
        $zoneId = $this->model()->getRemoteId();

        $this->dnsServer()->account()->server()->api()->createDnsRecord($this->dnsServer()->account()->model()->username, $zoneId, $params);
    }

    /**
     * Updates an existing DNS record in the specified zone.
     *
     * Modifies the configuration of an existing DNS record identified by its
     * record name or line identifier.
     *
     * @param string $line The record name or line identifier to update
     * @param array{type?: string, ttl?: string, content?: string} $params Update parameters:
     *     - 'type' (string, optional) New DNS record type
     *     - 'ttl' (string, optional) New time-to-live value
     *     - 'content' (string, optional) New record value or target
     * @return void
     */
    public function update(string $line, array $params): void
    {
        $zoneName = $this->model()->name;
        $zoneId = $this->model()->getRemoteId();

        $this->dnsServer()->account()->server()->api()->updateDnsRecord($this->dnsServer()->account()->model()->username, $zoneId, $line, $params);
    }

    /**
     * Deletes a DNS record from the specified zone.
     *
     * Removes the DNS record identified by its record name or line identifier
     * from the zone configuration.
     *
     * @param string $line The record name or line identifier to delete
     * @return void
     */
    public function delete(string $line): void
    {
        $zoneName = $this->model()->name;
        $zoneId = $this->model()->getRemoteId();

        $this->dnsServer()->account()->server()->api()->deleteDnsRecord($this->dnsServer()->account()->model()->username, $zoneId, $line);
    }
}