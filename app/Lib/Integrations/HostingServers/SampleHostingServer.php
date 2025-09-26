<?php

namespace App\Lib\Integrations\HostingServers;

use App\Lib\Apis\SampleHostingServerApi;
use App\Lib\Interfaces\Integrations\HostingServerInterface;
use App\Models\Server;
use App\Models\Service;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Class SampleHostingServer
 *
 * Provides a sample implementation of a hosting server integration.
 * The class contains methods to manage server accounts,
 * and handle WordPress installations.
 * It also exposes WordPress lifecycle management (install, update, clone, staging, backups).
 *
 * @method Server model()
 */
class SampleHostingServer extends AbstractHostingServer implements HostingServerInterface
{
    /**
     * The name of the hosting server integration.
     */
    public static string $name = "Sample Hosting Server Integration";

    /**
     * The unique slug used to identify this hosting server in the system.
     */
    public static string $slug = "sample-hosting-server-integration";

    /**
     * The URL to the hosting server's official website.
     *
     * This link shows up in hosting server list in admin area.
     * For informational purposes only.
     *
     * Optional.
     */
    public static ?string $link = "https://example.com";

    /**
     * The category of hosting server.
     * Available values:
     * - 'shared' - shared hosting server
     * - 'cloud' - server hosted in cloud
     *
     * It determines how the hosting server will be placed in wizard
     */
    public static string $category = 'cloud';

    /**
     * Optional.
     * Whether this hosting server has its own internal DNS server.
     * By default returns false.
     */
    public static bool $hasInternalDnsServer = true;

    /**
     * Optional.
     * Whether this hosting server has its own internal Email server.
     *
     * By default returns false.
     */
    public static bool $hasInternalEmailServer = false;

    /**
     * Optional.
     * Whether to force creation of server account for applications.
     * Each instance will be placed on separate server accounts.
     * Admin cannot change this in admin area.
     *
     * By default returns false.
     */
    public static bool $forceCreateServerAccountForApplication = true;

    /**
     * Optional.
     * Whether any WordPress version can be installed on this server.
     * If false, user cannot select WordPress version during installation.
     *
     * By default returns true.
     */
    public static bool $anyWordpressVersionInstallable = true;

    /**
     * Optional.
     * Whether WordPress updates by WP CLI are available through this integration.
     * If false, automatic WordPress update is disabled.
     *
     * By default returns true.
     */
    public static bool $isWordpressUpdateAvailable = true;

    /**
     * Configuration fields needed to connect to the hosting server API.
     * This is used to authenticate with the server. It may contain URL, API key, etc.
     *
     * Each configuration field consists of:
     * - 'name' (string) - the field identifier
     * - 'type' (string) - the field type, e.g., text or checkbox
     *
     * Form with these fields will be rendered in admin area when adding/editing hosting server.
     *
     * If the hosting server is configured in admin area, values of these fields can be accessed
     * by using `$this->getConnectionConfig()` from within this class
     *
     * @var array<array> $configFields
     */
    public static array $configFields = [
        'api_url' => [
            'name' => 'api_url',
            'type' => 'text',
        ],
        'api_key' => [
            'name' => 'api_key',
            'type' => 'text',
        ]
    ];

    /**
     * Optional configuration fields needed to create server account.
     *
     * Each configuration field consists of:
     * - 'name' (string) - the field identifier
     * - 'type' (string) - the field type, e.g., text, checkbox, or select
     * - 'flags' (array) - optional flags like
     *     - 'configurable_in_wizard' - allow to display config in wizard
     *     - 'billable' -  config field can be updated and can be Configurable Option in WHMCS
     *     - 'not_upgradable' - config field cannot be updated
     *     - 'user_configurable' - config field can be displayed to choose by client in client area
     *
     * Form with these fields will be rendered in admin area when adding/editing plans (Hosting > Hosting Account Config).
     *
     * If the hosting server is configured in admin area, values of these fields can be accessed
     * by using `$this->getConfigFields()` from within this class.
     *
     * @var array<array> $accountConfigFields
     */
    public static array $accountConfigFields = [
        'plan' => [
            'name' => 'plan',
            'type' => 'select',
            'flags' => ['configurable_in_wizard', 'billable'],
        ],
        'space_quota' => [
            'name' => 'space_quota',
            'type' => 'text',
            'flags' => ['billable'],
        ],
        'burst_up_php_workers' => [
            'name' => 'burst_up_php_workers',
            'type' => 'checkbox',
            'flags' => ['billable', 'not_upgradable']
        ],
        'location' => [
            'name' => 'location',
            'type' => 'select',
            'flags' => ['billable', 'user_configurable'],
        ],
    ];

    /**
     * Account Configuration Field which will be shown in wizard to configure.
     * This field must be compatible with one of the fields from $accountConfigFields.
     */
    public static string $accountPlanField = "plan";

    /**
     * The API client instance for communicating with the hosting server.
     */
    protected SampleHostingServerApi $api;

    /**
     * Constructor for the hosting server integration.
     *
     * Initializes the integration with the provided server model and sets up the API client.
     * The API client will use the server's configuration for authentication.
     *
     * @param Server $server The server model instance containing connection details
     */
    public function __construct(Server $server)
    {
        parent::__construct($server);
        $this->api = new SampleHostingServerApi($server->connection_config);
    }

    /**
     * Get the API client instance for this hosting server.
     *
     * @return SampleHostingServerApi The API client instance
     */
    public function api(): SampleHostingServerApi
    {
        return $this->api;
    }

    /**
     * Get the hostname of the hosting server.
     *
     * @return string The server hostname
     */
    public function getHostname(): string
    {
        // Example implementation

        $url = $this->model()->connection_config['api_url'];
        $parsed = parse_url($url);
        return $parsed['host'] ?? '';
    }

    /**
     * Get custom details specific to this hosting server.
     *
     * This method allows the integration to provide additional server-specific
     * information that should be displayed in the admin area or used for
     * server management purposes.
     *
     * It can be private_key, specific server details, etc.
     * If the hosting server is configured in admin area, values of these fields can be accessed
     * by using `$this->model()->getDetails()` from within this class
     *
     * @return array
     */
    public function getCustomDetails(): array
    {
        // Example implementation

        return [
            'os' => 'Ubuntu 24.04',
            'version' => '1.2',
        ];
    }

    /**
     * This optional method returns API URL if it is static
     * and not configurable in $configFields.
     *
     * @param array $config
     * @return string|null
     */
    public static function getApiUrl(array $config): ?string
    {
        // Example implementation

        return 'example.com';
    }

    /**
     * This optional method corrects connection config.
     * By default returns $config.
     *
     * @param array<string> $config
     * @return array
     */
    public static function correctConnectionConfig(array $config): array
    {
        // Example Implementation - standardize `api_url`

        $parsedUrl = parse_url($config['api_url']);

        $scheme = $parsedUrl['scheme'] ?? 'https';
        $host = $parsedUrl['host'] ?? $parsedUrl['path'] ?? '';
        $port = $parsedUrl['port'] ?? '443';
        $path = empty($parsedUrl['host']) ? '/' : $parsedUrl['path'] ?? '/';

        $config['api_url'] = "{$scheme}://{$host}:{$port}{$path}";

        return $config;
    }

    /**
     * Tests the connection to the hosting server API using the provided configuration.
     *
     * This method should validate the API credentials and test connectivity to the
     * hosting server's control panel. It should throw an exception on failure,
     * allowing the admin interface to display appropriate error messages.
     *
     * @param array $config The API connection configuration based on $configFields
     * @return void
     * @throws Exception If the connection test fails
     */
    public static function testConnection(array $config): void
    {
        // Example API call to test the connection to the hosting server.

        $api = new SampleHostingServerApi($config);
        $result = $api->testConnection([
            'api_url' => $config['api_url'],
            'api_key' => $config['api_key'],
        ]);

        if (!$result['success']) {
            throw new Exception('Connection test failed');
        }
    }

    /**
     * Tests WP-CLI functionality on the hosting server.
     *
     * This method verifies that WP-CLI is installed and accessible on the hosting server
     * using the provided configuration. It's used to ensure WordPress management
     * operations can be performed through command-line interface.
     *
     * In default cases, it should be checked whether WP-CLI is installed and accessible, that WP-CLI is working,
     * that the PHP memory limit is greater than 256MB, and that PHP has the required extensions and functions.
     *
     * @param array $config The API connection configuration
     * @return void
     * @throws Exception If WP-CLI is not available or accessible
     */
    public static function testWpCli(array $config): void
    {
        $api = new SampleHostingServerApi($config);
        $result = $api->checkWpCliConnection([
            'api_url' => $config['api_url'],
            'api_key' => $config['api_key'],
        ]);

        if (!$result['success']) {
            throw new Exception('WP-CLI is not available or not accessible on this server');
        }
    }

    /**
     * Retrieves available configuration values for hosting account setup.
     *
     * Optional method that fetches dynamic values for fields defined in
     * $accountConfigFields. This is useful for fields that should show dropdown
     * options instead of text inputs (like available plans, locations, etc.).
     *
     * Example:
     * For the 'plan' field, this method retrieves all possible configurations.
     * This will replace text input with dropdown when adding/editing plan in admin area.
     *
     * @return array Array with field names as keys and options as values,
     *               where each option has 'text' and 'value' keys
     */
    public function getAvailableServerValues(): array
    {
        $serverPlans = $this->api()->getServerPlans();
        $plans = [];
        foreach ($serverPlans as $plan) {
            $plans[] = [
                'text' => $plan['name'],
                'value' => $plan['id'],
            ];
        }

        $serverLocations = $this->api()->getLocations();
        $locations = [];
        foreach ($serverLocations as $location) {
            $locations[] = [
                'text' => $location,
                'value' => $location,
            ];
        }

        return [
            'plan' => [
                'enabled' => true,
                'data' => $plans
            ],
            'location' => [
                'enabled' => true,
                'data' => $locations
            ]
        ];
    }

    /**
     * Retrieves available configuration values for hosting account setup.
     *
     * Optional method that fetches dynamic values for fields defined in
     * $accountConfigFields. This is useful for fields that should show dropdown
     * options instead of select inputs (like available plans, locations, etc.).
     *
     * Example:
     * For the 'location' field, this method retrieves all possible configurations.
     * This field has `user_configurable` flag.
     *
     * This value will be shown for client in client area during instance installation.
     *
     * @param Service $service
     * @return array
     */
    public function getUserConfigurableOptionValues(Service $service): array
    {
        $serverLocations = $this->api()->getLocations();
        $locations = [];
        foreach ($serverLocations as $location) {
            $locations[] = [
                'text' => $location,
                'value' => $location,
            ];
        }

        return [
            'location' => $locations
        ];
    }

    /**
     * Returns valid username for new hosting account.
     *
     * @param string $base
     * @return string
     */
    public function generateValidUsername(string $base = ""): string
    {
        // Example implementation

        if (empty($base)) {
            return Str::random(8);
        }
        return $base;
    }

    /**
     * Returns valid password for new hosting account.
     *
     * @return string
     */
    public function generateValidPassword(): string
    {
        // Example implementation

        $base = Str::random(16);
        return Crypt::encryptString($base);
    }

    /**
     * Check if a domain exists on this hosting server.
     *
     * This method queries the hosting server to determine if the specified
     * domain is already configured and active.
     *
     * @param string $domain The domain name to check
     * @return bool True if the domain exists on the server, false otherwise
     */
    public function domainExists(string $domain): bool
    {
        return $this->api->domainExists($domain);
    }

    /**
     * Optional method.
     * If implemented, the domain can be checked before creation.
     *
     * Checks whether a domain can be hosted.
     *
     * This method can return:
     * - `allowed` — the domain can be hosted
     * - `restricted` — the domain cannot be hosted
     * - `to_verify` — the domain can be hosted (e.g., because it belongs to another user),
     *   but must be verified (e.g., via a TXT DNS record obtained with $this->getDomainVerificationCode()).
     *
     * @param string $domain
     * @return string Verification result
     * @throws Exception
     */
    public function checkCanHostDomain(string $domain): string
    {
        return $this->api()->checkDomain($domain);
    }

    /**
     * Optional method. Required when `checkCanHostDomain()` is implemented.
     *
     * Returns verification code when it is required
     *
     * @param string $domain
     * @return string
     */
    public function getDomainVerificationCode(string $domain): string
    {
        return $this->api()->getDomainVerificationCode();
    }

    /**
     * Retrieves a list of all hosting accounts from the server.
     *
     * Used in server synchronization to import existing accounts into the system.
     * Each account should contain basic information needed for account management.
     *
     * Returned value is expected to be an array of arrays containing:
     * - 'username' (string) The username of hosting account
     * - 'domain' (string) The main domain name of hosting account
     * - 'email' (string) The email address of hosting account (or owner's email)
     * - 'plan' (string|null) The hosting plan (if exists) of hosting account
     * - 'suspended' (bool) Whether the hosting account is suspended or not
     *
     * @return array<array{
     *   username: string,
     *   domain: string,
     *   email: string,
     *   plan: string|null,
     *   suspended: bool
     * }> Array of account information
     */
    public function listAccounts(): array
    {
        $result = $this->api->listAccounts();

        $accounts = [];
        /** @var array{username: string, domain: string, email:string, plan: string|null, suspended: bool} $account */
        foreach ($result as $account) {
            $accounts[] = [
                'username' => $account['username'],
                'domain' => $account['domain'],
                'email' => $account['email'],
                'plan' => $account['plan'],
                'suspended' => $account['suspended']
            ];
        }

        return $accounts;
    }

    /**
     * Finds a specific hosting account by its username.
     *
     * Used in server synchronization and account management to retrieve
     * detailed information about a specific hosting account.
     *
     * Returns the hosting account array, or null if not found.
     * Details can include additional information about the hosting account.
     *
     * @param string $username The username of the account to find
     * @return array|null Array containing account details, or null if not found
     * @return array{
     *   username: string,
     *   domain: string,
     *   email: string,
     *   plan: string|null,
     *   suspended: bool,
     *   details: array<string,mixed>
     * }|null
     */
    public function findAccount(string $username): ?array
    {
        $account = $this->api->getAccount($username);
        if ($account) {
            return [
                'username' => $account['username'],
                'domain' => $account['domain'],
                'email' => $account['email'],
                'plan' => $account['plan'],
                'suspended' => $account['suspended'],
                'details' => []
            ];
        }
        return null;
    }

    /**
     * Discovers WordPress installations on the hosting server.
     *
     * This method scans the hosting server to find existing WordPress installations
     * and returns their details for synchronization with the management system.
     * Used for automated WordPress discovery and management.
     *
     * Returned value is expected to be an array of arrays containing:
     * - path (string) The path to the WordPress installation
     * - version (string) The version of WordPress installed
     * - account (array) Server account details where the installation is hosted
     * - domain (array) Domain details for the WordPress installation
     *   - domain (string) The domain name
     *   - type (string) The type of domain (main, addon, sub)
     *   - document_root (string) The document root of the WordPress installation
     *
     * @return array<array{
     *   path: string,
     *   version: string,
     *   account: array{
     *     username: string,
     *     domain: string,
     *     email: string,
     *     plan: string|null,
     *     suspended: bool
     *   },
     *   domain: array{
     *     domain: string,
     *     type: string,
     *     document_root: string
     *   }
     * }> Array of WordPress installation details
     */
    public function findWordpresses(): array
    {
        $installations = $this->api->findWordpressInstalls();
        $result = [];

        /** @var array{username: string, path: string, version: string, domain: string} $installation */
        foreach ($installations as $installation) {
            /** @var array{
             *     username: string,
             *     domain: string,
             *     email:string,
             *     plan: string|null,
             *     suspended: bool
             * } $accountData
             */
            $accountData = $this->api->getAccount($installation['username']);

            $result[] = [
                'path' => $installation['path'],
                'version' => $installation['version'],
                'account' => [
                    'username' => $accountData['username'],
                    'domain' => $accountData['domain'],
                    'email' => $accountData['email'],
                    'plan' => $accountData['plan'],
                    'suspended' => $accountData['suspended'],
                ],
                'domain' => [
                    'domain' => $installation['domain'],
                    'type' => 'main',
                    'document_root' => $installation['path'],
                ]
            ];
        }
        return $result;
    }

    /**
     * Optional method.
     * Returns if hosting server has sso to phpmyadmin enabled.
     *
     * By default returns true.
     *
     * @return bool
     */
    public function isPhpMyAdminSsoEnabled(): bool
    {
        return true;
    }

    /**
     * Optional method.
     * Returns if hosting server has sso login to their control panel.
     *
     * By default returns true.
     *
     * @param array|null $accountConfig
     * @return bool
     */
    public function isControlPanelSsoEnabled(?array $accountConfig = null): bool
    {
        return true;
    }
}
