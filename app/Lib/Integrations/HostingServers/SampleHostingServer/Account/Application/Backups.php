<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServer\Account\Application;

use App\Lib\Helper;
use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\Application\AbstractBackups;
use App\Lib\Integrations\HostingServers\SampleHostingServer\Account\Application;
use App\Lib\Integrations\HostingServers\SampleHostingServer\Account\FileManager;
use App\Lib\Interfaces\Integrations\HostingServer\Account\Application\BackupsInterface;
use App\Models\Backup;
use App\Models\BackupItem;
use App\Models\Setting;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Manages backup operations for hosting account applications.
 *
 * This class demonstrates the custom backup system (provider-managed, WpCloud-like pattern).
 * The hosting provider exposes its own backup API; PanelAlpha delegates create, list,
 * delete, download, and restore to that API instead of building archives on the server.
 *
 * PanelAlpha detects which strategy an integration uses via `hasCustomBackupSystem()` on
 * the main server class. That method returns `true` only when `list()` is declared in this
 * class (not inherited from `AbstractBackups`). Implementing `list()` here activates the
 * custom backup system flow and triggers `syncRemoteBackups()` when listing backups in the UI.
 *
 * default backup system (PanelAlpha-managed, provider has NO own backup API):
 * - Leave this class empty: `class Backups extends AbstractBackups implements BackupsInterface {}`
 * - Do NOT implement `list()` — `hasCustomBackupSystem()` will return `false`
 * - PanelAlpha handles everything via `AbstractBackups`: zip files + database dump on hosting,
 *   optional upload to remote storage, restore from local or remote archives
 * - Required outside this class: working `FileManager`, `runProcessWithAsyncPolling` on the
 *   account, WP-CLI / mysqldump access (provided by `AbstractBackups`)
 * - Not required in this class: all methods below, backup API methods in the API client
 * - Inherited from `AbstractBackups` (no override needed): `createOnHosting`, `uploadToRemote`,
 *   `restoreFromHosting`, `extractFromHosting`, `extractFromRemote`, `restoreFromRemote`,
 *   `deleteFromHosting`, `getDownloadStream`, `withBackupFiles`, `hasAvailableBackupSpace`,
 *   `canCreateManual`, `canCreateAutomatic`, `usage` (plan-based limits)
 * - Default `$supportCombinedBackup = true` — one backup can include both files and database
 *
 * custom backup system (provider-managed, provider HAS own backup API — this sample):
 * - Implement `list()` and the integration-specific methods below
 * - Add matching methods in the API client: `listBackups`, `createBackup`, `deleteBackup`,
 *   `getInfoBackup`, `downloadBackup`
 * - Required minimum: `list`, `requestCreateOnIntegration`, `awaitOnIntegration`,
 *   `getSizeOnIntegration`, `deleteFromIntegration`, `restoreFromIntegration`,
 *   `getDownloadStream` / `getDownloadStreamFromIntegration`, `getFilename`
 * - Often required (provider-dependent): `uploadToRemote`, `restoreFromRemote`, `usage`,
 *   `canCreateManual`, `canCreateAutomatic`
 * - Set `$supportCombinedBackup = false` when the provider stores files and database as
 *   separate backups (creates two `BackupItem` records per manual backup request)
 *
 * @method Application application()
 */
class Backups extends AbstractBackups implements BackupsInterface
{
    /**
     * Maximum seconds to wait for a provider webhook in `awaitOnIntegration()`.
     *
     * Required for: custom backup system only.
     * Not required for: default backup system.
     */
    public const TIMEOUT = 3600;

    /**
     * Whether a single backup item can include both files and database.
     *
     * - `true` (default in `AbstractBackups` for default backup system): one combined zip per backup
     * - `false` (this sample): provider stores files and database separately; PanelAlpha
     *   creates one `BackupItem` per type
     *
     * Required for: custom backup system when the provider does not support combined backups.
     * Not required for: default backup system — remove this property to use the default `true`.
     */
    public static bool $supportCombinedBackup = false;

    /**
     * Lists backups stored on the hosting provider and returns them in PanelAlpha format.
     *
     * Called by `Application::syncRemoteBackups()` to import provider backups into PanelAlpha.
     * Implementing this method sets `hasCustomBackupSystem()` to `true` on the server class.
     *
     * Required for: custom backup system.
     * Not required for: default backup system — remove this method and leave the class empty.
     *
     * Each returned entry contains:
     * - external_id (string): Provider-side backup identifier
     * - size_bytes (int): Backup size in bytes
     * - type (string): `manual` or `automatic`
     * - directory (bool): Whether the backup includes file system data
     * - database (bool): Whether the backup includes database data
     * - mode (string): Backup scope — typically `full`
     * - created_at (Carbon, optional): When the backup was created on the provider
     *
     * @return array<array{
     *     external_id: string,
     *     size_bytes: int,
     *     type: string,
     *     directory: boolean,
     *     database: boolean,
     *     mode: string,
     *     created_at?: Carbon
     * }>
     * @throws Exception When backup listing fails or the API is unreachable
     */
    public function list(): array
    {
        $username = $this->getAccountIdentifier();
        $result = $this->application()->account()->server()->api()->listBackups($username);

        $list = [];
        foreach ($result as $backup) {
            $hasDirectory = $backup['type'] === 'fs' || $backup['type'] === 'ondemand-fs';
            $hasDatabase = $backup['type'] === 'db' || $backup['type'] === 'ondemand-db';

            $filesize = 0;
            if (isset($backup['bytes'])) {
                $filesize = (int)$backup['bytes'];
            }

            $list[] = [
                'external_id' => $backup['atomic_backup_id'],
                'size_bytes' => $filesize,
                'type' => Str::startsWith($backup['type'], 'ondemand') ? Backup::TYPE_MANUAL : Backup::TYPE_AUTOMATIC,
                'directory' => $hasDirectory,
                'database' => $hasDatabase,
                'mode' => 'full',
                'created_at' => Carbon::parse($backup['backup_timestamp']),
            ];
        }

        return $list;
    }

    /**
     * Requests asynchronous backup creation on the hosting provider.
     *
     * Returns a provider request ID stored in `BackupItem` details as `external_request_id`.
     * PanelAlpha then calls `awaitOnIntegration()` to wait for the webhook confirming completion.
     *
     * Required for: custom backup system.
     * Not required for: default backup system (inherited `createOnHosting` from `AbstractBackups`).
     *
     * @param BackupItem $backupItem The backup item being created (files or database, not both)
     * @return string Provider-side request identifier (`atomic_backup_request_id`)
     * @throws Exception When validation fails or the API request fails
     */
    public function requestCreateOnIntegration(BackupItem $backupItem): string
    {
        if (!$backupItem->directory && !$backupItem->database) {
            throw new Exception(Helper::t('api/messages.backup_directory_or_database_required'));
        }

        if ($backupItem->directory && $backupItem->database) {
            throw new Exception(Helper::t('api/custom_validation.one_type_for_backup'));
        }

        $type = $backupItem->directory ? 'fs' : 'db';

        $result = $this->application()->account()->server()->api()
            ->createBackup($this->getAccountIdentifier(), $type);

        return (string)$result['atomic_backup_request_id'];
    }

    /**
     * Waits until the provider confirms backup creation via webhook.
     *
     * Polls until `external_request_id` is cleared from `BackupItem` details, which indicates
     * the provider webhook has delivered the final `external_id`.
     *
     * Required for: custom backup system when the provider uses async creation with webhooks.
     * Not required for: default backup system.
     *
     * @param BackupItem $backupItem The backup item awaiting provider confirmation
     * @return void
     * @throws Exception When the timeout is exceeded before the webhook arrives
     */
    public function awaitOnIntegration(BackupItem $backupItem): void
    {
        $attempt = 0;

        while ($attempt < self::TIMEOUT) {
            sleep(1);

            $backupItem->refresh();
            $externalRequestId = $backupItem->getDetails()['external_request_id'] ?? null;
            if ($externalRequestId !== null) {
                $attempt++;
                continue;
            }
            return;
        }

        throw new Exception(__('api/messages.incident.connection_timeout.label'));
    }

    /**
     * Fetches the backup file size from the hosting provider after creation.
     *
     * Required for: custom backup system.
     * Not required for: default backup system (size is read from the local file via `FileManager`).
     *
     * @param BackupItem $backupItem The backup item with a confirmed `external_id`
     * @return int Backup size in bytes
     * @throws Exception When the backup is not found or the API call fails
     */
    public function getSizeOnIntegration(BackupItem $backupItem): int
    {
        $username = $this->getAccountIdentifier();
        $remoteBackupId = $backupItem->external_id;
        if ($remoteBackupId === null) {
            throw new Exception(Helper::t('api/messages.backup_not_found'));
        }

        /** @var array{atomic_backup_id: string, bytes: string} $details */
        $details = $this->application()->account()->server()->api()->getInfoBackup($username, $remoteBackupId);

        return (int)$details['bytes'];
    }

    /**
     * Streams backup data from the hosting server directly to PanelAlpha remote storage.
     *
     * Overrides the default `AbstractBackups::uploadToRemote()` to match the provider's archive
     * format (`.tar.bz2` for files, `.sql.gz` for database). Used when a backup container is
     * configured and the backup should be stored remotely.
     *
     * Required for: custom backup system when using remote backup containers with streaming upload.
     * Not required for: default backup system — the default `AbstractBackups` implementation is sufficient.
     *
     * @param BackupItem $backupItem The backup item to upload
     * @return string Remote storage path where the backup was stored
     * @throws Exception When validation fails, webhook token is missing, or streaming fails
     */
    public function uploadToRemote(BackupItem $backupItem): string
    {
        $includeFiles = $backupItem->directory;
        $includeDatabase = $backupItem->database;
        $fromDate = $backupItem->backup->getIncrementalFromDate();

        if (!$includeFiles && !$includeDatabase) {
            throw new Exception(Helper::t('api/messages.backup_directory_or_database_required'));
        }

        if ($fromDate !== null && !$includeDatabase) {
            throw new Exception(Helper::t('api/messages.backup_database_is_required_with_incremental_backup'));
        }

        $token = $backupItem->getWebhookToken();
        if ($token === null) {
            throw new Exception(Helper::t('api/messages.invalid_webhook_token'));
        }

        $appUrl = trim(Setting::get('userapp.url'), '/');
        $uploadUrl = "{$appUrl}/api/webhook/backups?";
        $uploadQuery = [
            'token' => $token,
        ];

        $remoteDir = Str::slug($this->application()->model()->domain) . '_' . $backupItem->backup->created_at->format('Y-m-d_H-i-s');

        $scriptParts = [];
        $scriptParts[] = 'set -euo pipefail';

        if ($backupItem->database) {
            $remotePath = $remoteDir . '/database.sql.gz';
            $uploadQuery['remote_path'] = $remotePath;

            $scriptParts[] =
                'wp --skip-plugins --skip-themes db export - '
                . ' | gzip -c'
                . ' | curl -s -S -k -X POST -H "Content-Type: application/gzip" --data-binary @- '
                . escapeshellarg($uploadUrl . http_build_query($uploadQuery));

            $this->runProcessWithAsyncPolling($scriptParts);

            return $remotePath;
        }

        $remotePath = $remoteDir . '/files.tar.bz2';
        $uploadQuery['remote_path'] = $remotePath;

        $appPath = $this->application()->model()->path;
        $source = basename($appPath);
        $workDir = dirname($this->application()->account()->fileManager()->getFullPath($appPath));

        if ($fromDate !== null) {
            $tarCmd =
                'tar -cjf -'
                . ' --newer-mtime=' . escapeshellarg($fromDate)
                . ' ' . escapeshellarg($source);
        } else {
            $tarCmd =
                'tar -cjf -'
                . ' ' . escapeshellarg($source);
        }

        $scriptParts[] =
            $tarCmd
            . ' | curl -s -S -k -X POST -H "Content-Type: application/x-bzip2" --data-binary @- '
            . escapeshellarg($uploadUrl . http_build_query($uploadQuery));

        $this->runProcessWithAsyncPolling($scriptParts, $workDir);

        return $remotePath;
    }

    /**
     * Requests backup deletion on the hosting provider.
     *
     * Required for: custom backup system.
     * Not required for: default backup system (inherited `deleteFromHosting` from `AbstractBackups`).
     *
     * @param BackupItem $backupItem The backup item to delete (must have `external_id`)
     * @return string Provider-side deletion request identifier
     * @throws Exception When the backup is not found or the API call fails
     */
    public function deleteFromIntegration(BackupItem $backupItem): string
    {
        $backupId = $backupItem->external_id;
        if ($backupId === null) {
            throw new Exception(Helper::t('api/messages.backup_not_found'));
        }

        $result = $this->application()->account()->server()->api()
            ->deleteBackup($this->getAccountIdentifier(), $backupId);

        return (string)$result['atomic_backup_request_id'];
    }

    /**
     * Restores a backup by downloading it from the provider API and applying it on hosting.
     *
     * Downloads the archive via `downloadBackup`, extracts it on the server, then restores
     * files and/or database. Archive layout must match the provider's format (e.g. `htdocs/`
     * for files, `{backup_id}.sql` for database).
     *
     * Required for: custom backup system.
     * Not required for: default backup system (inherited `restoreFromHosting` from `AbstractBackups`).
     *
     * @param BackupItem $backupItem The backup item to restore (must have `external_id`)
     * @return void
     * @throws Exception When the backup is not found, download fails, or restore fails
     */
    public function restoreFromIntegration(BackupItem $backupItem): void
    {
        $restoreDetails = $backupItem->getRestoreDetails();

        $restoreDirectory = $restoreDetails['restoreDirectory'];
        $restoreDatabase = $restoreDetails['restoreDatabase'];
        $deleteExistingFiles = $restoreDetails['deleteExistingFiles'];

        /** @var FileManager $fileManager */
        $fileManager = $this->application()->account()->fileManager();
        $tmpDir = $fileManager->createTmpDir();

        try {
            $backupId = $backupItem->external_id;
            if ($backupId === null) {
                throw new Exception(Helper::t('api/messages.backup_not_found'));
            }

            /** @var resource $stream */
            $stream = $this->application()->account()->server()->api()
                ->downloadBackup($this->getAccountIdentifier(), $backupId);

            $backupPath = $tmpDir . '/' . $this->getFilename($backupItem);
            $fileManager->put($backupPath, $stream);

            $backupDir = $tmpDir . '/backup';
            $fileManager->createDirectory($backupDir);
            $fileManager->unzip($backupPath, $backupDir);

            if ($restoreDirectory) {
                $existingFilesPath = 'app_' . date('Y-m-d_H-i-s') . '.tar.gz';
                $fileManager->zip('/', $existingFilesPath);

                $fileManager->syncFiles($backupDir . '/htdocs/wp-content', '');

                if ($deleteExistingFiles) {
                    $fileManager->remove($existingFilesPath);
                }
            }

            if ($restoreDatabase) {
                $sqlFile = $fileManager->getFullPath($backupDir . '/' . $backupId . '.sql');
                $this->application()->wordpress()->database()->import($sqlFile);
            }
        } finally {
            $fileManager->removeDir('/.panelalpha');
        }
    }

    /**
     * Restores a backup by streaming it from PanelAlpha remote storage to the hosting server.
     *
     * Used when the backup is stored in a remote container (`remote_path` is set) rather than
     * on the provider's own backup system. Streams via curl + gunzip/tar directly on hosting.
     *
     * Required for: custom backup system when using remote backup containers with streaming restore.
     * Not required for: default backup system — inherited from `AbstractBackups`.
     *
     * @param BackupItem $backupItem The backup item to restore (must have `remote_path`)
     * @return void
     * @throws Exception When the backup is not found, webhook token is missing, or format is unsupported
     */
    public function restoreFromRemote(BackupItem $backupItem): void
    {
        $restoreDetails = $backupItem->getRestoreDetails();

        $restoreDirectory = $restoreDetails['restoreDirectory'];
        $restoreDatabase = $restoreDetails['restoreDatabase'];

        $remotePath = $backupItem->remote_path;
        if ($remotePath === null) {
            throw new Exception(Helper::t('api/messages.backup_not_found'));
        }

        $token = $backupItem->getWebhookToken();
        if ($token === null) {
            throw new Exception(Helper::t('api/messages.invalid_webhook_token'));
        }

        $appUrl = trim(Setting::get('userapp.url'), '/');
        $downloadUrl = "{$appUrl}/api/webhook/backups?" . http_build_query(['token' => $token]);

        $appPath = $this->application()->model()->path;
        $workDir = dirname($appPath);

        $scriptParts = ['set -euo pipefail'];

        if ($restoreDatabase && Str::endsWith($remotePath, '.sql.gz')) {
            $scriptParts[] =
                'curl -s -S -k ' . escapeshellarg($downloadUrl)
                . ' | gunzip'
                . ' | wp'
                . ' --skip-plugins --skip-themes db import -';

            $this->runProcessWithAsyncPolling($scriptParts);
        } elseif ($restoreDirectory && Str::endsWith($remotePath, '.tar.bz2')) {
            $scriptParts[] =
                'curl -s -S -k ' . escapeshellarg($downloadUrl)
                . ' | tar -xjf - -C ' . escapeshellarg($workDir);

            $this->runProcessWithAsyncPolling($scriptParts, $workDir);
        } else {
            throw new Exception('Unsupported remote backup format for streaming restore: ' . $remotePath);
        }
    }

    /**
     * Returns a download stream for a backup stored on the hosting provider.
     *
     * Required for: custom backup system.
     * Not required for: default backup system (inherited `getDownloadStreamFromHosting` from `AbstractBackups`).
     *
     * @param BackupItem $backupItem The backup item to download (must have `external_id`)
     * @return resource Stream resource for the backup file
     * @throws Exception When the backup is not found or the API call fails
     */
    public function getDownloadStreamFromIntegration(BackupItem $backupItem)
    {
        $backupId = $backupItem->external_id;
        if ($backupId === null) {
            throw new Exception(Helper::t('api/messages.backup_not_found'));
        }

        return $this->application()->account()->server()->api()
            ->downloadBackup($this->getAccountIdentifier(), $backupId);
    }

    /**
     * Returns a download stream for the backup file.
     *
     * Delegates to `getDownloadStreamFromIntegration()` because backups live on the provider.
     * In default backup system, the default implementation reads from the local file on hosting.
     *
     * Required for: custom backup system.
     * Not required for: default backup system (inherited from `AbstractBackups`).
     *
     * @param BackupItem $backupItem The backup item to download
     * @return resource Stream resource for the backup file
     * @throws Exception When the backup is not found or stream creation fails
     */
    public function getDownloadStream(BackupItem $backupItem)
    {
        return $this->getDownloadStreamFromIntegration($backupItem);
    }

    /**
     * Returns current backup usage against provider-specific limits.
     *
     * This sample enforces a maximum of one manual file backup and one manual database backup,
     * matching the WpCloud provider constraints. Keys differ from plan-based limits used by
     * the default backup system (`backups_number`, `backups_size`).
     *
     * Required for: custom backup system when the provider imposes its own backup quotas.
     * Not required for: default backup system — inherited `usage` from `AbstractBackups` uses plan limits.
     *
     * @return array{
     *     database_manual_backups_number: array{total: int, max: int},
     *     directory_manual_backups_number: array{total: int, max: int}
     * }
     */
    public function usage(): array
    {
        $backups = $this->application()->model()->backups;

        $databaseManualBackups = $backups->filter(function (Backup $backup) {
            return $backup->type === 'manual' && $backup->database && ($backup->getAsyncStatus()['create'] ?? 'finished') === 'finished';
        });
        $directoryManualBackups = $backups->filter(function (Backup $backup) {
            return $backup->type === 'manual' && $backup->directory && ($backup->getAsyncStatus()['create'] ?? 'finished') === 'finished';
        });

        return [
            'database_manual_backups_number' => [
                'total' => $databaseManualBackups->count(),
                'max' => 1,
            ],
            'directory_manual_backups_number' => [
                'total' => $directoryManualBackups->count(),
                'max' => 1,
            ],
        ];
    }

    /**
     * Checks whether a new manual backup of the given type can be created.
     *
     * Enforces provider-specific limits (one file backup and one database backup in this sample).
     *
     * Required for: custom backup system when the provider has per-type backup quotas.
     * Not required for: default backup system — inherited `canCreateManual` from `AbstractBackups` uses plan limits.
     *
     * @param bool $backupDirectory Whether the requested backup includes files
     * @param bool $backupDatabase Whether the requested backup includes database
     * @return bool True if creation is allowed, false if the quota is exceeded
     */
    public function canCreateManual(bool $backupDirectory, bool $backupDatabase): bool
    {
        $backups = $this->application()->model()->backup_details;

        if (
            $backupDirectory
            && collect($backups)->where('type', 'manual')->where('directory', true)->where('async_status->create', 'finished')->count() >= 1
        ) {
            return false;
        }

        if (
            $backupDatabase
            && collect($backups)->where('type', 'manual')->where('database', true)->where('async_status->create', 'finished')->count() >= 1
        ) {
            return false;
        }

        return true;
    }

    /**
     * Checks whether automatic backups can be created by PanelAlpha.
     *
     * Returns `false` because this sample provider manages its own automatic backups
     * (listed via `list()` with type `automatic`).
     *
     * Required for: custom backup system when the provider handles automatic backups externally.
     * Not required for: default backup system — inherited default allows automatic backups per plan limits.
     *
     * @return bool True if PanelAlpha may create automatic backups, false otherwise
     */
    public function canCreateAutomatic(): bool
    {
        return false;
    }

    /**
     * Returns the expected filename for a backup archive on the provider.
     *
     * Used during restore and download to name the temporary file on hosting. Must match the
     * archive format returned by the provider's `downloadBackup` API.
     *
     * Required for: custom backup system.
     * Not required for: default backup system (filename is derived from `local_path` in `AbstractBackups`).
     *
     * @param BackupItem $backupItem The backup item (files or database)
     * @return string Archive filename (e.g. `{id}.tar.bz2` or `{id}.sql.gz`)
     */
    public function getFilename(BackupItem $backupItem): string
    {
        $type = $backupItem->directory ? 'directory' : 'database';
        $backupId = $backupItem->external_id;

        return match ($type) {
            'directory' => $backupId . '.tar.bz2',
            'database' => $backupId . '.sql.gz',
        };
    }

    /**
     * Returns the account identifier used in provider API calls.
     *
     * In this sample the hosting account username is used. Other integrations may use
     * a remote site ID from account details (e.g. `$account->getRemoteId()`).
     */
    private function getAccountIdentifier(): string
    {
        return $this->application()->account()->model()->username;
    }
}
