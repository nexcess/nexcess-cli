<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\CloudAccount;

use DateTimeImmutable as DateTime;

use Nexcess\Sdk\ {
  Resource\CloudAccount\Endpoint,
  Util\Config
};

use Nexcess\Sdk\Cli\ {
  Command\CloudAccount\GetsCloudAccountChoices,
  Command\Show as ShowCommand
};

use Symfony\Component\Console\ {
  Input\InputInterface as Input,
  Input\InputOption as Opt,
  Output\OutputInterface as Output
};

/**
 * Creates a new Cloud Account.
 */
class Show extends ShowCommand {
  use GetsCloudAccountChoices;

  /** {@inheritDoc} */
  const ENDPOINT = Endpoint::class;

  /** {@inheritDoc} */
  const NAME = 'cloud-account:show';

  /** {@inheritDoc} */
  const OPTS = [
    'domain' => [OPT::VALUE_REQUIRED],
    'ip' => [Opt::VALUE_REQUIRED]
  ];

  /** {@inheritDoc} */
  const RESTRICT_TO = [Config::COMPANY_NEXCESS];

  /** {@inheritDoc} */
  const SUMMARY_KEYS = [
    'id',
    'app',
    'deploy_date',
    'domain',
    'environment',
    'ip',
    'location',
    'service',
    'service.status',
    'status',
    'temp_domain',
    'unix_username'
  ];

  /**
   * {@inheritDoc}
   */
  public function initialize(Input $input, Output $output) {
    parent::initialize($input, $output);

    if ($this->_input['id'] === null) {
      $lookup = $input->getOption('domain') ?? $input->getOption('ip');
      if (! empty($lookup)) {
        $this->_lookupChoice('id', $lookup);
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  protected function _getChoices(string $name, bool $format = true) : array {
    switch ($name) {
      case 'id':
        return $this->_getCloudAccountChoices($format);
      default:
        return parent::_getChoices($name, $format);
    }
  }

  /**
   * {@inheritDoc}
   */
  protected function _getSummary(array $details) : array {
    $details = parent::_getSummary($details);

    $details['app'] = $this->getPhrase('summary_app', $details['app']);
    $details['deploy_date'] = (new DateTime("@{$details['deploy_date']}"))
      ->format('Y-m-d H:i:s T');
    $details['location'] = $this->getPhrase(
      'summary_location',
      $details['location']
    );
    $details['service'] = $this->getPhrase(
      'summary_service',
      $details['service']
    );

    return $details;
  }
}
