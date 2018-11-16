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
  Resource\CloudAccount\Entity as CloudAccount,
  Util\Config,
  Util\Util
};

use Nexcess\Sdk\Cli\ {
  Command\Create as CreateCommand,
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
class Download extends CreateCommand {

  /** {@inheritDoc} */
  const ARGS = [];

  /** {@inheritDoc} */
  const ENDPOINT = Endpoint::class;

  /** {@inheritDoc} */
  const SUMMARY_KEYS = ['filename'];

  /** {@inheritDoc} */
  const INPUTS = [];

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
    $download_path = $input->getOption('download-path');

    $app = $this->getConsole();
    $cloud_account_id = Util::filter(
      $input->getOption('cloud-account-id'),
      Util::FILTER_INT
    );

    $filename = $input->getOption('filename');
    $force = $input->getOption('force');

    $app->say(
      $this->getPhrase(
        'downloading',
        ['filename' => $filename, 'download_path' => $download_path]
      )
    );

    $endpoint = $this->_getEndpoint();
    assert($endpoint instanceof Endpoint);

    $cloud = $endpoint->retrieve($cloud_account_id);
    assert($cloud instanceof CloudAccount);

    $backup = $endpoint->retrieveBackup($cloud, $filename);

    $backup->download($download_path, $force);
    $app->say($this->getPhrase('done'));

    return Console::EXIT_SUCCESS;
  }

}
