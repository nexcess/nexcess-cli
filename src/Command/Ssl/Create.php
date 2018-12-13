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
  Util\Util,
  Resource\Creatable
};


use Nexcess\Sdk\Cli\ {
  Command\Ssl\GetsPackageChoices,
  Command\Ssl\ParseApproverEmail,
  Command\Create as CreateCommand,
  Command\SslException,
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
  use GetsPackageChoices, ParseApproverEmail;

  /** {@inheritDoc} */
  const ARGS = [];

  /** {@inheritDoc} */
  const ENDPOINT = Endpoint::class;

  /** {@inheritDoc} */
  const INPUTS = [];

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
    'approver-email' => [OPT::VALUE_REQUIRED|OPT::VALUE_IS_ARRAY]
  ];

  /** {@inheritDoc} */
  const RESTRICT_TO = [Config::COMPANY_NEXCESS];

  /** @var array list of domains and the approver email **/
  protected  $_approver_email = [];

  /**
   * {@inheritDoc}
   */
  public function initialize(Input $input, Output $output) {
    parent::initialize($input, $output);
    $this->_approver_email = $this->_parseApproverEmail(
      $input->getOption('approver-email')
    );
  }

  /**
   * {@inheritDoc}
   */
  protected function _getChoices(string $name, bool $format = true) : array {
    switch ($name) {
      case 'package_id':
        return $this->_getPackageChoices($format);
      default:
        return parent::_getChoices($name, $format);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function execute(Input $input, Output $output) {
    $console = $this->getConsole();
    $endpoint = $this->_getEndpoint();
    assert($endpoint instanceof Creatable);

    $console->say($this->getPhrase('creating'));

    $months = Util::filter($input->getOption('months'), Util::FILTER_INT);
    $domain = $input->getOption('domain');
    $package_id = Util::filter($input->getOption('package-id'), Util::FILTER_INT);

    $dn = ! empty($this->_readFile($input->getOption('dn-file'))) ?
      json_decode($this->_readFile($input->getOption('dn-file')),true) :
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

  /**
   * {@inheritDoc}
   */
  protected function _getSummary(array $details) : array {
    $details = parent::_getSummary($details);

    if (empty($details['alt_names'])) {
      unset($details['alt_names']);
    }

    unset($details['approver_email']);
    unset($details['chain']);
    unset($details['crt']);
    unset($details['is_expired']);
    unset($details['is_installable']);
    unset($details['is_multi_domain']);
    unset($details['is_wildcard']);
    unset($details['key']);
    unset($details['domain']);
    unset($details['months']);
    unset($details['package_id']);
    unset($details['client_id']);
    unset($details['identity']);

    if (!is_null($details['valid_from_date'])) {
      $details['valid_from_date'] = (new \DateTimeImmutable(date('Y-m-d h:i:s',$details['valid_from_date'])))->format('Y-m-d h:i:s');
    } else {
      unset($details['valid_from_date']);
    }
    
    if (!is_null($details['valid_to_date'])) {
      $details['valid_to_date'] = (new \DateTimeImmutable(date('Y-m-d h:i:s',$details['valid_to_date'])))->format('Y-m-d h:i:s');
    } else {
      unset($details['valid_to_date']);
    }
    
    return $details;
  }

  protected function _readfile(string $filename) : string {
    if (! file_exists($filename)) {
        throw new \Exception('File does not exist');
    }

    return file_get_contents($filename);
  }

}
