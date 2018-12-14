<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\Ssl;

use Nexcess\Sdk\ {
  ApiException,
  Resource\Ssl\Endpoint,
  Util\Config,
  Resource\Creatable
};

use Nexcess\Sdk\Cli\ {
  Command\Ssl\GetsPackageChoices,
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
class Import extends CreateCommand {
  use GetsPackageChoices;

  /** {@inheritDoc} */
  const ARGS = [];

  /** {@inheritDoc} */
  const ENDPOINT = Endpoint::class;

  /** {@inheritDoc} */
  const INPUTS = [];

  /** {@inheritDoc} */
  const NAME = 'ssl:import';

  /** {@inheritDoc} */
  const OPTS = [
    'crt-file' => [OPT::VALUE_REQUIRED],
    'key-file' => [OPT::VALUE_REQUIRED],
    'chain-file' => [OPT::VALUE_REQUIRED],
  ];

  /** {@inheritDoc} */
  const RESTRICT_TO = [Config::COMPANY_NEXCESS];

  /** {@inheritDoc} */
  public function execute(Input $input, Output $output) : int{
    $console = $this->getConsole();
    $endpoint = $this->_getEndpoint();
    assert($endpoint instanceof Creatable);

    $console->say($this->getPhrase('creating'));

    // Import
    $key = $this->_readFile($input->getOption('key-file'));
    $crt = $this->_readFile($input->getOption('crt-file'));

    $chain = ! empty($this->_readFile($input->getOption('chain-file'))) ?
      $this->_readFile($input->getOption('chain-file')) :
      '';

    try {
      // @phan-suppress-next-line PhanUndeclaredMethod
      $model = $endpoint->importCertificate($key, $crt, $chain);
      $console->say($this->getPhrase('created', ['id' => $model->getId()]));
      $this->_saySummary($model->toArray(false), $input->getOption('json'));
      return Console::EXIT_SUCCESS;
    } catch (ApiException $e) {
      $console->say($this->getPhrase('import_failed'));
      return Console::EXIT_API_ERROR;
    }

    $console->say($this->getPhrase('created', ['id' => $model->getId()]));
    $this->_saySummary($model->toArray(false), $input->getOption('json'));
    return Console::EXIT_SUCCESS;
  }

  /**
   * {@inheritDoc}
   */
  protected function _getSummary(array $details) : array {
    $details = parent::_getSummary($details);
    $details['valid_from_date'] =
      $details['valid_from_date']->format('Y-m-d h:i:s');
    $details['valid_to_date'] =
      $details['valid_to_date']->format('Y-m-d h:i:s');
    unset($details['crt']);
    unset($details['key']);
    unset($details['chain']);
    unset($details['identity']);
    unset($details['domain']);
    unset($details['months']);
    unset($details['package_id']);
    unset($details['approver_email']);
    unset($details['client_id']);
    return $details;
  }

  protected function _readfile(string $filename) : string {
    if (! file_exists($filename)) {
        throw new \Exception('File does not exist');
    }

    return file_get_contents($filename);
  }

}
