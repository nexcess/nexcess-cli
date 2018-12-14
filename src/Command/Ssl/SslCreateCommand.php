<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\Ssl;

use Nexcess\Sdk\ {
  Resource\Ssl\Endpoint
};


use Nexcess\Sdk\Cli\ {
  Command\Ssl\GetsPackageChoices,
  Command\Ssl\ParsesApproverEmail,
  Command\Create as CreateCommand,
  Command\Ssl\SslException
};

use Symfony\Component\Console\ {
  Input\InputInterface as Input,
  Output\OutputInterface as Output
};

/**
 * Creates a new Cloud Account.
 */
class SslCreateCommand extends CreateCommand {
  use GetsPackageChoices,
    ParsesApproverEmail;

  /** {@inheritDoc} */
  const ARGS = [];

  /** {@inheritDoc} */
  const ENDPOINT = Endpoint::class;

  /** {@inheritDoc} */
  const INPUTS = [];

  /** {@inheritDoc} */
  const NAME = '';

  /** {@inheritDoc} */
  const OPTS = [];

  /** @var array list of domains and the approver email **/
  protected $_approver_email = [];

  /**
   * {@inheritDoc}
   */
  public function initialize(Input $input, Output $output) {
    parent::initialize($input, $output);
    $this->_approver_email = $this->_parsesApproverEmail(
      $input->getOption('approver-email')
    );
  }

  /**
   * {@inheritDoc}
   */
  protected function _getChoices(string $name, bool $format = true) : array {
    switch ($name) {
      case 'package_id':
        return $this->_getPackageChoices($format);
      default:
        return parent::_getChoices($name, $format);
    }
  }

  /**
   * {@inheritDoc}
   */
  protected function _getSummary(array $details) : array {
    $details = parent::_getSummary($details);

    if (empty($details['alt_names'])) {
      unset($details['alt_names']);
    }

    unset(
      $details['approver_email'],
      $details['chain'],
      $details['crt'],
      $details['is_expired'],
      $details['is_installable'],
      $details['is_multi_domain'],
      $details['is_wildcard'],
      $details['key'],
      $details['domain'],
      $details['months'],
      $details['package_id'],
      $details['client_id'],
      $details['identity']
    );

    if (!is_null($details['valid_from_date'])) {
      $details['valid_from_date'] = (
        new \DateTimeImmutable(
          date('Y-m-d h:i:s', $details['valid_from_date'])
        ))->format('Y-m-d h:i:s');
    } else {
      unset($details['valid_from_date']);
    }
    
    if (!is_null($details['valid_to_date'])) {
      $details['valid_to_date'] = (new \DateTimeImmutable(
        date('Y-m-d h:i:s', $details['valid_to_date'])
      ))->format('Y-m-d h:i:s');
    } else {
      unset($details['valid_to_date']);
    }
    
    return $details;
  }

  /**
   * Read the contents of a file and return it.
   *
   * @param string $filename the full path and filename to read
   * @return string
   * @throws SslException
   */
  protected function _readfile(string $filename) : string {
    if (! file_exists($filename)) {
        throw new SslException(
          SslException::INVALID_FILENAME, ['filename' => $filename]
        );
    }
    return file_get_contents($filename);
  }

}
