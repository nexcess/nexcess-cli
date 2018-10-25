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
use Nexcess\Sdk\Cli\ {
  Console,
  ConsoleException,
  Command\ShowList as ShowListCommand
};
use Nexcess\Sdk\Util\Util;

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
    
    $cloud_id = Util::filter($input->getOption('cloud_account_id'), Util::FILTER_INT);

    $cloudAccountEndpoint = $this->_getEndpoint();
    $cloudAccount = $cloudAccountEndpoint->retrieve($cloud_id);

      $backup_list = $this->_getEndpoint()->getBackups($cloudAccount)->toArray(true);

      $backup_list = array_map(
        function($backup_array) {
          $timestamp = Util::filter(
            $backup_array['filedate'], Util::FILTER_INT
          );
          $new_file_date = new \DateTimeImmutable();
          $new_file_date = $new_file_date->setTimestamp($timestamp);
          $backup_array['filedate'] = $new_file_date->format('Y-m-d h:i:s');
          return $backup_array;
        },
        $backup_list
      );

      $this->_saySummary($backup_list,
        $input->getOption('json')
      );

      return Console::EXIT_SUCCESS;
    }

}
