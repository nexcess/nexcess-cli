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
  Util\Config,
  Util\Util
};

use Nexcess\Sdk\Cli\ {
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

    $app->say($this->getPhrase('creating'));
    $model = $endpoint->retrieve($cloud_id);

    $downloadClosure = null;

    if ($input->getOption('download')) {
      $downloadClosure = $this->_downloadClosure();
    }

    $backup = $endpoint->createBackup($model)
      ->waitUntil($this->_waitClosure())
      ->then($downloadClosure)
      ->wait();

    $this->_saySummary($backup->toArray(), $input->getOption('json'));

    $app->say(
      $this->getPhrase('done')
    );
    return Console::EXIT_SUCCESS;
  }

  /**
   * Used in the wait() of a Guzzle promise.
   * Checks to see if the download is complete.
   *
   * @return bool
   */
  protected function _waitClosure() : callable {
    $cloud_id = Util::filter(
      $this->getApplication()->getIO()[Console::GET_IO_INPUT]
        ->getOption('cloud_account_id'),
      Util::FILTER_INT
    );
    return function ($backup) use ($cloud_id) {
      $endpoint = $this->_getEndpoint();
      return (
        $endpoint->getBackup(
          $endpoint->retrieve($cloud_id),
          $backup->get('filename')
        )->wait()
      )->get('complete');
    };
  }

  /**
   * Returns a closure that when used in the then() of a Guzzle promise,
   * will download the backup just created.
   *
   * @return callable
   */
  protected function _downloadClosure() : callable {
    $cloud_id = Util::filter(
      $this->getApplication()
        ->getIO()[Console::GET_IO_INPUT]
        ->getOption('cloud_account_id'),
      Util::FILTER_INT
    );
    return function ($backup) use ($cloud_id) {
      $endpoint = $this->_getEndpoint();
        $endpoint->getBackup(
          $endpoint->retrieve($cloud_id),
          $backup->get('filename')
        )->wait();
        $endpoint->downloadBackup(
          $endpoint->retrieve($cloud_id),
          $backup->get('filename'),
          $this->getApplication()
            ->getIO()[Console::GET_IO_INPUT]
            ->getOption('download')
        );
      return $backup;
    };
  }
}
