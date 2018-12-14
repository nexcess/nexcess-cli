<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\Ssl;

use Nexcess\Sdk\ {
  Resource\Ssl\Endpoint,
  Util\Config,
  Util\Util
};

use Nexcess\Sdk\Cli\ {
  Command\Ssl\SslCreateCommand,
  Console
};

use Symfony\Component\Console\ {
  Input\InputArgument as Arg,
  Input\InputInterface as Input,
  Input\InputOption as Opt,
  Output\OutputInterface as Output
};

/**
 * Creates a new SSL Certificate.
 */
class Create extends SslCreateCommand {

  /** {@inheritDoc} */
  const NAME = 'ssl:create';

  /** {@inheritDoc} */
  const OPTS = [
    'dn-file' => [OPT::VALUE_REQUIRED],
    'domain' => [OPT::VALUE_REQUIRED],
    'months' => [OPT::VALUE_REQUIRED],
    'package-id' => [OPT::VALUE_REQUIRED],
    'organization' => [OPT::VALUE_REQUIRED],
    'street'  => [OPT::VALUE_REQUIRED],
    'locality'  => [OPT::VALUE_REQUIRED],
    'state'  => [OPT::VALUE_REQUIRED],
    'country'  => [OPT::VALUE_REQUIRED],
    'organizational_unit'  => [OPT::VALUE_REQUIRED],
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

    $dn = ! empty($input->getOption('dn-file')) ?
      Util::readJsonFile($input->getOption('dn-file')) :
      null;
    
    // Failsafe
    if (json_last_error()) {
      $dn = null;
    }

    if (! is_array($dn) || empty($dn)) {
      $dn = [
        'organization' => $input->getOption('organization'),
        'street' => $input->getOption('street'),
        'locality' => $input->getOption('locality'),
        'state' => $input->getOption('state'),
        'country' => $input->getOption('country'),
        'organizational_unit' => $input->getOption('unit'),
        'approver_email'  => $this->_approver_email
      ];
    }

    $domain = $input->getOption('domain');

    try {
      // @phan-suppress-next-line PhanUndeclaredMethod
      $model = $endpoint->create(
        $domain,
        $dn,
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
