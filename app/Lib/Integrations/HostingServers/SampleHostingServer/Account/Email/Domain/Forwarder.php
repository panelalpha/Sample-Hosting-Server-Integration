<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account\Email\Domain;

use App\Lib\Integrations\EmailServers\AbstractEmailServer\Domain\AbstractForwarder;
use App\Lib\Integrations\HostingServers\SampleHostingServerIntegration\Account\Email;
use App\Lib\Interfaces\Integrations\EmailServer\Domain\ForwarderInterface;
use Exception;

/**
 * Manages email forwarding rules within email domains.
 *
 * @method Email emailServer()
 */
class Forwarder extends AbstractForwarder implements ForwarderInterface
{
    /**
     * Creates a new email forwarder on the server.
     *
     * - email (string): The source email address to forward from
     * - domain (string): The domain name containing the source email
     * - destination (string): The destination email address to forward to
     *
     * @param array{email: string, domain: string, destination: string} $params Email forwarder creation parameters
     * @return void
     * @throws Exception When forwarder creation fails or email already exists
     */
    public function create(array $params): void
    {
        $username = $this->emailServer()->account()->model()->username;

        $this->emailServer()->account()->server()->api()->createEmailForwarder(
            $username,
            $params['email'],
            $params['destination']
        );
    }

    /**
     * Deletes an email forwarder from the server.
     *
     * Removes the email forwarding rule that redirects messages from the source
     * email address to the specified destination.
     *
     * @param string $email The source email address to stop forwarding
     * @param string $forward_to The destination email address (for verification)
     * @return void
     * @throws Exception When forwarder deletion fails or forwarder doesn't exist
     */
    public function delete(string $email, string $forward_to): void
    {
        $username = $this->emailServer()->account()->model()->username;

        $this->emailServer()->account()->server()->api()->deleteEmailForwarder($username, $email);
    }
}
