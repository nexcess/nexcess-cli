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
  Resource\CloudAccount\Endpoint,
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
class Create extends CreateCommand {

  /** {@inheritDoc} */
  const ARGS = [];

  /** {@inheritDoc} */
  const ENDPOINT = Endpoint::class;

  /** {@inheritDoc} */
  const SUMMARY_KEYS = ['filename', 'complete'];

  /** {@inheritDoc} */
  const INPUTS = [];

  /** {@inheritDoc} */
  const NAME = 'cloud-account:backup:create';

  /** {@inheritDoc} */
  const OPTS = [
    'cloud_account_id|c' => [OPT::VALUE_REQUIRED],
    'download|d' => [OPT::VALUE_REQUIRED],
  ];

  /** {@inheritDoc} */
  const RESTRICT_TO = [Config::COMPANY_NEXCESS];

  /**
   * {@inheritDoc}
   */
  public function execute(Input $input, Output $output) {
    $app = $this->getApplication();
    $app->getConfig()->set(
      'wait',
      ['timeout' => 9999, 'interval' => 5]
    );

    $endpoint = $this->_getEndpoint();

    $cloud_id = Util::filter(
      $input->getOption('cloud_account_id'),
      Util::FILTER_INT
    );
    $model = $endpoint->retrieve($cloud_id);
    $path = $input->getOption('download');
    $then_download = empty($path) ?
      null :
      $this->_thenDownload($path);

    $app->say($this->getPhrase('creating'));
    $backup = $endpoint->createBackup($model)
      ->then(function ($backup) use ($input) {
        $this->_saySummary($backup->toArray(), $input->getOption('json'));
        return $backup;
      })
      ->then($then_download)
      ->wait();

    return Console::EXIT_SUCCESS;
  }

  /**
   * Returns a closure that when used in the then() of a Guzzle promise,
   * will download the backup just created.
   *
   * @return callable
   */
  protected function _thenDownload(string $path) : Closure {
    return function ($backup) use ($path) {
      $console = $this->getApplication();

      $console->say(
        $this->getPhrase('downloading'),
        [Console::SAY_OPT_NEWLINE => false]
      );
      $backup->download($path);
      $console->say(
        $this->getPhrase(
          'download_complete',
          ['file' => "{$path}/{$backup->get('filename')}"]
        )
      );

      return $backup;
    };
  }
}
