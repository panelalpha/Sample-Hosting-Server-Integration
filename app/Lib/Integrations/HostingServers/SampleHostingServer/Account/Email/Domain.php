<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account\Email;

use App\Lib\Integrations\EmailServers\AbstractEmailServer\AbstractDomain;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account\Email;
use App\Lib\Interfaces\Integrations\EmailServer\DomainInterface;
use App\Models\EmailDomain;

/**
 * Manages email domain operations within hosting account email services.
 *
 * @method Email emailServer()
 * @method EmailDomain model()
 */
class Domain extends AbstractDomain implements DomainInterface
{
    /**
     * Returns all email accounts configured for the domain.
     *
     * - email (string): The complete email address
     * - disk_usage (int): Current disk usage in MB
     * - disk_quota (int|string): Disk quota in MB or 'unlimited' for no limit
     *
     * @return array<array{email: string, disk_usage: int, disk_quota: int|string}> List of email accounts with usage details
     */
    public function listAccounts(): array
    {
        $domain = $this->model()->domain;
        $username = $this->emailServer()->account()->model()->username;

        $accounts = $this->emailServer()->account()->server()->api()->listEmailAccounts($username, $domain);

        return array_map(function ($account) {
            return [
                'email' => $account['email'],
                'disk_usage' => $account['disk_usage'],
                'disk_quota' => $account['disk_quota'],
            ];
        }, $accounts);
    }

    /**
     * Returns all email forwarders configured for the domain.
     *
     * - email (string): The source email address that receives forwarded mail
     * - forward_to (string): The destination email address where mail is forwarded
     *
     * @return array<array{email: string, forward_to: string}> List of email forwarders with their destinations
     */
    public function listForwarders(): array
    {
        $domain = $this->model()->domain;
        $username = $this->emailServer()->account()->model()->username;

        $forwarders = $this->emailServer()->account()->server()->api()->listEmailForwarders($username, $domain);

        return array_map(function ($forwarder) {
            return [
                'email' => $forwarder['email'],
                'forward_to' => $forwarder['forward_to'],
            ];
        }, $forwarders);
    }

    /**
     * Returns DKIM DNS records for email authentication.
     *
     * - name (string): The DNS record name for the DKIM selector
     * - type (string): The DNS record type (always 'TXT' for DKIM)
     * - value (string): The DKIM public key and configuration data
     *
     * @return array<array{name: string, type: string, value: string}> DKIM DNS records for domain authentication
     */
    public function getDkimRecords(): array
    {
        $domain = $this->model()->domain;
        $username = $this->emailServer()->account()->model()->username;

        $dkimRecord = $this->emailServer()->account()->server()->api()->getDkimRecord($username, $domain);

        return [
            [
                'name' => $dkimRecord['name'],
                'type' => $dkimRecord['type'],
                'value' => $dkimRecord['value'],
            ]
        ];
    }

    /**
     * Returns SPF DNS records for email authentication.
     *
     * - name (string): The domain name for the SPF record
     * - type (string): The DNS record type (always 'TXT' for SPF)
     * - value (string): The SPF policy definition including authorized servers
     *
     * @return array<array{name: string, type: string, value: string}> SPF DNS records for sender authentication
     */
    public function getSpfRecords(): array
    {
        $domain = $this->model()->domain;
        $ipAddress = $this->emailServer()->account()->model()->getIpAddress();

        return [
            [
                'name' => $domain,
                'type' => 'TXT',
                'value' => "v=spf1 mx a ip4:{$ipAddress} ~all"
            ]
        ];
    }
}
