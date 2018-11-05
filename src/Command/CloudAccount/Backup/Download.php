<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\CloudAccount\Backup;

use Closure;

use Nexcess\Sdk\ {
  Resource\CloudAccount\Backup,
  Resource\CloudAccount\Endpoint,
  Resource\Promise,
  Util\Config,
  Util\Util
};

use Nexcess\Sdk\Cli\ {
  Command\CloudAccount\CloudAccountException,
  Command\Create as CreateCommand,
  Console
};

use Symfony\Component\Console\ {
  Input\InputArgument as Arg,
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

    if (empty($download_path) || ! is_string($download_path)) {
      throw new CloudAccountException(CloudAccountException::INVALID_PATH);
    }

    $app = $this->getApplication();
    $cloud_account_id = Util::filter(
      $input->getOption('cloud-account-id'),
      Util::FILTER_INT
    );

    $filename = $input->getOption('filename');

    if ($input->getOption('force')) {
      $download_path = trim($download_path);
      if (substr($download_path, -1) !== DIRECTORY_SEPARATOR) {
        $download_path .= DIRECTORY_SEPARATOR;
      }

      if (file_exists($download_path . $filename)) {
        unlink($download_path . $filename);
      }
    }

    $app->say(
      $this->getPhrase(
        'downloading',
        ['filename' => $filename, 'download_path' => $download_path])
    );
    
    $endpoint = $this->_getEndpoint();
    $cloud = $endpoint->retrieve($cloud_account_id);
    $backup = $endpoint->getBackup($cloud, $filename);

    $backup->download($download_path);
    $app->say($this->getPhrase('done'));

    return Console::EXIT_SUCCESS;
  }

}
