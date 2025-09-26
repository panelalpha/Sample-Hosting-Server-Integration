<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account\Application;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\Application\AbstractBackups;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account\Application;
use App\Lib\Interfaces\Integrations\HostingServer\Account\Application\BackupsInterface;
use App\Models\Backup;
use Exception;

/**
 * Manages backup operations for hosting account applications.
 *
 * Leave body class empty if you want to use PanelAlpha backup system (create backups directly on hosting account)
 * Or fill classes if you use your own backup system.
 *
 * @method Application application()
 */
class Backups extends AbstractBackups implements BackupsInterface
{
    /**
     * Returns all backups for the hosting account application.
     *
     * Provides details about each backup including type, coverage, and storage location.
     * - type (string): Backup creation type - 'manual' or 'automatic'
     * - directory (boolean): Whether the backup includes file system data
     * - database (boolean): Whether the backup includes database data
     * - mode (string): Backup scope - always 'full' for complete backups
     * - has_local_storage (boolean): Whether backup is stored locally - always `false` for remote storage
     * - location_details (array): Remote storage metadata
     *   - remote_backup_id (string): Unique identifier for the remote backup
     *   - filename (string): The backup filename
     *   - filesize (int): Backup file size in bytes
     * - created_at (string): Unix timestamp when the backup was created
     *
     * @return array<array{
     *     type: string,
     *     directory: boolean,
     *     database: boolean,
     *     mode: string,
     *     has_local_storage: boolean,
     *     location_details: array{
     *         remote_backup_id: string,
     *         filename: string,
     *         filesize: int,
     *     },
     *     created_at: string
     * }>
     */
    public function list(): array
    {
        $username = $this->application()->account()->model()->username;

        $backups = $this->application()->account()->server()->api()->listBackups($username);

        return array_map(function ($backup) {
            return [
                'type' => $backup['type'],
                'directory' => $backup['directory'],
                'database' => $backup['database'],
                'mode' => $backup['mode'],
                'has_local_storage' => $backup['has_local_storage'],
                'location_details' => [
                    'remote_backup_id' => $backup['location_details']['remote_backup_id'],
                    'filename' => $backup['location_details']['filename'],
                    'filesize' => $backup['location_details']['filesize']
                ],
                'created_at' => $backup['created_at']
            ];
        }, $backups);
    }


    /**
     * Creates a manual backup of the hosting account application.
     *
     * @param array{
     *     backupDatabase: bool,
     *     backupDirectory: bool
     * } $params Backup configuration:
     *     - `backupDatabase` (bool): Whether to include database in backup
     *     - `backupDirectory` (bool): Whether to include files in backup
     * @return array{success: bool, backup_id?: string, message?: string} Backup creation result
     * @throws Exception
     */
    public function create(array $params): array
    {
        $username = $this->application()->account()->model()->username;

        $result = $this->application()->account()->server()->api()->createBackup($username, $params);

        return $result;
    }

    /**
     * Deletes a backup from the remote storage.
     *
     * Removes the specified backup using its `remote_backup_id` from the location details.
     *
     * @param Backup $backup The backup model instance to delete
     * @return void
     * @throws Exception
     */
    public function delete(Backup $backup): void
    {
        $username = $this->application()->account()->model()->username;
        $remoteId = $backup->location_details['remote_backup_id'];

        $this->application()->account()->server()->api()->deleteBackup($username, $remoteId);
    }

    /**
     * Restores a backup to the hosting account application.
     *
     * Initiates the restoration process using the backup's `remote_backup_id`.
     *
     * @param Backup $backup The backup model instance to restore
     * @return void
     * @throws Exception
     */
    public function restore(Backup $backup): void
    {
        $username = $this->application()->account()->model()->username;
        $remoteId = $backup->location_details['remote_backup_id'];

        $this->application()->account()->server()->api()->restoreBackup($$username, $remoteId);
    }

    /**
     * Returns a download stream for the backup file.
     *
     * Creates a resource stream for downloading the backup using its `remote_backup_id`.
     *
     * @param Backup $backup The backup model instance to download
     * @return resource Stream resource for downloading the backup file
     */
    public function getDownloadStream(Backup $backup)
    {
        $username = $this->application()->account()->model()->username;
        $remoteId = $backup->location_details['remote_backup_id'];

        return $this->application()->account()->server()->api()->getBackupDownloadStream($username, $remoteId);
    }
}
