<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command;

use Nexcess\Sdk\Exception;

/**
 * Generic/common Command-related errors.
 */
class CommandException extends Exception {

  /** @var int Lookup value matches no choices. */
  const NO_LOOKUP_MATCH = 1;

  /** @var int Ambiguous lookup value. */
  const LOOKUP_MATCH_AMBIGUOUS = 2;

  /** @var int Missing argument or option. */
  const MISSING_INPUT = 3;

  /** @var int Asked for an input that doesn't exist. */
  const NO_SUCH_INPUT = 4;

  /** @var int Malformatted list filter input. */
  const INVALID_LIST_FILTER = 5;

  /** {@inheritDoc} */
  const INFO = [
    self::NO_LOOKUP_MATCH => ['message' => 'console.command.no_lookup_match'],
    self::LOOKUP_MATCH_AMBIGUOUS =>
      ['message' => 'console.command.lookup_match_ambiguous'],
    self::MISSING_INPUT => ['message' => 'console.command.missing_input'],
    self::NO_SUCH_INPUT => ['message' => 'console.command.no_such_input'],
    self::INVALID_LIST_FILTER =>
      ['message' => 'console.command.invalid_list_filter']
  ];
}
