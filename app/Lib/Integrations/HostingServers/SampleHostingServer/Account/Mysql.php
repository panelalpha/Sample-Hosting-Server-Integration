<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\AbstractMysql;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;
use App\Lib\Interfaces\Integrations\HostingServer\Account\MysqlInterface;

/**
 * Manages MySQL databases and database users for hosting accounts.
 *
 * Enables clients to manage their databases and users in the Client Area.
 * If the hosting server does not support databases, remove all methods in this class,
 * and the Databases, Users, and Privileges classes in the Mysql directory.
 *
 * @method Account account()
 */
class Mysql extends AbstractMysql implements MysqlInterface
{
    /**
     * Returns database server connection information.
     *
     * Provides the database server hostname and port for connecting to MySQL.
     * This method is optional if the hosting account doesn't support database operations.
     *
     * @return array{ host: string, port: string } Database server connection details
     */
    public function serverInfo(): array
    {
        return [
            'host' => $this->getDatabaseHost(),
            'port' => '3306',
        ];
    }

    /**
     * Returns the database server hostname.
     *
     * Provides the hostname used for MySQL database connections.
     * This method is optional if the hosting account doesn't support database operations.
     *
     * @return string Database server hostname
     */
    public function getDatabaseHost(): string
    {
        return 'localhost';
    }

    /**
     * Generates a valid database name from the provided base name.
     *
     * Creates a database name that complies with the hosting server's naming conventions.
     * This method is optional if the hosting account doesn't support database operations.
     *
     * @param string $base Base name for the database
     * @return string Valid database name
     */
    public function generateValidDbName(string $base = ""): string
    {
        return $base;
    }

    /**
     * Generates a valid database username from the provided base name.
     *
     * Creates a database username that complies with the hosting server's naming conventions.
     * This method is optional if the hosting account doesn't support database operations.
     *
     * @param string $base Base name for the database user
     * @return string Valid database username
     */
    public function generateValidUserName(string $base = ""): string
    {
        return $base;
    }

    /**
     * Returns the SSO URL for phpMyAdmin access.
     *
     * Creates a single sign-on URL that allows direct access to phpMyAdmin
     * for the hosting account, optionally targeting a specific database.
     *
     * @param string|null $databaseId Optional database ID to target in phpMyAdmin
     * @return string SSO URL for phpMyAdmin access
     */
    public function createPhpmyadminSsoUrl(?string $databaseId = null): string
    {
        return $this->account()->server()->api()->createPhpmyadminSso($this->account()->model()->username, $databaseId);
    }
}
