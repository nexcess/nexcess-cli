<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\CloudAccount\Backup;

use Nexcess\Sdk\ {
  Resource\CloudAccount\Backup,
  Resource\CloudAccount\Endpoint,
  Util\Config,
  Util\Util
};

use Nexcess\Sdk\Cli\ {
  Command\CloudAccount\CloudAccountException,
  Console
};

use Symfony\Component\Console\ {
  Input\InputInterface as Input,
  Input\InputOption as Opt,
  Output\OutputInterface as Output
};

/**
 * Delete a backup
 */
class Delete extends Command {

  /** {@inheritDoc} */
  const ARGS = [];

  /** {@inheritDoc} */
  const ENDPOINT = Endpoint::class;

  /** {@inheritDoc} */
  const SUMMARY_KEYS = ['filename'];

  /** {@inheritDoc} */
  const INPUTS = [];

  /** {@inheritDoc} */
  const NAME = 'cloud-account:backup:delete';

  /** {@inheritDoc} */
  const OPTS = [
    'cloud-account-id|c' => [OPT::VALUE_REQUIRED],
    'filename|f' => [OPT::VALUE_REQUIRED]
  ];

  /** {@inheritDoc} */
  const RESTRICT_TO = [Config::COMPANY_NEXCESS];

  /**
   * {@inheritDoc}
   */
  public function execute(Input $input, Output $output) {
    $app = $this->getApplication();
    $cloud_account_id = Util::filter(
      $input->getOption('cloud-account-id'),
      Util::FILTER_INT
    );
    
    $filename = $input->getOption('filename');
    if (empty($filename)) {
      throw new CloudAccountException(CloudAccountException::INVALID_BACKUP);
    }

    $app->say($this->getPhrase('deleting', ['filename' => $filename]));

    $endpoint = $this->_getEndpoint();
    $cloud = $endpoint->retrieve($cloud_account_id);
    $backup = $endpoint->getBackup($cloud, $filename);

    $backup->delete($cloud);
    $app->say($this->getPhrase('done'));

    return Console::EXIT_SUCCESS;
  }

}
