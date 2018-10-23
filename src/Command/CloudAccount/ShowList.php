<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\CloudAccount;

use Nexcess\Sdk\Resource\CloudAccount\Endpoint;
use Nexcess\Sdk\Cli\Command\ShowList as ShowListCommand;

/**
 * Lists Cloud Accounts.
 */
class ShowList extends ShowListCommand {

  /** {@inheritDoc} */
  const ENDPOINT = Endpoint::class;

  /** {@inheritDoc} */
  const NAME = 'cloud-account:list';

  /** {@inheritDoc} */
  const SUMMARY_KEYS = ['id', 'domain', 'ip'];
}
