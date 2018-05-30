<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\CloudAccount;

use Nexcess\Sdk\ {
  Endpoint\CloudAccount,
  Model\Modelable as Model,
  Util\Config
};

use Nexcess\Sdk\Cli\ {
  Command\Show as ShowCommand,
  Exception\CloudAccountException
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
class Show extends ShowCommand {

  /** {@inheritDoc} */
  const ENDPOINT = CloudAccount::class;

  /** {@inheritDoc} */
  const NAME = 'cloud-account:show';

  /** {@inheritDoc} */
  const OPTS = [
    'domain' => [OPT::VALUE_REQUIRED],
    'ip' => [Opt::VALUE_REQUIRED]
  ];

  /** {@inheritDoc} */
  const RESTRICT_TO = [Config::COMPANY_NEXCESS];

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
        if (empty($this->_choices['id'])) {
          $this->_choices['id'] = array_column(
            $this->_getEndpoint()->list()->toArray(true),
            null,
            'service_id'
          );
          foreach ($this->_choices['id'] as $id => $service) {
            $this->_choices['id'][$id] = $service['cloud_account_ip'] .
              " {$service['cloud_account_domain']}";
          }
        }
        return $this->_choices['id'];
      default:
        return parent::_getChoices($name, $format);
    }
  }
}
