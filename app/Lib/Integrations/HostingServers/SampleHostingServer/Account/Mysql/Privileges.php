<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account\Mysql;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\Mysql\AbstractPrivileges;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;
use App\Lib\Interfaces\Integrations\HostingServer\Account\Mysql\PrivilegesInterface;
use App\Models\Timestamp;

/**
 * Manages MySQL database user privileges for hosting accounts.
 *
 * Provides methods to get, set, and delete MySQL database user privileges
 * on the hosting server.
 *
 * @method Account account()
 */
class Privileges extends AbstractPrivileges implements PrivilegesInterface
{
    /**
     * Returns privileges for a MySQL database user.
     *
     * Retrieves the list of privileges granted to the specified user for the
     * given database. If the user has all privileges, only `ALL PRIVILEGES`
     * is returned.
     *
     * @param string $user The database username
     * @param string $database The database name
     * @return array<string> List of privilege names granted to the user
     */
    public function getUserPrivileges(string $user, string $database): array
    {
        return $this->account()->server()->api()->getUserPrivileges(
            $this->account()->model()->username,
            $user,
            $database
        );
    }

    /**
     * Sets privileges for a MySQL database user.
     *
     * Grants the specified privileges to the user for the given database
     * and updates the modification timestamp.
     *
     * @param string $user The database username
     * @param string $database The database name
     * @param string $privileges Comma-separated list of privileges to grant
     * @return void
     */
    public function setUserPrivileges(string $user, string $database, string $privileges): void
    {
        $this->account()->server()->api()->setUserPrivileges(
            $this->account()->model()->username,
            $user,
            $database,
            $privileges
        );

        $timestamp = Timestamp::firstOrCreate([
            'server_account_id' => $this->account()->model()->id,
            'item_type' => 'mysql_user',
            'item_id' => $user,
        ]);
        $timestamp->touch();
    }

    /**
     * Deletes all privileges for a MySQL database user.
     *
     * Revokes all privileges granted to the user for the given database
     * and removes the associated timestamp record.
     *
     * @param string $user The database username
     * @param string $database The database name
     * @return void
     */
    public function deleteUserPrivileges(string $user, string $database): void
    {
        $this->account()->server()->api()->deleteUserPrivileges(
            $this->account()->model()->username,
            $user,
            $database
        );

        $timestamp = Timestamp::firstOrNew([
            'server_account_id' => $this->account()->model()->id,
            'item_type' => 'mysql_user',
            'item_id' => $user,
        ]);
        $timestamp->delete();
    }
}
