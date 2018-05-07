<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Exception;

use Nexcess\Sdk\Exception\Exception;

class ConsoleException extends Exception {

  /** @var int Symfony's question helper is missing. */
  const QUESTIONHELPERLESS = 1;

  /** @var int An invalid config class was specified. */
  const INVALID_CONFIG_TYPE = 2;

  /** @var int Unsupported config file format. */
  const UNSUPPORTED_PROFILE_TYPE = 3;

  /** {@inheritDoc} */
  const INFO = [
    self::QUESTIONHELPERLESS =>
      ['message' => 'console.exception.missing_questionhelper'],
    self::INVALID_CONFIG_TYPE =>
      ['message' => 'console.exception.invalid_config_type'],
    self::UNSUPPORTED_PROFILE_TYPE =>
      ['message' => 'console.exception.unsupported_profile_type']
  ];
}
