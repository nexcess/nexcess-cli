<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli;

use Nexcess\Sdk\Exception;

class ConsoleException extends Exception {

  /** @var int Symfony's question helper is missing. */
  const QUESTIONHELPERLESS = 1;

  /** @var int An invalid config class was specified. */
  const INVALID_CONFIG_TYPE = 2;

  /** @var int Unsupported config file format. */
  const UNSUPPORTED_PROFILE_TYPE = 3;

  /** @var int Couldn't find the specified user profile. */
  const PROFILE_NOT_FOUND = 4;

  /** @var int Sandbox mode is not enabled. */
  const NOT_SANDBOXED = 5;

  /** {@inheritDoc} */
  const INFO = [
    self::QUESTIONHELPERLESS =>
      ['message' => 'console.exception.missing_questionhelper'],
    self::INVALID_CONFIG_TYPE =>
      ['message' => 'console.exception.invalid_config_type'],
    self::UNSUPPORTED_PROFILE_TYPE =>
      ['message' => 'console.exception.unsupported_profile_type'],
    self::PROFILE_NOT_FOUND =>
      ['message' => 'console.exception.profile_not_found'],
    self::NOT_SANDBOXED => ['message' => 'console.exception.not_sandboxed']
  ];
}
