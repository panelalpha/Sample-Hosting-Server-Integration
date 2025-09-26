<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServer\Account;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\AbstractApplication;
use App\Lib\Integrations\HostingServers\SampleHostingServer\Account;
use App\Lib\Integrations\HostingServers\SampleHostingServer\Account\Application\Backups;
use App\Lib\Interfaces\Integrations\HostingServer\Account\ApplicationInterface;
use App\Models\Application as ApplicationModel;
use App\Models\Template;
use Carbon\Carbon;
use Exception;

/**
 *  Manages application operations for a WordPress instance on a hosting server.
 *
 * @method Account account()
 * @method ApplicationModel model()
 * @method Backups backups()
 */
class Application extends AbstractApplication implements ApplicationInterface
{
    /**
     * Installs a WordPress instance on the hosting server.
     *
     * Supports multiple installation types via the `install_type` parameter:
     * - `clone`: Clone from an existing WordPress instance
     * - `template`: Create from a template (zip package with database and files)
     * - `clean`: Create a new clean WordPress installation
     *
     * The installation process includes domain preparation, database creation,
     * WordPress file setup, `wp-config.php` configuration, and WordPress installation.
     *
     * Typical flow:
     * - create or get new domain
     * - create database for WordPress
     * - download WordPress files
     * - configure `wp-config.php`
     * - install WordPress
     *
     * @return array{
     *     path: string,
     *     db_name: string,
     *     db_user: string,
     *     db_password: string,
     *     db_host: string
     * } Installation details with file path and database credentials
     * @throws Exception If installation fails or required parameters are missing
     */
    public function install(): array
    {
        // Example implementation

        /** @var array{
         *     install_type: string,
         *     clone_instance_id?: string,
         *     domain: string,
         *     php_version?: string,
         *     dir?: string,
         *     db_name?: string,
         *     db_user?: string,
         *     db_password?: string,
         *     install_type: string,
         *     instance_template_id?: int,
         *     version: string,
         *     site_name: string,
         *     url: string,
         *     language?: string,
         *     admin_username?: string,
         *     admin_password?: string,
         *     admin_email?: string
         * } $params
         */
        $params = $this->model()->getInstallDetails();
        $username = $this->account()->model()->username;

        $result = $this->account()->server()->api()->installWordpressInstance($username, $params);

        $this->model()->setInstallPath($result['path']);
        $this->model()->setInstallDetails([
            'db_host' => $result['db_host'],
            'db_name' => $result['db_name'],
            'db_user' => $result['db_user'],
            'db_password' => $result['db_password'],
        ]);
        $this->model()->save();

        return [
            'path' => $result['path'],
            'db_name' => $result['db_name'],
            'db_user' => $result['db_user'],
            'db_password' => $result['db_password'],
            'db_host' => $result['db_host'],
        ];
    }

    /**
     * Auxiliary method.
     *
     * Prepares the domain for the WordPress installation.
     *
     * @return string The document root path for the domain
     * @throws Exception If domain creation fails or domain is not found
     */
    public function prepareDomain(): string
    {
        // Example implementation

        $installDetails = $this->model()->getInstallDetails();
        $domainName = $installDetails['domain'];

        $this->account()->domains()->createAddonDomain($domainName);
        $domain = $this->account()->domains()->find($domainName);

        if ($domain === null) {
            throw new Exception(__('api/messages.domain_not_found'));
        }

        return $domain['document_root'];
    }

    /**
     *  Auxiliary method.
     *
     * Prepares the database for the WordPress installation.
     *
     * @return array{
     *     db_host: string,
     *     db_name: string,
     *     db_user: string,
     *     db_password: string
     * } Database connection details for WordPress configuration
     */
    protected function prepareDatabase(): array
    {
        // Example implementation

        /** @var array{
         *     db_name: string,
         *     db_user: string,
         *     db_password: string,
         *     domain: string,
         *     dir: string
         * } $params
         */
        $params = $this->model()->getInstallDetails();
        $username = $this->account()->model()->username;

        $dbName = $params['db_name'] ?: $username . '_wp';
        $dbUser = $params['db_user'] ?: $username . '_wpuser';
        $dbPassword = $params['db_password'] ?: bin2hex(random_bytes(16));

        $this->account()->server()->api()->createDatabase($username, ['name' => $dbName]);

        $this->account()->server()->api()->createMysqlUser($username, $dbUser, $dbPassword);

        $this->account()->server()->api()->setUserPrivileges($username, $dbUser, $dbName, 'ALL PRIVILEGES');

        return [
            'db_host' => 'localhost',
            'db_name' => $dbName,
            'db_user' => $dbUser,
            'db_password' => $dbPassword,
        ];
    }

    /**
     * Clones a WordPress instance from an existing application.
     *
     * Creates a complete copy of the source WordPress instance including files
     * and database. The process involves creating a zip archive of source files,
     * exporting the source database, preparing the target domain and database,
     * and configuring `wp-config.php` for the new instance.
     *
     * Typical flow:
     * - zip files from source WordPress with database
     * - create or get domain for new WordPress instance
     * - create database for new WordPress instance
     * - upload zip with files and database from source WordPress instance
     * - copy files to domain
     * - import database
     * - configure `wp-config.php`
     * - install WordPress
     *
     * @param ApplicationModel $sourceAppModel The source WordPress instance to clone from
     * @return array{
     *     db_host: string,
     *     db_name: string,
     *     db_user: string,
     *     db_password: string
     * } Database connection details for the cloned instance
     */
    protected function cloneFromApp(ApplicationModel $sourceAppModel): array
    {
        // Example implementation

        $username = $this->account()->model()->username;

        $this->account()->fileManager()->compressToZip($sourceAppModel->path, '/tmp/.panelalpha');
        $this->account()->fileManager()->upload('/tmp/.panelalpha', $this->model()->path);

        $result = $this->account()->server()->api()->installWordpressInstance($username, [
            'install_type' => 'clone',
            'domain' => $this->model()->domain,
        ]);

        return [
            'db_host' => $result['db_host'],
            'db_name' => $result['db_name'],
            'db_user' => $result['db_user'],
            'db_password' => $result['db_password'],
        ];
    }

    /**
     * Installs a WordPress instance from a template.
     *
     * Creates a new WordPress installation using a pre-configured template
     * containing WordPress files and database. The process includes domain
     * preparation, database creation, template file extraction, database import,
     * and `wp-config.php` configuration.
     *
     * Template has zip file in path with. Package contains `database.sql` file with database dump
     * and directory with WordPress files, eg. `public_html` directory.
     *
     * Typical flow:
     * - create or get domain
     * - create database
     * - upload files and database from template
     * - copy files
     * - import database
     * - configure `wp-config.php`
     * - install WordPress
     *
     * @param Template $template The template containing WordPress files and database
     * @return array{
     *     db_host: string,
     *     db_name: string,
     *     db_user: string,
     *     db_password: string
     * } Database connection details for the template-based instance
     */
    protected function installFromTemplate(Template $template): array
    {
        // Example implementation

        /** @var array{
         *     domain?: string,
         *     is_complete?: bool,
         *     version?: string,
         *     db_prefix?: string
         * } $templateDetails
         */
        $templateDetails = $template->getDetails();
        $username = $this->account()->model()->username;

        $this->account()->fileManager()->upload($template->path, $this->model()->path);

        $result = $this->account()->server()->api()->installWordpressInstance($username, [
            'install_type' => 'template',
            'domain' => $this->model()->domain,
        ]);

        return [
            'db_host' => $result['db_host'],
            'db_name' => $result['db_name'],
            'db_user' => $result['db_user'],
            'db_password' => $result['db_password'],
        ];
    }

    /**
     * Updates the WordPress installation to a specified version.
     *
     * @param array{
     *     create_backup: bool,
     *     version: string
     * } $params Update parameters:
     *     - `create_backup` (bool): Whether to create a backup before updating
     *     - `version` (string): Target WordPress version to update to
     * @return void
     * @throws Exception If the update process fails
     */
    public function update(array $params): void
    {
        $username = $this->account()->model()->username;
        $instanceId = $this->model()->getDetails()['remote_id'] ?? 1;

        $this->account()->server()->api()->updateWordpressInstance($username, $instanceId, $params);
    }

    /**
     * Deletes the WordPress instance from the hosting server.
     *
     * Removes the WordPress installation including files and database
     * via the hosting server API based on the provided parameters.
     *
     * @param array $params Deletion parameters specifying what to remove
     * @return void
     * @throws Exception If the deletion process fails
     */
    public function delete(array $params): void
    {
        $username = $this->account()->model()->username;
        $instanceId = $this->model()->getDetails()['remote_id'] ?? 1;

        $this->account()->server()->api()->deleteWordpressInstance($username, $instanceId, $params);
    }

    /**
     * Creates a staging environment from the current WordPress instance.
     *
     * Creates an identical copy of the WordPress installation for staging purposes.
     * The process involves archiving current files, exporting the database, setting up
     * a new domain and database, and configuring the staging instance with updated
     * `wp-config.php` settings.
     *
     * Typical flow:
     * - zip files from source WordPress with database
     * - create or get domain for new instance
     * - create database
     * - upload zip
     * - extract files
     * - import database
     * - configure `wp-config.php`
     * - install WordPress
     *
     * @param ApplicationModel $targetAppModel The target model for the staging instance
     * @return array{
     *     path: string,
     *     db_host: string,
     *     db_user: string,
     *     db_name: string,
     *     db_password: string,
     *     remote_id: string
     * } Staging instance details:
     *     - `path` (string): File system path to the staging instance
     *     - `db_host` (string): Database host for the staging instance
     *     - `db_user` (string): Database username
     *     - `db_name` (string): Database name
     *     - `db_password` (string): Database password
     * @throws Exception If staging creation fails
     */
    public function staging(ApplicationModel $targetAppModel): array
    {
        // Example implementation

        $username = $this->account()->model()->username;

        $this->account()->fileManager()->compressToZip($this->model()->path, '/tmp/.panelalpha');
        $this->account()->fileManager()->upload('/tmp/.panelalpha', $targetAppModel->path);

        // Create staging environment via unified API
        $result = $this->account()->server()->api()->installWordpressInstance($username, [
            'install_type' => 'staging',
            'domain' => $targetAppModel->domain,
        ]);

        return [
            'remote_id' => $result['remote_id'],
            'path' => $result['path'],
            'db_name' => $result['db_name'],
            'db_user' => $result['db_user'],
            'db_password' => $result['db_password'],
            'db_host' => $result['db_host'],
        ];
    }

    /**
     * Pushes changes from the current instance to a target instance.
     *
     * Deploys files and database changes from the current WordPress instance
     * (typically staging) to the target instance (typically production) based
     * on the specified parameters for selective synchronization.
     *
     * Typical flow:
     * - zip files from source WordPress with database
     * - upload zip to target WordPress
     * - copy files (if `overwrite_files` is enabled)
     * - import database (if `push_db` is enabled)
     * - push database changes
     *
     * @param ApplicationModel $targetModel The target WordPress instance to push to
     * @param array{
     *     overwrite_files: bool,
     *     push_db: bool,
     *     structural_change_tables: array<string>,
     *     datachange_tables: array<string>
     * } $params Push configuration:
     *     - `overwrite_files` (bool): Whether to overwrite target files
     *     - `push_db` (bool): Whether to push database changes
     *     - `push_views` (bool): Whether to push database views
     *     - `structural_change_tables` (array): Tables with structural changes
     *     - `datachange_tables` (array): Tables with data changes
     * @return void
     * @throws Exception If the push operation fails
     */
    public function pushToApp(ApplicationModel $targetModel, array $params): void
    {
        // Example implementation

        $username = $this->account()->model()->username;
        $sourceInstanceId = $this->model()->id;
        $targetInstanceId = $targetModel->id;

        /** Copy all files from source to target */
        if (!empty($params['overwrite_files'])) {
            $this->account()->fileManager()->compressToZip($this->model()->path, '/tmp/.panelalpha/app_' . $sourceInstanceId . '.zip');
            $this->account()->fileManager()->upload('/tmp/.panelalpha/app_' . $sourceInstanceId . '.zip', '/tmp/.panelalpha/app_' . $targetInstanceId . '.zip');
            $this->account()->fileManager()->extractZip('/tmp/.panelalpha/app_' . $targetInstanceId. '.zip', $targetModel->path);
        }

        /** Migrate whole database */
        if (!empty($params['push_db'])) {
            // implementation
        }


        /** Push Structural Change Tables */
        if (!empty($params['structural_change_tables'])) {
            // implementation
        }

        /** Push Data Change In Tables */
        if (!empty($params['datachange_tables'])) {
            //implementation
        }
    }

    /**
     * Creates a zip archive of the WordPress instance files and database.
     *
     * Packages the entire WordPress installation including all files and
     * database export into a zip archive. The database is included as
     * `database.sql` and files are organized in a `public_html` directory.
     *
     * @return array{
     *     dir: string,
     *     filename: string,
     *     version: string,
     *     is_complete: bool,
     *     db_prefix: string
     * } Archive details:
     *     - `dir` (string): Directory containing the zip file
     *     - `filename` (string): Name of the created zip file
     *     - `version` (string): WordPress version of the archived instance
     *     - `is_complete` (bool): Whether the archive contains complete WordPress files and database. Some integrations can zip partial files.
     *     - `db_prefix` (string): Database table prefix used in the instance
     * @throws Exception If archiving fails
     */
    public function zipApp(): array
    {
        // Example implementation

        $instanceId = $this->model()->id;

        $this->account()->fileManager()->compressToZip($this->model()->path, '/tmp/.panelalpha/app_' . $instanceId . '.zip');

        return [
            'dir' => '/tmp/.panelalpha',
            'filename' => 'app_' . $instanceId,
            'version' => $this->model()->version,
            'is_complete' => true,
            'db_prefix' => $this->model()->getInstallDetails()['db_prefix'],
        ];
    }

    /**
     * Returns usage statistics for the WordPress instance.
     *
     * @return array{
     *     storage: array{
     *         usage: int,
     *         maximum: int|null
     *     },
     *     bandwidth: array{
     *         usage: int,
     *         maximum: null
     *     },
     *     visitors: array{
     *         unique: int,
     *         total: int,
     *         unique_lastmonth: int,
     *         total_lastmonth: int
     *     }
     * } Usage statistics:
     *     - `storage` (array): Disk usage with current usage and maximum limit
     *     - `bandwidth` (array): Network bandwidth usage and limits
     *     - `visitors` (array): Visitor counts including unique and total visitors
     * @throws Exception If statistics retrieval fails
     */
    public function getStats(): array
    {
        $username = $this->account()->model()->username;
        $instanceId = $this->model()->id;

        return $this->account()->server()->api()->getWordpressInstanceStats($username, $instanceId);
    }

    /**
     * Returns bandwidth usage data for a specified date range.
     *
     * @param string $startDate Start date in 'Y-m-d' format
     * @param string $endDate End date in 'Y-m-d' format
     * @param string $groupBy Grouping interval: 'day' or 'month'
     * @return array<string, int> Bandwidth data indexed by date with usage values
     */
    public function getBandwidth(string $startDate, string $endDate, string $groupBy = 'day'): array
    {
        $username = $this->account()->model()->username;
        $instanceId = $this->model()->id;

        return $this->account()->server()->api()->getWordpressInstanceBandwidth($username, $instanceId, $startDate, $endDate, $groupBy);
    }

    /**
     * Returns the number of unique visitors for the current month.
     *
     * @return int Number of unique visitors in the current month
     */
    public function getMonthlyVisitors(): int
    {
        $username = $this->account()->model()->username;
        $instanceId = $this->model()->id;

        return $this->account()->server()->api()->getWordpressInstanceMonthlyVisitors($username, $instanceId);
    }

    /**
     * Returns a list of available webserver log files for the WordPress instance.
     *
     * @return array<array{
     *     path: string|null,
     *     file: string,
     *     mtime: string
     * }> List of log files:
     *     - `path` (string|null): Directory path to the log file
     *     - `file` (string): Log file name
     *     - `mtime` (string): Last modification timestamp
     */
    public function listLogFiles(): array
    {
        $username = $this->account()->model()->username;
        $instanceId = $this->model()->id;

        return $this->account()->server()->api()->listWordpressInstanceLogFiles($username, $instanceId);
    }

    /**
     * Optional
     *
     * Changes the site type for the WordPress instance.
     *
     * Optional method - only if hosting account supports instance types like `live` or `staging`.
     * Updates the hosting configuration to change the instance type
     * (e.g., from development to production) if supported by the server.
     *
     * @param string $type The new site type to set
     * @return void
     */
    public function changeSiteType(string $type): void
    {
        $username = $this->account()->model()->username;
        $instanceId = $this->model()->id;

        // Change WordPress instance site type via API
        $this->account()->server()->api()->changeWordpressInstanceSiteType($username, $instanceId, $type);
    }

    /**
     * Optional.
     *
     * Returns the current site type for the WordPress instance.
     *
     * Optional method - only if hosting account supports instance types like `live` or `staging`.
     * Retrieves the hosting configuration site type setting
     * if supported by the hosting server.
     *
     * @return string The current site type or empty string if not supported
     */
    public function getSiteType(): string
    {
        $username = $this->account()->model()->username;
        $instanceId = $this->model()->id;

        // Get WordPress instance site type via API
        $result = $this->account()->server()->api()->getWordpressInstanceSiteType($username, $instanceId);

        return $result['site_type'] ?? '';
    }
}
