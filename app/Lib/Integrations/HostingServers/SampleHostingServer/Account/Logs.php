<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\AbstractLogs;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;
use App\Lib\Interfaces\Integrations\HostingServer\Account\LogsInterface;
use Exception;

/**
 * Manages log file operations for hosting accounts.
 *
 * @method Account account()
 */
class Logs extends AbstractLogs implements LogsInterface
{
    /**
     * Retrieves a list of all available webserver log files for a domain.
     *
     * @param string $domain The domain name to retrieve log files for
     * @return array<array{
     *     name: string,
     *     plain_text: bool,
     *     size: ?int
     * }> List of available log files containing:
     *   - name (string): The log file name (e.g., "access.log", "error.log")
     *   - plain_text (bool): Whether the file is in plain text format or compressed
     *   - size (int|null): File size in bytes, null if size cannot be determined
     * @throws Exception When log file listing fails or domain doesn't exist
     */
    public function listWebserverLogFiles(string $domain): array
    {
        $username = $this->account()->model()->username;

        return $this->account()->server()->api()->listWebserverLogFiles($username, $domain);
    }

    /**
     * Retrieves the complete content of a specific webserver log file.
     *
     * @param string $domain The domain name to retrieve logs for
     * @param string $filename The log file name to retrieve (must exist in listWebserverLogFiles())
     * @return string The complete log file content as a string
     * @throws Exception When log file retrieval fails, file doesn't exist, or access is denied
     */
    public function getWebserverLogFileContent(string $domain, string $filename): string
    {
        $username = $this->account()->model()->username;

        return $this->account()->server()->api()->getWebserverLogFileContent($username, $domain, $filename);
    }

    /**
     * Creates a stream resource for accessing webserver log content.
     *
     * @param string $domain The domain name to stream logs for
     * @param string $filename The log file name to stream (must exist in listWebserverLogFiles())
     * @return resource|null Stream resource for reading log content, or null if stream cannot be established
     * @throws Exception When stream creation fails, file doesn't exist, or access is denied
     */
    public function getWebserverLogStream(string $domain, string $filename)
    {
        $username = $this->account()->model()->username;

        return $this->account()->server()->api()->getWebserverLogStream($username, $domain, $filename);
    }


    /**
     * Retrieves PHP error logs for the specified domain.
     *
     * @param string $domain The domain name to retrieve PHP error logs for
     * @return string The complete PHP error log content as a string
     * @throws Exception When PHP log retrieval fails or domain doesn't exist
     */
    public function getPhpLogs(string $domain): string
    {
        $username = $this->account()->model()->username;

        return $this->account()->server()->api()->getPhpLogs($username, $domain);
    }

    /**
     * Creates a stream resource for accessing PHP error logs.
     *
     * @param string $domain The domain name to stream PHP error logs for
     * @return resource|null Stream resource for reading PHP error logs, or null if stream cannot be established
     * @throws Exception When stream creation fails or domain doesn't exist
     */
    public function getPhpLogsStream(string $domain)
    {
        $username = $this->account()->model()->username;

        return $this->account()->server()->api()->getPhpLogsStream($username, $domain);
    }
}
