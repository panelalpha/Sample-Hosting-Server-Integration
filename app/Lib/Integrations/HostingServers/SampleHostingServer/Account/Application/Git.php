<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServer\Account\Application;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\Application\AbstractGit;
use App\Lib\Interfaces\Integrations\HostingServer\Account\Application\GitInterface;

/**
 * Manages Git repository operations for a hosting account application.
 *
 * Leave this class empty to use the default implementations from `AbstractGit`
 * (clone, pull, push, branch management, deployment hooks, etc.).
 * Override methods here only when the hosting provider requires custom API calls.
 */
class Git extends AbstractGit implements GitInterface
{
}
