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
    'cloud_account_id|c' => [OPT::VALUE_REQUIRED],
    'file_name|f' => [OPT::VALUE_REQUIRED],
    'download_path|d' => [OPT::VALUE_REQUIRED]
  ];

  /** {@inheritDoc} */
  const RESTRICT_TO = [Config::COMPANY_NEXCESS];

  /**
   * {@inheritDoc}
   */
  public function execute(Input $input, Output $output) {
    $app = $this->getApplication();
    $cloud_account_id = Util::filter(
      $input->getOption('cloud_account_id'),
      Util::FILTER_INT
    );
    $file_name = $input->getOption('file_name');
    $download_path = $input->getOption('download_path');

    $endpoint = $this->_getEndpoint();
    $cloud = $endpoint->retrieve($cloud_account_id);
    $backup = $endpoint->getBackup($cloud,$file_name);

    $app->say($this->getPhrase('downloading',['file_name' => $file_name]));
    $backup->download($download_path);
    $app->say($this->getPhrase('done'));

    return Console::EXIT_SUCCESS;
  }

}
