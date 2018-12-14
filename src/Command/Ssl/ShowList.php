<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\Ssl;

use Nexcess\Sdk\Resource\Ssl\Endpoint;
use Nexcess\Sdk\Cli\Command\ShowList as ShowListCommand;

/**
 * Lists Ssl Certificates
 */
class ShowList extends ShowListCommand {

  /** {@inheritDoc} */
  const ENDPOINT = Endpoint::class;

  /** {@inheritDoc} */
  const NAME = 'ssl:list';

  /** {@inheritDoc} */
  const SUMMARY_KEYS = ['id', 'common_name'];
}
