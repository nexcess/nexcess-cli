<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\CloudAccount;

use Nexcess\Sdk\Cli\ {
  Command\Create as CreateCommand
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
  const ARGS = [['app', ARG::OPTIONAL]];

  /** {@inheritDoc} */
  const ENDPOINT = CloudAccount::class;

  /** {@inheritDoc} */
  const NAME = 'cloud-account:create';

  /** {@inheritDoc} */
  const OPTS = [
    ['app_id', null, OPT::VALUE_REQUIRED],
    ['cloud_id', null, OPT::VALUE_REQUIRED],
    ['domain', null, OPT::VALUE_REQUIRED],
    ['install_app', null, OPT::VALUE_NONE],
    ['package_id', null, OPT::VALUE_REQUIRED]
  ];

  /**
   * {@inheritDoc}
   */
  protected function _getChoices(string $name) : array {

    // @todo need to build these endpoints in the SDK

    if (empty($this->_choices[$name])) {
      switch ($name) {
        case 'app_id':
          $this->_choices['app_id'] = array_column(
            $this->_getEndpoint('App')->list()->toArray(),
            'name',
            'id'
          );
          break;

        case 'cloud_id':
          $clouds = $this->_getEndpoint('Cloud')
            ->list(['status' => 'active'])
            ->toArray();
          foreach ($clouds as $cloud) {
            if ($cloud['status'] !== 'active') {
              continue;
            }
            $this->_choices['cloud_id'][$cloud['id']] =
              $this->_getPhrase('cloud_desc', $cloud);
          }
          break;

        case 'package_id':
          $packages = $this->_getEndpoint('Package')
            ->list([
              'type' => 'virt-guest-cloud',
              'environment_type' => 'production'
            ])
            ->toArray();
          foreach ($packages as $package) {
            $this->_choices['package_id'][$package['id']] =
              $this->_getPhrase('package_desc', $package);
          }
          break;
      }
    }

    return parent::_getChoices($name);
  }

  /**
   * {@inheritDoc}
   */
  protected function _initializeInput() {
    $app = $input->getArgument('app');
      // @todo set $_data defaults based on chosen app
    $this->_input = [
      'app_id' => null,
      'cloud_id' => null,
      'domain' => null,
      'package_id' => null
    ];
  }
}
