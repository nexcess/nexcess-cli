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
  Util\Util};

use Nexcess\Sdk\Cli\ {
  Command\Ssl\SslCreateCommand,
  Console
};

use Symfony\Component\Console\ {
  Input\InputInterface as Input,
  Input\InputOption as Opt,
  Output\OutputInterface as Output
};

/**
 * Creates a new SSL Certificate from a CSR.
 */
class CreateFromCsr extends SslCreateCommand {

  /** {@inheritDoc} */
  const NAME = 'ssl:createFromCsr';

  /** {@inheritDoc} */
  const OPTS = [
    'csr-file' => [OPT::VALUE_REQUIRED],
    'key-file' => [OPT::VALUE_REQUIRED],
    'months' => [OPT::VALUE_REQUIRED],
    'package-id' => [OPT::VALUE_REQUIRED],
    'approver-email' => [OPT::VALUE_REQUIRED | OPT::VALUE_IS_ARRAY]
  ];

  /**
   * {@inheritDoc}
   */
  public function execute(Input $input, Output $output) {
    $console = $this->getConsole();
    $endpoint = $this->_getEndpoint();
    assert($endpoint instanceof Endpoint);

    $console->say($this->getPhrase('creating'));

    $months = Util::filter($input->getOption('months'), Util::FILTER_INT);
    $package_id = Util::filter(
      $input->getOption('package-id'),
      Util::FILTER_INT
    );
    
    $key = $this->_readFile($input->getOption('key-file'));
    $csr = $this->_readFile($input->getOption('csr-file'));

    try {
      // @phan-suppress-next-line PhanUndeclaredMethod
      $model = $endpoint->createFromCsr(
        $csr,
        $key,
        $months,
        $package_id,
        $this->_approver_email
      );
    } catch (ApiException $e) {
      switch ($e->getCode()) {
        case ApiException::CREATE_FAILED:
          // @todo Open a support ticket?
          $console->say($this->getPhrase('failed'));
          return Console::EXIT_API_ERROR;
        default:
          throw $e;
      }
    }

    $console->say($this->getPhrase('created', ['id' => $model->getId()]));
    $this->_saySummary($model->toArray(false), $input->getOption('json'));
    return Console::EXIT_SUCCESS;
  }

}
