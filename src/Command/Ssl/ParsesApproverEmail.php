<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\Ssl;

use Nexcess\Sdk\Cli\Command\Ssl\SslException;

trait ParsesApproverEmail {


  protected function _parsesApproverEmail(array $approver_emails_raw) : array {
    if (empty($approver_emails_raw)) {
        throw new SslException(SslException::NO_APPROVER_EMAIL);
    }

    $returnValue = [];
    foreach ($approver_emails_raw as $approver) {
      if (substr_count($approver, ':') !== 1) {
        throw new SslException(
          SslException::INVALID_APPROVER_EMAIL,
          ['approver' => $approver]
        );
      }

      [$key, $value] = explode(':', $approver);
      $returnValue[$key] = $value;
    }

    return $returnValue;
  }

}
