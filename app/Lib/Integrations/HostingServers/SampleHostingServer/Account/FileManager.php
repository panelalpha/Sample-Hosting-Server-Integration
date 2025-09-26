<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\AbstractFileManager;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;
use App\Lib\Interfaces\Integrations\HostingServer\Account\FileManagerInterface;
use Exception;
use Illuminate\Support\Str;

/**
 * Manages file operations for hosting accounts.
 *
 * The class provides the essential methods and may include additional helper methods if needed by the integration.
 *
 * @method Account account()
 */
class FileManager extends AbstractFileManager implements FileManagerInterface
{
    /**
     * Creates a new file with specified content in the given directory.
     *
     * @param string $filename The name of the file to create
     * @param string $contents The content to write to the file
     * @param string $dir The target directory path on the hosting server
     * @return void
     * @throws Exception When file creation fails or directory doesn't exist
     */
    public function saveFileContents(string $filename, string $contents, string $dir): void
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->saveFileContents($username, $filename, $contents, $dir);
    }

    /**
     * Retrieves the content of a file from the hosting server.
     *
     * @param string $path Absolute path to the file on the hosting server
     * @return string The content of the file as a string
     * @throws Exception When file reading fails, file doesn't exist, or access is denied
     */
    public function getFileContents(string $path): string
    {
        $username = $this->account()->model()->username;

        return $this->account()->server()->api()->getFileContents($username, $path);
    }

    /**
     * Creates a unique temporary directory on the hosting server.
     *
     * @return string The absolute path to the created temporary directory
     * @throws Exception When directory creation fails or permissions are insufficient
     */
    public function createTmpDir(): string
    {
        $dir = '/.panelalpha/' . Str::random(8) . '_' . date("Ymd_His");
        $username = $this->account()->model()->username;
        $result = $this->account()->server()->api()->createDirectory($username, $dir);
        return $result['path'] ?? '';
    }

    /**
     * Uploads a file from the local filesystem to the hosting server.
     *
     * @param string $filePath Absolute path to the local file to upload
     * @param string $destinationDir Target directory path on the hosting server
     * @param string|null $asFilename Optional new filename for the uploaded file
     * @return void
     * @throws Exception When upload fails, file doesn't exist, or destination is invalid
     */
    public function upload(string $filePath, string $destinationDir, ?string $asFilename = null): void
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->uploadFile($username, $filePath, $destinationDir, $asFilename);
    }


    /**
     * Deletes a file or directory from the hosting server.
     *
     * @param string $path Absolute path to the file or directory to remove
     * @return void
     * @throws Exception When deletion fails, file doesn't exist, or permissions are insufficient
     */
    public function remove(string $path): void
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->removeFile($username, $path);
    }

    /**
     * Verifies whether a file or directory exists on the hosting server.
     *
     * @param string $path Absolute path to the file or directory to check
     * @return bool True if the file or directory exists, false otherwise
     * @throws Exception When the existence check fails or access is denied
     */
    public function exists(string $path): bool
    {
        $username = $this->account()->model()->username;

        return $this->account()->server()->api()->fileExists($username, $path);
    }

    /**
     * Copies a file or directory to the specified destination on the hosting server.
     *
     * @param string $sourceDir Absolute path to the source file or directory
     * @param string $destDir Absolute path to the destination directory
     * @return void
     * @throws Exception When copying fails, source doesn't exist, or destination is invalid
     */
    public function copy(string $sourceDir, string $destDir): void
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->copyFile($username, $sourceDir, $destDir);
    }

    /**
     * Creates a directory and all necessary parent directories on the hosting server.
     *
     * @param string $path Absolute path to the directory to create
     * @return void
     * @throws Exception When directory creation fails or permissions are insufficient
     */
    public function createDirWithParents(string $path): void
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->createDirectory($username, $path);
    }

    /**
     * Moves a file or directory to a new location on the hosting server.
     *
     * @param string $source Absolute path to the source file or directory
     * @param string $dest Absolute path to the destination location
     * @return void
     * @throws Exception When move fails, source doesn't exist, or destination is invalid
     */
    public function move(string $source, string $dest): void
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->moveFile($username, $source, $dest);
    }

    /**
     * Creates a ZIP archive from a file or directory on the hosting server.
     *
     * @param string $sourcePath Absolute path to the file or directory to compress
     * @param string $destinationPath Absolute path where the ZIP archive will be created
     * @return void
     * @throws Exception When compression fails, source doesn't exist, or destination is invalid
     */
    public function compressToZip(string $sourcePath, string $destinationPath): void
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->compressToZip($username, $sourcePath, $destinationPath);
    }

    /**
     * Extracts a ZIP archive to the specified directory on the hosting server.
     *
     * @param string $sourcePath Absolute path to the ZIP archive to extract
     * @param string $destinationPath Absolute path to the destination directory for extracted files
     * @return void
     * @throws Exception When extraction fails, archive is invalid, or destination doesn't exist
     */
    public function extractZip(string $sourcePath, string $destinationPath): void
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->extractZip($username, $sourcePath, $destinationPath);
    }

    /**
     * Retrieves the file size in bytes from the hosting server.
     *
     * @param string $filePath Absolute path to the file on the hosting server
     * @return string File size in bytes as a string representation
     * @throws Exception When size retrieval fails or file doesn't exist
     */
    public function getFileSize(string $filePath): string
    {
        $username = $this->account()->model()->username;

        $result = $this->account()->server()->api()->getFileSize($username, $filePath);
        return (string)($result['size'] ?? '');
    }

    /**
     * Creates a stream resource for downloading a file from the hosting server.
     *
     * @param string $path Absolute path to the file to download from the hosting server
     * @return false|resource|null Stream resource for file download, null if file not found, or false on failure
     * @throws Exception When stream creation fails or access is denied
     */
    public function getDownloadStream(string $path)
    {
        $username = $this->account()->model()->username;

        return $this->account()->server()->api()->getFileDownloadStream($username, $path);
    }

    /**
     * Uploads file content from a stream resource to the hosting server.
     *
     * @param resource $stream Readable stream resource containing the file data
     * @param string $destinationDir Target directory path on the hosting server
     * @param string $filename Name for the uploaded file on the hosting server
     * @param int $filesize Total size of the file in bytes (must be accurate)
     * @return void
     * @throws Exception When upload fails, stream is invalid, or destination doesn't exist
     */
    public function uploadFromStream($stream, string $destinationDir, string $filename, int $filesize): void
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->uploadFromStream($username, $stream, $destinationDir, $filename, $filesize);
    }
}