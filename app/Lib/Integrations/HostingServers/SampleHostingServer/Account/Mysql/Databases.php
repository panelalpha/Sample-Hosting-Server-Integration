<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account\Mysql;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\Mysql\AbstractDatabases;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;
use App\Lib\Interfaces\Integrations\HostingServer\Account\Mysql\DatabasesInterface;
use App\Models\Timestamp;

/**
 * Manages MySQL databases for hosting accounts.
 *
 * Provides methods to create, delete, rename, and list MySQL databases
 * on the hosting server, including database prefix management and existence checking.
 *
 * @method Account account()
 */
class Databases extends AbstractDatabases implements DatabasesInterface
{
    /**
     * Returns a list of MySQL databases for the hosting account.
     *
     * Retrieves all databases associated with the account from the hosting server.
     *
     * Field descriptions for returned databases:
     * - database (string): The database name
     * - disk_usage (int): Current disk usage in bytes
     *
     * @return array<array{
     *     database: string,
     *     disk_usage: int
     * }> List of databases with their disk usage information
     */
    public function list(): array
    {
        return $this->account()->server()->api()->listDatabases($this->account()->model()->username);
    }

    /**
     * Creates a MySQL database on the hosting server.
     *
     * Creates a new database with the specified name and records the creation
     * timestamp for tracking purposes.
     *
     * @param string $name The database name to create
     * @return array{ name: string } The created database information
     */
    public function create(string $name): array
    {
        $result = $this->account()->server()->api()->createDatabase($this->account()->model()->username, ['name' => $name]);

        Timestamp::firstOrCreate([
            'server_account_id' => $this->account()->model()->id,
            'item_type' => 'mysql_database',
            'item_id' => $name,
        ]);

        return [
            'name' => $name
        ];
    }

    /**
     * Deletes a MySQL database from the hosting server.
     *
     * Removes the specified database and cleans up associated timestamp records.
     *
     * @param string $name The database name to delete
     * @return void
     */
    public function delete(string $name): void
    {
        $this->account()->server()->api()->deleteDatabase($this->account()->model()->username, $name);

        $timestamp = Timestamp::firstOrNew([
            'server_account_id' => $this->account()->model()->id,
            'item_type' => 'mysql_database',
            'item_id' => $name,
        ]);
        $timestamp->delete();
    }

    /**
     * Optional. If the hosting server supports renaming databases.
     * Renames a MySQL database on the hosting server.
     *
     * Changes the database name from `$name` to `$newName` and updates the
     * timestamp tracking record. This operation is optional and depends on
     * hosting server support.
     *
     * @param string $name The current database name
     * @param string $newName The new database name
     * @return array{ name: string } The created database information
     */
    public function rename(string $name, string $newName): array
    {
        $this->account()->server()->api()->renameDatabase($this->account()->model()->username, $name, $newName);

        $timestamp = Timestamp::firstOrCreate([
            'server_account_id' => $this->account()->model()->id,
            'item_type' => 'mysql_database',
            'item_id' => $newName,
        ]);
        $timestamp->touch();

        return [
            'name' => $name
        ];
    }

    /**
     * Returns the database prefix for this hosting account.
     *
     * Provides a standard prefix that will be prepended to database names
     * created through this integration.
     *
     * @return string The database prefix (e.g., "wp_")
     */
    public function getPrefix(): string
    {
        return "wp_";
    }


    /**
     * Checks if a MySQL database exists on the hosting server.
     *
     * Verifies whether a database with the specified name exists for this
     * hosting account.
     *
     * @param string $name The database name to check
     * @return bool True if the database exists, false otherwise
     */
    public function exists(string $name): bool
    {
        return $this->account()->server()->api()->databaseExists($this->account()->model()->username, $name);
    }
}
