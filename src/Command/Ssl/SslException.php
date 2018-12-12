<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\Ssl;

use Nexcess\Sdk\Exception;

/**
 * Generic/common Command-related errors.
 */
class SslException extends Exception {

  /** @var int Lookup value matches no choices. */
  const INVALID_APPROVER_EMAIL = 1;

  /** @var int Ambiguous lookup value. */
  const NO_APPROVER_EMAIL = 2;

  /** @var int Missing argument or option. */
  const MISSING_INPUT = 3;

  /** @var int Asked for an input that doesn't exist. */
  const NO_SUCH_INPUT = 4;

  /** @var int Malformatted list filter input. */
  const INVALID_LIST_FILTER = 5;

  /** {@inheritDoc} */
  const INFO = [
    self::INVALID_APPROVER_EMAIL => ['message' => 'console.command.ssl.invalid_approver_email'],
    self::NO_APPROVER_EMAIL =>
      ['message' => 'console.command.ssl.no_approver_email'],
    self::MISSING_INPUT => ['message' => 'console.command.missing_input'],
    self::NO_SUCH_INPUT => ['message' => 'console.command.no_such_input'],
    self::INVALID_LIST_FILTER =>
      ['message' => 'console.command.invalid_list_filter']
  ];
}
