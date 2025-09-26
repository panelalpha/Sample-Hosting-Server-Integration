<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\AbstractCronJobs;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account;
use App\Lib\Interfaces\Integrations\HostingServer\Account\CronJobsInterface;

/**
 * Manages cron job operations for hosting server accounts.
 *
 * Enables clients to manage their cron jobs in the Client Area.
 * If the hosting server does not support cron jobs, remove methods in this class
 * and set `supported()` to `false`.
 *
 * @method Account account()
 */
class CronJobs extends AbstractCronJobs implements CronJobsInterface
{
    /**
     * Retrieves all scheduled cron jobs for the hosting account.
     *
     * @return array<array{
     *     id: string,
     *     minute: string,
     *     hour: string,
     *     day_of_month: string,
     *     month: string,
     *     day_of_week: string,
     *     command: string
     *  }> List of cron jobs containing:
     *   - id (string): Unique identifier for the cron job
     *   - minute (string): Minute field (0-59) or cron expression
     *   - hour (string): Hour field (0-23) or cron expression
     *   - day_of_month (string): Day of month field (1-31) or cron expression
     *   - month (string): Month field (1-12) or cron expression
     *   - day_of_week (string): Day of week field (0-7) or cron expression
     *   - command (string): Shell command or script to execute
     * @throws Exception When cron job retrieval fails or server is unreachable
     */
    public function list(): array
    {
        $username = $this->account()->model()->username;

        return $this->account()->server()->api()->listCronJobs($username);
    }

    /**
     * Creates a new scheduled cron job for the hosting account.
     *
     * @param array{
     *      minute: string,
     *      hour: string,
     *      day_of_month: string,
     *      month: string,
     *      day_of_week: string,
     *      command: string
     * } $params Cron job configuration containing:
     *   - minute (string): Minute field (0-59, *, *\/N, range, list)
     *   - hour (string): Hour field (0-23, *, *\/N, range, list)
     *   - day_of_month (string): Day of month field (1-31, *, *\/N, range, list)
     *   - month (string): Month field (1-12, *, *\/N, range, list)
     *   - day_of_week (string): Day of week field (0-7, *, *\/N, range, list)
     *   - command (string): Shell command or script path to execute
     * @return void
     * @throws Exception When cron job creation fails, invalid parameters provided, or server error occurs
     */
    public function create(array $params): void
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->createCronJob($username, $params);
    }

    /**
     * Modifies an existing cron job's schedule and command configuration.
     *
     * @param string $id Unique cron job identifier to update
     * @param array{
     *      minute: string,
     *      hour: string,
     *      day_of_month: string,
     *      month: string,
     *      day_of_week: string,
     *      command: string
     * } $params Updated cron job configuration containing:
     *   - minute (string): New minute field (0-59, *, *\/N, range, list)
     *   - hour (string): New hour field (0-23, *, *\/N, range, list)
     *   - day_of_month (string): New day of month field (1-31, *, *\/N, range, list)
     *   - month (string): New month field (1-12, *, *\/N, range, list)
     *   - day_of_week (string): New day of week field (0-7, *, *\/N, range, list)
     *   - command (string): New shell command or script path to execute
     * @return void
     * @throws Exception When cron job update fails, job doesn't exist, or invalid parameters provided
     */
    public function update(string $id, array $params): void
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->updateCronJob($username, $id, $params);
    }

    /**
     * Removes a scheduled cron job from the hosting account.
     *
     * @param string $id Unique cron job identifier to delete
     * @return void
     * @throws Exception When cron job deletion fails, job doesn't exist, or server error occurs
     */
    public function delete(string $id): void
    {
        $username = $this->account()->model()->username;

        $this->account()->server()->api()->deleteCronJob($username, $id);
    }

    /**
     * Determines if cron job functionality is supported by the hosting server.
     * By default returns true, but can be overridden by specific integrations.
     *
     * @return bool True if cron jobs are supported, false otherwise
     * @throws Exception When server capability check fails or server is unreachable
     */
    public function supported(): bool
    {
        return true;
    }
}
