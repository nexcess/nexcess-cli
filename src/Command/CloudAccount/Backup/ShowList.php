<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\CloudAccount\Backup;

use DateTimeImmutable;

use Nexcess\Sdk\ {
  Resource\CloudAccount\Endpoint,
  Resource\CloudAccount\CloudAccount,
  Util\Util
};

use Nexcess\Sdk\Cli\ {
  Console,
  Command\ShowList as ShowListCommand
};

use Symfony\Component\Console\ {
  Input\InputInterface as Input,
  Input\InputOption as Opt,
  Output\OutputInterface as Output
};

/**
 * Show a list of backups for a given cloud account
 */
class ShowList extends ShowListCommand {

  /** {@inheritDoc} */
  const ENDPOINT = Endpoint::class;

  /** {@inheritDoc} */
  const NAME = 'cloud-account:backup:list';

  /** {@inheritDoc} */
  const SUMMARY_KEYS = ['filename', 'filedate', 'complete', 'filesize'];

  /** {@inheritDoc} */
  const OPTS = ShowListCommand::OPTS +
    ['cloud-account-id' => [Opt::VALUE_REQUIRED]];

  /** {@inheritDoc} */
  public function execute(Input $input, Output $output) {
    $cloud_id = Util::filter(
      $input->getOption('cloud-account-id'),
      Util::FILTER_INT
    );

    $endpoint = $this->_getEndpoint();
    assert($endpoint instanceof Endpoint);
    $cloud = $endpoint->retrieve($cloud_id);
    assert($cloud instanceof CloudAccount);

    $this->_saySummary(
      $endpoint->listBackups($cloud)->toArray(),
      $input->getOption('json')
    );

    $this->getConsole()->say($this->getPhrase('done'));

    return Console::EXIT_SUCCESS;
  }

  /** {@inheritDoc} */
  protected function _getSummary(array $details) : array {
    $details = array_map(
      function ($backup_array) {
        $new_date = new DateTimeImmutable("@{$backup_array['filedate']}");
        $backup_array['filedate'] = $new_date->format('Y-m-d H:i:s T');
        $backup_array['complete'] = ($backup_array['complete'] ? 'YES' : 'NO');
        return $backup_array;
      },
      $details
    );

    return parent::_getSummary($details);
  }

}
