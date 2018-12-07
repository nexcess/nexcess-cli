<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Tests\Command;

use Nexcess\Sdk\ {
  ApiException,
  SdkException
};
use Nexcess\Sdk\Cli\ {
  Command\CommandException,
  Tests\Command\CommandTestCase
};

/**
 * Base class for "list" command tests.
 */
abstract class ListTestCase extends CommandTestCase {

  /**
   * {@inheritDoc}
   */
  public function runProvider() : array {
    return [
      [
        ['--filter' => ['foobar']],
        [],
        [],
        new CommandException(CommandException::INVALID_LIST_FILTER)
      ]
    ];
  }
}
