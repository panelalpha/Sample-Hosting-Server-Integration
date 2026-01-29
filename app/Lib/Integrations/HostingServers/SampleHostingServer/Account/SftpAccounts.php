<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\AbstractSftpAccounts;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;
use App\Lib\Interfaces\Integrations\HostingServer\Account\SftpAccountsInterface;
use App\Models\Timestamp;

/**
 * Manages SFTP account operations for hosting accounts.
 *
 *  Enables clients to manage their SFTP accounts in the Client Area.
 *  If the hosting server does not support SFTP accounts, remove this class.
 *
 * @method Account account()
 */
class SftpAccounts extends AbstractSftpAccounts implements SftpAccountsInterface
{
    /**
     * Retrieves all SFTP accounts associated with the hosting account.
     *
     * Returns a list of all SFTP users configured for the
     * hosting account.
     *
     * @return array<array{
     *     user: string,
     * }> List of SFTP accounts containing:
     *   - user (string): The SFTP username
     *   - password (string): Password. Optional; if provided, the password will be displayed in the Client Area.
     * @throws Exception When SFTP account retrieval fails or account doesn't exist
     */
    public function list(): array
    {
        $username = $this->account()->model()->username;

        return $this->account()->server()->api()->listSftpAccounts($username);
    }

    /**
     * Creates a new SFTP account with specified authentication methods.
     *
     * @param array{
     *     username: string,
     *     password: string|null,
     *     public_key: string|null
     * } $params Configuration for the new SFTP account:
     *   - username (string): The desired SFTP username
     *   - password (string|null): Password for password-based authentication
     *   - public_key (string|null): SSH public key for key-based authentication
     * @return array{username: string} Created account information containing:
     *   - username (string): The SFTP username that was successfully created
     * @throws Exception When account creation fails, username conflicts, or invalid parameters provided
     */
    public function create(array $params): array
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->createSftpAccount($username, $params);

        Timestamp::firstOrCreate([
            'server_account_id' => $this->account()->model()->id,
            'item_type' => 'sftp_account',
            'item_id' => $params['username'],
        ]);

        return [
            'username' => $params['username']
        ];
    }


    /**
     * Updates authentication credentials for an existing SFTP account.
     *
     * @param string $user The SFTP username to update
     * @param array{
     *     password: string|null,
     *     public_key: string|null
     * } $params Update parameters:
     *   - password (string|null): New password for authentication
     *   - public_key (string|null): New SSH public key for authentication
     * @return void
     * @throws Exception When account update fails, user doesn't exist, or invalid parameters provided
     */
    public function update(string $user, array $params): void
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->updateSftpAccount($username, $user, $params);

        $timestamp = Timestamp::firstOrCreate([
            'server_account_id' => $this->account()->model()->id,
            'item_type' => 'sftp_account',
            'item_id' => $user,
        ]);
        $timestamp->touch();
    }

    /**
     * Permanently removes an SFTP account from the server.
     *
     * @param string $user The SFTP username to permanently delete
     * @return void
     * @throws Exception When account deletion fails or user doesn't exist
     */
    public function delete(string $user): void
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->deleteSftpAccount($username, $user);

        Timestamp::firstOrNew([
            'server_account_id' => $this->account()->model()->id,
            'item_type' => 'sftp_account',
            'item_id' => $user,
        ])->delete();
    }

    /**
     * Retrieves SFTP server connection configuration for client setup.
     *
     * @return array{
     *     host: string,
     *     port: int
     * } SFTP server connection details containing:
     *   - host (string): SFTP server hostname or IP address for connections
     *   - port (int): SFTP server port number (typically 22 for standard SSH/SFTP)
     * @throws Exception When server information retrieval fails
     */
    public function serverInfo(): array
    {
        $details = $this->account()->server()->api()->getSftpServerInfo();

        return [
            'host' => $details['host'],
            'port' => $details['port'],
        ];
    }

    /**
     * Optional.
     *
     * Forcibly terminates all active SFTP sessions for the user hosting account.
     *
     * Disconnects all currently active SFTP connections associated with the
     * hosting account.
     *
     * Note: This method is optional and only available if the hosting server
     * supports active session management.
     *
     * @return void
     * @throws Exception When session disconnection fails or feature is not supported
     */
    public function disconnectAll(): void
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->disconnectAllSftpSessions($username);
    }

    /**
     * Optional.
     *
     * Configures SFTP access permissions and restrictions for the hosting account.
     *
     * Exemplary access types include:
     * - 'ssh': Full SSH and SFTP access with shell capabilities
     * - 'sftp': SFTP-only access without shell access
     * - 'custom': Custom permission set based on server configuration
     *
     * Note: This method is optional and only available if the hosting server
     * supports granular SFTP access control.
     *
     * @param string $type The access type configuration identifier
     * @return void
     * @throws Exception When access type configuration fails or feature is not supported
     */
    public function setAccessType(string $type): void
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->setSftpAccessType($username, $type);
    }
}
