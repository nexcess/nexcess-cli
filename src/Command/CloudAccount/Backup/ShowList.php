<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\CloudAccount\Backup;

use Nexcess\Sdk\Resource\CloudAccount\Endpoint;
use Nexcess\Sdk\Resource\CloudAccount\Backup;
use Nexcess\Sdk\Cli\Command\ShowList as ShowListCommand;

use Symfony\Component\Console\ {
  Input\InputArgument as Arg,
  Input\InputInterface as Input,
  Input\InputOption as Opt,
  Output\OutputInterface as Output,
};

/**
 * Lists backups.
 */
class ShowList extends ShowListCommand {

  /** {@inheritDoc} */
  const ENDPOINT = Endpoint::class;

  /** {@inheritDoc} */
  const NAME = 'cloud-account:backup:list';

  /** {@inheritDoc} */
  const SUMMARY_KEYS = ['filename', 'filesize', 'filedate'];

  /** {@inheritDoc} */
  const OPTS = ['cloud_account_id' => [Opt::VALUE_REQUIRED]];

  /** {@inheritDoc} */
  const ARGS = [];

  public function execute( $input,  $output) {
   // $cloud = // make your CloudAccount Entity somehow
    $cloudAccountEndpoint = $this->_getEndpoint();
    $cloudAccount = $cloudAccountEndpoint->retrieve(1642);

      $this->_saySummary(
        $this->_getEndpoint()->getBackups($cloudAccount)->toArray(true),
        $input->getOption('json')
      );

      return Console::EXIT_SUCCESS;
    }

}
