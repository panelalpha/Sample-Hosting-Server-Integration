<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account\Mysql;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\Mysql\AbstractUsers;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;
use App\Lib\Interfaces\Integrations\HostingServer\Account\Mysql\UsersInterface;
use App\Models\Timestamp;

/**
 * Manages MySQL database users for hosting accounts.
 *
 * Provides methods to create, update, delete, and manage MySQL database users
 * on the hosting server.
 *
 * @method Account account()
 */
class Users extends AbstractUsers implements UsersInterface
{
    /**
     * Returns a list of MySQL database users with their associated databases.
     *
     * Retrieves all MySQL database users for the hosting account and their
     * database access permissions.
     *
     * Field descriptions:
     * - user (string): The database username
     * - databases (array<string>): List of databases the user has access to
     *
     * @return array<array{
     *     user: string,
     *     databases: array<string>
     * }> List of MySQL users with their database associations
     */
    public function list(): array
    {
        return $this->account()->server()->api()->listMysqlUsers($this->account()->model()->username);
    }

    /**
     * Checks if a MySQL database user exists.
     *
     * Verifies whether the specified database user exists on the hosting server.
     *
     * @param string $name The database username to check
     * @return bool True if the user exists, false otherwise
     */
    public function exists(string $name): bool
    {
        return $this->account()->server()->api()->mysqlUserExists($this->account()->model()->username, $name);
    }

    /**
     * Creates a new MySQL database user.
     *
     * Creates a database user with the specified username and password on the
     * hosting server and records the creation timestamp.
     *
     * @param string $name The database username to create
     * @param string $password The password for the database user
     * @return array{
     *     name: string
     * } Array containing the created user's name
     */
    public function create(string $name, string $password): array
    {
        $result = $this->account()->server()->api()->createMysqlUser(
            $this->account()->model()->username,
            $name,
            $password
        );

        Timestamp::firstOrCreate([
            'server_account_id' => $this->account()->model()->id,
            'item_type' => 'mysql_user',
            'item_id' => $name,
        ]);

        return [
            'name' => $name,
        ];
    }

    /**
     * Changes the password for a MySQL database user.
     *
     * Updates the password for an existing database user and updates the
     * modification timestamp.
     *
     * @param string $name The database username
     * @param string $password The new password for the database user
     * @return void
     */
    public function changePassword(string $name, string $password): void
    {
        $this->account()->server()->api()->changeMysqlUserPassword(
            $this->account()->model()->username,
            $name,
            $password
        );

        $timestamp = Timestamp::firstOrCreate([
            'server_account_id' => $this->account()->model()->id,
            'item_type' => 'mysql_user',
            'item_id' => $name,
        ]);
        $timestamp->touch();
    }

    /**
     * Optional. If the hosting server supports renaming database users.
     * Renames a MySQL database user.
     *
     * Changes the username of an existing database user and updates the
     * corresponding timestamp record. This operation is optional and may not
     * be supported by all hosting servers.
     *
     * @param string $name The current database username
     * @param string $newName The new database username
     * @return void
     */
    public function rename(string $name, string $newName): void
    {
        $this->account()->server()->api()->renameMysqlUser(
            $this->account()->model()->username,
            $name,
            $newName
        );

        Timestamp::updateOrCreate(
            [
                'server_account_id' => $this->account()->model()->id,
                'item_type' => 'mysql_user',
                'item_id' => $name,
            ],
            [
                'item_id' => $newName
            ]
        );
    }

    /**
     * Deletes a MySQL database user.
     *
     * Removes the specified database user from the hosting server and deletes
     * the associated timestamp record.
     *
     * @param string $name The database username to delete
     * @return void
     */
    public function delete(string $name): void
    {
        $this->account()->server()->api()->deleteMysqlUser(
            $this->account()->model()->username,
            $name
        );

        $timestamp = Timestamp::firstOrNew([
            'server_account_id' => $this->account()->model()->id,
            'item_type' => 'mysql_user',
            'item_id' => $name,
        ]);
        $timestamp->delete();
    }
}
