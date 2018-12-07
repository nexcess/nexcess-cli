<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\CloudAccount\Backup;

use Nexcess\Sdk\ {
  Resource\CloudAccount\Endpoint,
  Resource\CloudAccount\CloudAccount,
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
class Download extends InputCommand {
  use GetsCloudAccountChoices,
    GetsBackupChoices;

  /** {@inheritDoc} */
  const ENDPOINT = Endpoint::class;

  /** {@inheritDoc} */
  const SUMMARY_KEYS = ['filename'];

  /** {@inheritDoc} */
  const INPUTS = [
    'cloud_account_id' => Util::FILTER_INT,
    'download_path' => Util::FILTER_STRING,
    'filename' => Util::FILTER_STRING
  ];

  /** {@inheritDoc} */
  const NAME = 'cloud-account:backup:download';

  /** {@inheritDoc} */
  const OPTS = [
    'cloud-account-id|c' => [OPT::VALUE_REQUIRED],
    'filename|f' => [OPT::VALUE_REQUIRED],
    'download-path|d' => [OPT::VALUE_REQUIRED],
    'force' => [OPT::VALUE_NONE]
  ];

  /** {@inheritDoc} */
  const RESTRICT_TO = [Config::COMPANY_NEXCESS];

  /**
   * {@inheritDoc}
   */
  public function execute(Input $input, Output $output) {
    $endpoint = $this->_getEndpoint();
    assert($endpoint instanceof Endpoint);

    $cloud_account_id = $this->getInput('cloud_account_id', false);
    $cloud = $endpoint->retrieve($cloud_account_id);
    assert($cloud instanceof CloudAccount);

    $filename = $this->getInput('filename', false);
    $backup = $endpoint->retrieveBackup($cloud, $filename);

    $download_path = $this->getInput('download_path') ?: '.';

    $app = $this->getConsole();
    $app->say(
      $this->getPhrase(
        'downloading',
        ['filename' => $filename, 'download_path' => $download_path]
      )
    );
    $backup->download($download_path, $input->getOption('force'));
    $app->say($this->getPhrase('done'));

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
