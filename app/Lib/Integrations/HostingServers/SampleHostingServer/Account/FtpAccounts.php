<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\AbstractFtpAccounts;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;
use App\Lib\Interfaces\Integrations\HostingServer\Account\FtpAccountsInterface;
use App\Models\Timestamp;

/**
 * Manages FTP account operations for hosting accounts.
 *
 * Enables clients to manage their FTP accounts in the Client Area.
 * If the hosting server does not support FTP accounts, remove this class.
 *
 * @method Account account()
 */
class FtpAccounts extends AbstractFtpAccounts implements FtpAccountsInterface
{
    /**
     * Returns all FTP accounts for the hosting account.
     *
     * The response contains the disk usage and quota for each FTP account.
     * - user (string): The FTP username
     * - directory (string): The home directory path for the FTP account
     * - diskused (int|string): Current disk usage, can be in bytes or formatted string
     * - diskquota (int|string): Disk quota limit, can be in bytes or formatted string
     *
     * @return array<array{
     *     user: string,
     *     directory: string,
     *     diskused: int|string,
     *     diskquota: int|string
     * }> List of FTP accounts, where each account includes:
     *     - 'user' (string) The FTP username
     *     - 'directory' (string) The home directory path
     *     - 'diskused' (int|string) Current disk usage
     *     - 'diskquota' (int|string) Disk quota limit
     */
    public function list(): array
    {
        $accounts = [];

        $result = $this->account()->server()->api()->listFtpAccounts($this->account()->model()->username);

        foreach ($result as $acc) {
            $accounts[] = [
                'user' => $acc['user'],
                'directory' => $acc['dir'],
                'diskused' => $acc['diskused'],
                'diskquota' => $acc['diskquota'],
            ];
        }

        return $accounts;
    }

    /**
     * Creates a new FTP account on the hosting server.
     *
     * Creates a new FTP account with the specified parameters and records the creation
     * timestamp for tracking purposes.
     *
     * @param array{
     *     user: string,
     *     domain: string,
     *     password: string,
     *     directory: string,
     *     quota: int
     * } $params Configuration for the new FTP account:
     *     - 'user' (string) The FTP username
     *     - 'domain' (string) The domain name
     *     - 'password' (string) The FTP account password
     *     - 'directory' (string) The home directory path
     *     - 'quota' (int) The disk quota in MB or 0 for unlimited
     * @return array{
     *     username: string,
     *     domain: string
     * } Created account information:
     *     - 'username' (string) The full FTP username (user@domain)
     *     - 'domain' (string) The associated domain
     */
    public function create(array $params): array
    {
        $this->account()->server()->api()->createFtpAccount($this->account()->model()->username, $params);

        Timestamp::firstOrCreate([
            'server_account_id' => $this->account()->model()->id,
            'item_type' => 'ftp_account',
            'item_id' => $params['user'] . '@' . $params['domain'],
        ]);

        return [
            'username' => $params['user'] . '@' . $params['domain'],
            'domain' => $params['domain'],
        ];
    }

    /**
     * Updates an existing FTP account configuration.
     *
     * Updates the specified FTP account with new parameters and touches the
     * timestamp to track the modification.
     *
     * @param string $user The FTP username to update
     * @param array{password?: string, quota?: int} $params Update parameters:
     *     - 'password' (string, optional) New password for the FTP account
     *     - 'quota' (int, optional) New disk quota in MB
     * @return void
     */
    public function update(string $user, array $params): void
    {
        // Update FTP account via hosting server API
        $this->account()->server()->api()->updateFtpAccount($this->account()->model()->username, $user, $params);

        $timestamp = Timestamp::firstOrCreate([
            'server_account_id' => $this->account()->model()->id,
            'item_type' => 'ftp_account',
            'item_id' => $user,
        ]);
        $timestamp->touch();
    }

    /**
     * Deletes an FTP account from the hosting server.
     *
     * Removes the FTP account from the server and deletes the associated
     * timestamp record.
     *
     * @param string $user The FTP username to delete
     * @return void
     */
    public function delete(string $user): void
    {
        $this->account()->server()->api()->deleteFtpAccount($this->account()->model()->username, $user);

        $timestamp = Timestamp::firstOrNew([
            'server_account_id' => $this->account()->model()->id,
            'item_type' => 'ftp_account',
            'item_id' => $user,
        ]);
        $timestamp->delete();
    }

    /**
     * Returns FTP server connection information.
     *
     * Provides the hostname and port number that clients should use to
     * connect to the FTP server.
     *
     * @return array{host: string, port: int} FTP server connection details:
     *     - 'host' (string) The FTP server hostname or IP address
     *     - 'port' (int) The FTP server port number (typically 21)
     */
    public function serverInfo(): array
    {
        return [
            'host' => 'example.com',
            'port' => 21,
        ];
    }

    /**
     * Indicates if FTP usage reporting is supported.
     *
     * Determines whether the hosting server provides FTP usage statistics.
     * If `false`, FTP usage charts are hidden in the client area.
     *
     * @return bool True if FTP usage reporting is supported, false otherwise
     */
    public function usageSupported(): bool
    {
        return false;
    }
}
