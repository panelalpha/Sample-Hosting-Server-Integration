<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account\Email\Domain;

use App\Lib\Integrations\EmailServers\AbstractEmailServer\Domain\AbstractAccount;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account\Email;
use App\Lib\Interfaces\Integrations\EmailServer\Domain\AccountInterface;
use Exception;
use stdClass;

/**
 * Manages individual email account operations within email domains.
 *
 * @method Email emailServer()
 */
class Account extends AbstractAccount implements AccountInterface
{
    /**
     * Creates a new email account on the server.
     *
     * - email (string): The email address to create
     * - password (string): The account password
     * - quota (string|int): Disk quota limit or 'unlimited'
     *
     * @param array{email: string, password: string, quota?: string|int} $params Email account creation parameters
     * @return void
     * @throws Exception When account creation fails or email already exists
     */
    public function create(array $params): void
    {
        $username = $this->emailServer()->account()->model()->username;

        $this->emailServer()->account()->server()->api()->createEmailAccount(
            $username,
            $params['email'],
            $params['password'],
            [
                'quota' => $params['quota'] ?? 'unlimited'
            ]
        );
    }

    /**
     * Updates an existing email account configuration.
     *
     * @param string $email The email address to update
     * @param array{password?: string, quota?: string} $params Updated account parameters
     * @return void
     * @throws Exception When account update fails or email doesn't exist
     */
    public function update(string $email, array $params): void
    {
        $username = $this->emailServer()->account()->model()->username;

        if (isset($params['password'])) {
            $this->emailServer()->account()->server()->api()->changeEmailPassword(
                $username,
                $email,
                $params['password']
            );
        }

        if (isset($params['quota'])) {
            $this->emailServer()->account()->server()->api()->updateEmailAccount(
                $username,
                $email,
                ['quota' => $params['quota']]
            );
        }
    }

    /**
     * Deletes an email account from the server.
     *
     * @param string $email The email address to delete
     * @return void
     * @throws Exception When account deletion fails or email doesn't exist
     */
    public function delete(string $email): void
    {
        $username = $this->emailServer()->account()->model()->username;

        $this->emailServer()->account()->server()->api()->deleteEmailAccount($username, $email);
    }

    /**
     * Returns email client configuration settings for the account.
     *
     * Provides complete email client configuration including IMAP, POP3, and SMTP
     * server settings with secure and insecure port options for email client setup.
     *
     * - account (string): The email account address
     * - display (string): Display name for the account
     * - domain (string): The email domain
     * - inbox_host (string): Incoming mail server hostname
     * - pop3_port (int): Secure POP3 port (SSL/TLS)
     * - pop3_insecure_port (int): Insecure POP3 port
     * - imap_port (int): Secure IMAP port (SSL/TLS)
     * - imap_insecure_port (int): Insecure IMAP port
     * - inbox_username (string): Username for incoming mail
     * - mail_domain (string): Mail server domain
     * - smtp_host (string): Outgoing mail server hostname
     * - smtp_insecure_port (int): Insecure SMTP port (STARTTLS)
     * - smtp_port (int): Secure SMTP port (SSL/TLS)
     * - smtp_username (string): Username for outgoing mail
     *
     * @param string $email The email address to get configuration for
     * @return array{
     *     account: string,
     *     display: string,
     *     domain: string,
     *     inbox_host: string,
     *     pop3_port: int,
     *     pop3_insecure_port: int,
     *     imap_port: int,
     *     imap_insecure_port: int,
     *     inbox_username: string,
     *     mail_domain: string,
     *     smtp_host: string,
     *     smtp_insecure_port: int,
     *     smtp_port: int,
     *     smtp_username: string
     * } Complete email client configuration
     * @throws Exception When configuration retrieval fails
     */
    public function getConfiguration(string $email): array
    {
        $username = $this->emailServer()->account()->model()->username;
        $mailServerConfig = $this->emailServer()->account()->server()->api()->getEmailServerConfig($username);

        return [
            "account" => $email,
            "display" => $email,
            "domain" => $this->emailServer()->domain()->model()->domain,
            "inbox_host" => $mailServerConfig['inbox_host'],
            "pop3_port" => $mailServerConfig['pop3_port'],
            "pop3_insecure_port" => $mailServerConfig['pop3_insecure_port'],
            "imap_port" => $mailServerConfig['imap_port'],
            "imap_insecure_port" => $mailServerConfig['imap_insecure_port'],
            "inbox_username" => $email,
            "mail_domain" => $mailServerConfig['mail_domain'],
            "smtp_host" => $mailServerConfig['smtp_host'],
            "smtp_insecure_port" => $mailServerConfig['smtp_insecure_port'],
            "smtp_port" => $mailServerConfig['smtp_port'],
            "smtp_username" => $email
        ];
    }

    /**
     * Creates a webmail SSO URL for direct email access.
     *
     * Generates a single sign-on URL that allows users to access their webmail
     * interface without additional authentication through the hosting control panel.
     *
     * @param string $email The email address to create SSO URL for
     * @return stdClass Object containing the webmail SSO URL
     */
    public function webmailSso(string $email): stdClass
    {
        $username = $this->emailServer()->account()->model()->username;
        $ssoUrl = $this->emailServer()->account()->server()->api()->createWebmailSso($username, $email);

        $sso = new stdClass();
        $sso->url = $ssoUrl;
        return $sso;
    }
}
