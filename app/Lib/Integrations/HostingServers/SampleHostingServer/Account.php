<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration;

use App\Events\RemoteDomainCreated;
use App\Events\RemoteDomainDeleted;
use App\Lib\Integrations\HostingServers\AbstractHostingServer\AbstractAccount;
use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\Application\Wordpress\AbstractConfig;
use App\Lib\Integrations\HostingServers\SampleHostingServer;
use App\Lib\Interfaces\Integrations\HostingServer\AccountInterface;
use App\Models\Plan;
use App\Models\ServerAccount;
use App\Models\User;
use Exception;

/**
 * Manages hosting account operations for the SampleHostingServerIntegration.
 *
 * @method SampleHostingServer server()
 * @method ServerAccount model()
 */
class Account extends AbstractAccount implements AccountInterface
{
    /**
     * Creates a new hosting account on the server.
     *
     * Provisions a complete hosting account using the configured username, password,
     * domain, and service plan settings. Retrieves account configuration from the
     * service plan and applies it during account creation.
     *
     * Available account model properties:
     * - username (string): The hosting account username
     * - password (string): The hosting account password
     * - domain (string): The primary domain for the account
     *
     * Exemplary configuration fields retrieved via `getHostingAccountConfig()`:
     * - plan (string): The hosting plan identifier
     * - space_quota (string): Disk space allocation
     * - burst_up_php_workers (bool): PHP worker burst capability
     * - location (string): Server location preference
     *
     * Automatically dispatches `RemoteDomainCreated` event for the main domain
     * to ensure proper domain tracking and DNS configuration.
     *
     * @return void
     * @throws Exception When account creation fails or API communication errors occur
     */
    public function create(): void
    {
        $username = $this->model()->username;
        $password = $this->model()->password;
        $domain = $this->model()->domain;

        $this->server()->api()->createAccount([
            'username' => $username,
            'password' => $password,
            'domain' => $domain,
            'plan' => $this->model()->service->getHostingAccountConfig('plan'),
            'space_quota' => $this->model()->service->getHostingAccountConfig('space_quota'),
            'burst_up_php_workers' => $this->model()->service->getHostingAccountConfig('burst_up_php_workers'),
            'location' => $this->model()->service->getHostingAccountConfig('location'),
        ]);

        // Don't remove
        RemoteDomainCreated::dispatch($this->model()->domain, 'main_domain', $this);
    }

    /**
     * Updates hosting account configuration on the server.
     *
     * Modifies existing account settings by applying the provided configuration
     * parameters. Only the specified parameters will be updated, leaving other
     * settings unchanged.
     *
     * Accepts configuration fields matching `$accountConfigFields` defined in
     * SampleHostingServerIntegration.php. All parameters are optional and will
     * only be applied if provided.
     *
     * @param array{plan?: string, space_quota?: string, burst_up_php_workers?: bool, location?: string} $params Exemplary configuration parameters to update:
     *   - plan (string, optional): The hosting plan identifier
     *   - space_quota (string, optional): Disk space allocation limit
     *   - burst_up_php_workers (bool, optional): Enable/disable PHP worker bursting
     *   - location (string, optional): Preferred server location
     * @return void
     * @throws Exception When account update fails or invalid parameters are provided
     */
    public function update(array $params): void
    {
        $username = $this->model()->username;

        $this->server()->api()->updateAccount([
            'username' => $username,
            'plan' => $params['plan'],
            'space_quota' => $params['space_quota'],
            'burst_up_php_workers' => $params['burst_up_php_workers'],
            'location' => $params['location'],
        ]);
    }

    /**
     * Permanently deletes the hosting account from the server.
     *
     * Removes the entire hosting account including all associated data, files,
     * databases, and configurations.
     *
     * Automatically dispatches `RemoteDomainDeleted` event for the main domain
     * to ensure proper cleanup of domain records and DNS configurations.
     *
     * @return void
     * @throws Exception When account deletion fails or API communication errors occur
     */
    public function delete(): void
    {
        $username = $this->model()->username;

        $this->server()->api()->deleteAccount($username);

        // Don't remove
        RemoteDomainDeleted::dispatch($this->model()->domain, 'main_domain', $this);
    }

    /**
     * Verifies if the hosting account exists on the server.
     *
     * @return bool True if the account exists on the server, false otherwise
     * @throws Exception When API communication fails or server is unreachable
     */
    public function exists(): bool
    {
        $username = $this->model()->username;

        return $this->server()->api()->userExists($username);
    }

    /**
     * Determines if the hosting account is currently suspended.
     *
     * @return bool True if the account is suspended, false if active
     * @throws Exception When API communication fails or account doesn't exist
     */
    public function isSuspended(): bool
    {
        $username = $this->model()->username;

        return $this->server()->api()->isSuspendedAccount($username);
    }

    /**
     * Suspends the hosting account on the server.
     *
     * @return void
     * @throws Exception When suspension fails or account doesn't exist
     */
    public function suspend(): void
    {
        $username = $this->model()->username;

        $this->server()->api()->suspendAccount($username);
    }

    /**
     * Reactivates a suspended hosting account.
     *
     * @return void
     * @throws Exception When unsuspension fails or account doesn't exist
     */
    public function unsuspend(): void
    {
        $username = $this->model()->username;

        $this->server()->api()->unsuspendAccount($username);
    }

    /**
     * Retrieves the absolute path to the account's home directory.
     *
     * @return string The absolute filesystem path to the account's home directory
     * @throws Exception When path retrieval fails or account doesn't exist
     */
    public function getHomeDir(): string
    {
        $username = $this->model()->username;

        return $this->server()->api()->getHomeDir($username);
    }

    /**
     * Retrieves all IP addresses assigned to the hosting account.
     *
     * Returns comprehensive IP address information including primary IPv4/IPv6
     * addresses and any additional IP addresses allocated to the account.
     * This information is essential for DNS configuration and network setup.
     *
     * @return array{ip_address: string, ipv4: string, ipv6: ?string, additional_ip_addresses: array} IP address configuration containing:
     *   - ip_address (string): Primary IP address (typically IPv4)
     *   - ipv4 (string): Primary IPv4 address
     *   - ipv6 (string|null): Primary IPv6 address, null if not assigned
     *   - additional_ip_addresses (array): List of any additional IP addresses (if exists)
     * @throws Exception When IP address retrieval fails or account doesn't exist
     */
    public function getIPAddresses(): array
    {
        return [
            'ip_address' => '127.0.0.1',
            'ipv4' => '127.0.0.1',
            'ipv6' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'additional_ip_addresses' => [],
        ];
    }

    /**
     * Provides a simplified list of all IP addresses assigned to the account.
     *
     * Returns all IPv4 and IPv6 addresses in a single flat array format,
     * making it convenient for operations that need to iterate through
     * all available IP addresses without structure complexity.
     *
     * @return array<string> Flat list of all IP addresses (IPv4 and IPv6)
     * @throws Exception When IP address retrieval fails or account doesn't exist
     */
    public function getAllIpAddresses(): array
    {
        return [
            '127.0.0.1',
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334'
        ];
    }

    /**
     * Retrieves the list of host CNAME records required for the account.
     *
     * Returns an array of hostnames that should be configured as CNAME records
     * to ensure proper routing and functionality of the hosting account.
     *
     * @return string[] List of required CNAME hostnames
     */
    public function getHostCNames(): array
    {
        return [
            'example.com',
        ];
    }

    /**
     * Retrieves comprehensive account usage statistics and limits.
     *
     * Provides detailed usage information for various account resources,
     * comparing current usage against configured limits. This data is
     * essential for monitoring, billing, and capacity planning.
     *
     * Available usage metrics (all optional):
     * - disk_usage: Filesystem storage consumption and limits
     * - bandwidth: Data transfer usage and limits
     * - addon_domains: Additional domains count and limits
     * - subdomains: Subdomain count and limits
     * - aliases: Domain alias count and limits
     * - email_accounts: Email mailbox count and limits
     * - forwarders: Email forwarding rules count and limits
     * - mysql_databases: MySQL database count and limits
     * - ftp_accounts: FTP user account count and limits
     * - sftp_accounts: SFTP user account count and limits
     *
     * Each metric contains:
     * - usage (int|float): Current resource consumption
     * - maximum (int|float|null): Resource limit, null indicates unlimited
     *
     * @return array<string, array{usage: int|float, maximum: int|float|null}> Usage statistics grouped by resource type
     * @throws Exception When usage data retrieval fails or account doesn't exist
     */
    public function usage(): array
    {
        //Example Implementation

        return [
            'disk_usage' => [
                'usage' => 10,
                'maximum' => null,
            ],
            'bandwidth' => [
                'usage' => 10,
                'maximum' => null,
            ],
        ];
    }

    /**
     * Retrieves the current hosting account configuration settings.
     *
     * Returns all configurable account parameters including hosting plan,
     * resource allocations, and server preferences. This information reflects
     * the account's current operational configuration based on `$accountConfigFields`.
     *
     * @return array{plan: string, space_quota: string, burst_up_php_workers: bool, location: string} Exemplary configuration containing:
     *   - plan (string): The active hosting plan identifier
     *   - space_quota (string): Current disk space allocation limit
     *   - burst_up_php_workers (bool): PHP worker burst capability status
     *   - location (string): Assigned server location
     * @throws Exception When configuration retrieval fails or account doesn't exist
     */
    public function getConfig(): array
    {
        return [
            'plan' => 'Basic',
            'space_quota' => '1 GB',
            'burst_up_php_workers' => false,
            'location' => 'Finland'
        ];
    }

    /**
     * Updates hosting account configuration with validation and field mapping.
     *
     * Applies configuration changes using the account configuration fields
     * defined in the integration. Validates parameters against allowed fields
     * and maps them appropriately before sending to the server API.
     *
     * @param array{plan?: string, space_quota?: string, burst_up_php_workers?: bool, location?: string} $params Exemplary configuration parameters to update:
     *   - plan (string, optional): The hosting plan identifier
     *   - space_quota (string, optional): Disk space allocation limit
     *   - burst_up_php_workers (bool, optional): Enable/disable PHP worker bursting
     *   - location (string, optional): Preferred server location
     * @return array The original parameters array that was passed to the method
     * @throws Exception When configuration update fails or invalid parameters provided
     */
    public function updateConfig(array $params): array
    {
        /** @var array{
         *     plan: string,
         *      space_quota: string,
         *     burst_up_php_workers: bool,
         *     location: string
         * } $accountConfigFields
         */
        $accountConfigFields = $this->model()->server->integration->type::$accountConfigFields;

        $data = [];
        foreach ($accountConfigFields as $field) {
            $data[$field['name']] = $params[$field['name']] ?? null;
        }
        $data['username'] = $this->model()->username;

        $this->server()->api()->updateAccount($data);

        return $params;
    }

    /**
     * Provides additional metadata and details about the hosting account.
     *
     * Returns supplementary information that extends beyond basic account
     * configuration. This data is accessible throughout the integration
     * namespace via `$this->model->getDetails()` for enhanced account
     * management and display purposes.
     *
     * @return array{type: string} Exemplary account metadata containing:
     *   - type (string): Account classification or category
     * @throws Exception When account information retrieval fails
     */
    public function getAccountInfo(): array
    {
        //Example implementation

        return [
            'type' => 'standard'
        ];
    }

    /**
     * Generates a Single Sign-On URL for the hosting control panel.
     *
     * Creates an authenticated URL that allows users to access the hosting
     * control panel without additional login credentials.
     *
     * This method is only utilized when `isControlPanelSsoEnabled()` returns
     * true in the SampleHostingServerIntegration configuration. If SSO is
     * disabled, this method will not be called.
     *
     * @return string The complete SSO URL for control panel access
     * @throws Exception When SSO URL generation fails or account doesn't exist
     */
    public function createControlPanelSsoUrl(): string
    {
        $username = $this->model()->username;

        return $this->server()->api()->createControlPanelSso($username);
    }

    /**
     * Transfers hosting account ownership to a different user.
     *
     * Updates the account's owner information including contact details and
     * user associations. This operation typically occurs during account
     * transfers or when changing the primary account holder.
     *
     * The method extracts relevant user information and updates the hosting
     * server configuration to reflect the new ownership details.
     *
     * @param User $user The new owner user object containing:
     *   - email: New owner's email address
     *   - first_name: New owner's first name
     *   - last_name: New owner's last name
     * @return void
     * @throws Exception When ownership change fails or user data is invalid
     */
    public function changeOwner(User $user): void
    {
        $email = $user->email;
        $firstName = $user->first_name;
        $lastName = $user->last_name;

        $this->server()->api()->updateAccount([
            'username' => $this->model()->username,
            'email' => $email,
        ]);
    }

    /**
     * Executes WordPress CLI commands within the hosting account context.
     *
     * This is a crucial method for WordPress management operations. Runs
     * WP-CLI commands in the account's environment and returns detailed
     * execution results including output streams and exit status.
     *
     * For all other contexts, it returns sample output demonstrating successful
     * command execution. In a real implementation, this would interface with
     * the server's WP-CLI installation.
     *
     * @param array $params WP-CLI command parameters and options
     * @return object{
     *     stdout: string,
     *     stderr: string,
     *     exit_code: int,
     * } Command execution result containing:
     *   - stdout (string): Standard output from the command
     *   - stderr (string): Error output from the command
     *   - exit_code (int): Command exit status (0 for success)
     * @throws Exception When WP-CLI execution fails or account doesn't exist
     */
    public function runWpCliCommand(array $params): object
    {
        $backtrace = debug_backtrace();
        $caller = $backtrace[3];


        // For demonstration purposes only
        if ($caller['class'] . ':' . $caller['function'] === AbstractConfig::class . ':list') {
            return (object)[
                'stdout' => '[]',
                'stderr' => '',
                'exit_code' => 0,
            ];
        }

        return (object)[
            'stdout' => '"Some output"',
            'stderr' => '',
            'exit_code' => 0,
        ];
    }
}
