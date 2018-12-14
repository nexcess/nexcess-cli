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

  /** @var int Invalid file name passed in. */
  const INVALID_FILENAME = 3;

  /** {@inheritDoc} */
  const INFO = [
    self::INVALID_APPROVER_EMAIL => [
      'message' => 'console.ssl.exception.invalid_approver_email'
    ],
    self::NO_APPROVER_EMAIL => [
      'message' => 'console.ssl.exception.no_approver_email'
    ],
    self::INVALID_FILENAME => [
      'message' => 'console.ssl.exception.invalid_filename' // not translating
    ]
  ];
}
