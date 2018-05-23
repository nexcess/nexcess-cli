<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Exception;

use Nexcess\Sdk\Exception\Exception;

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

  /** {@inheritDoc} */
  const INFO = [
    self::NO_LOOKUP_MATCH =>
      ['message' => 'console.command.exception.no_lookup_match'],
    self::LOOKUP_MATCH_AMBIGUOUS =>
      ['message' => 'console.command.exception.lookup_match_ambiguous'],
    self::MISSING_INPUT =>
      ['message' => 'console.command.exception.missing_input'],
    self::NO_SUCH_INPUT =>
      ['message' => 'console.command.exception.no_such_input']
  ];
}
