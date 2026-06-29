<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServer\Account\Application;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\Application\AbstractImport;
use App\Lib\Interfaces\Integrations\HostingServer\Account\Application\ImportInterface;

/**
 * Manages WordPress site import operations for a hosting account application.
 *
 * Leave this class empty to use the default implementations from `AbstractImport`
 * (import from archive, URL, or remote source).
 * Override methods here only when the hosting provider requires custom API calls.
 */
class Import extends AbstractImport implements ImportInterface
{
}
