<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\CloudAccount\Backup;

use Nexcess\Sdk\ {
  Resource\CloudAccount\CloudAccount,
  Resource\CloudAccount\Endpoint,
  Util\Config,
  Util\Util
};

use Nexcess\Sdk\Cli\ {
  Command\CloudAccount\Backup\GetsBackupChoices,
  Command\CloudAccount\GetsCloudAccountChoices,
  Command\InputCommand,
  Console
};

use Symfony\Component\Console\ {
  Input\InputInterface as Input,
  Input\InputOption as Opt,
  Output\OutputInterface as Output
};

/**
 * Creates a new Cloud Account.
 */
class Show extends InputCommand {
  use GetsCloudAccountChoices,
    GetsBackupChoices;

  /** {@inheritDoc} */
  const ENDPOINT = Endpoint::class;

  /** {@inheritDoc} */
  const NAME = 'cloud-account:backup:show';

  /** {@inheritDoc} */
  const INPUTS = [
    'cloud_account_id' => Util::FILTER_INT,
    'filename' => Util::FILTER_STRING
  ];

  /** {@inheritDoc} */
  const OPTS = [
    'cloud-account-id|c' => [OPT::VALUE_REQUIRED],
    'filename|f' => [Opt::VALUE_REQUIRED]
  ];

  /** {@inheritDoc} */
  const RESTRICT_TO = [Config::COMPANY_NEXCESS];

  /** {@inheritDoc} */
  const SUMMARY_KEYS = [
    'filename',
    'type',
    'filesize',
    'filedate',
    'complete'
  ];

  /**
   * {@inheritDoc}
   */
  public function execute(Input $input, Output $output) {
    $endpoint = $this->_getEndpoint();
    assert($endpoint instanceof Endpoint);
    $cloudaccount_id = $this->getInput('cloud_account_id', false);
    $cloudaccount = $endpoint->retrieve($cloudaccount_id);
    assert($cloudaccount instanceof CloudAccount);
    $backup = $endpoint
      ->retrieveBackup($cloudaccount, $this->getInput('filename', false))
      ->toArray();

    $this->_saySummary($backup, $input->getOption('json'));
    $this->getConsole()->say(
      $this->getPhrase(
        'how_to_download',
        [
          'cloud_account_id' => $cloudaccount_id,
          'filename' => $backup['filename']
        ]
      )
    );

    return Console::EXIT_SUCCESS;
  }

  /**
   * {@inheritDoc}
   */
  protected function _getChoices(string $name, bool $format = true) : array {
    switch ($name) {
      case 'cloud_account_id':
        return $this->_getCloudAccountChoices($format);
      case 'filename':
        return $this->_getBackupChoices($format);
      default:
        return parent::_getChoices($name, $format);
    }
  }
}
