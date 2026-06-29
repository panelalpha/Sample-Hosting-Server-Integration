<?php

namespace App\Lib\Integrations\HostingServers\SampleHostingServer\Account\Application;

use App\Lib\Integrations\HostingServers\AbstractHostingServer\Account\Application\AbstractWordpress;
use App\Lib\Interfaces\Integrations\HostingServer\Account\Application\WordpressInterface;

/**
 * Manages WordPress-specific operations for a hosting account application.
 *
 * Leave this class empty to use the default implementations from `AbstractWordpress`
 * (WP-CLI, plugins, themes, users, database, config, etc.).
 * Override methods here only when the hosting provider requires custom API calls.
 */
class Wordpress extends AbstractWordpress implements WordpressInterface
{
}
