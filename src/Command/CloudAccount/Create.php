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
  Util\Config,
  Util\Util
};

use Nexcess\Sdk\Cli\ {
  Command\Create as CreateCommand,
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
class Create extends CreateCommand {

  /** {@inheritDoc} */
  const ARGS = ['app' => [Arg::OPTIONAL]];

  /** {@inheritDoc} */
  const ENDPOINT = CloudAccount::class;

  /** {@inheritDoc} */
  const INPUTS = [
    'app_id' => Util::FILTER_INT,
    'cloud_id' => Util::FILTER_INT,
    'domain' => null,
    'install_app' => Util::FILTER_BOOL,
    'package_id' => Util::FILTER_INT
  ];

  /** {@inheritDoc} */
  const NAME = 'cloud-account:create';

  /** {@inheritDoc} */
  const OPTS = [
    'app-id' => [OPT::VALUE_REQUIRED],
    'cloud-id' => [OPT::VALUE_REQUIRED],
    'domain' => [OPT::VALUE_REQUIRED],
    'install-app' => [OPT::VALUE_NONE],
    'package-id' => [OPT::VALUE_REQUIRED]
  ];

  /** {@inheritDoc} */
  const RESTRICT_TO = [Config::COMPANY_NEXCESS];

  /**
   * {@inheritDoc}
   */
  public function initialize(Input $input, Output $output) {
    parent::initialize($input, $output);

    $app = $input->getArgument('app');
    if ($app !== null) {
      $this->_lookupChoice('app_id', $app);
    }
  }

  /**
   * {@inheritDoc}
   */
  protected function _getChoices(string $name, bool $format = true) : array {
    switch ($name) {
      case 'app_id':
        return $this->_getAppChoices($format);
      case 'cloud_id':
        return $this->_getCloudChoices($format);
      case 'package_id':
        return $this->_getPackageChoices($format);
      default:
        return parent::_getChoices($name, $format);
    }
  }

  protected function _getAppChoices(bool $format) : array {
    if (empty($this->_choices['app_id'])) {
      $this->_choices['app_id'] = array_column(
        $this->_getEndpoint('App')->list()->toArray(true),
        'name',
        'app_id'
      );
      // @todo this is hacky
      uasort(
        $this->_choices['app_id'],
        function ($a, $b) {
          return (strpos($a, 'Flexible') !== false) ? -1 : 1;
        }
      );
    }
    $apps = $this->_choices['app_id'];

    if ($format) {
      $max = max(array_map('strlen', $apps));
      foreach ($apps as $id => $app) {
        $apps[$id] = $this->getPhrase(
          'app_desc',
          ['app' => ' ' . str_pad($app, $max) . ' ']
        );
      }
    }

    return $apps;
  }

  protected function _getCloudChoices(bool $format) : array {
    if (empty($this->_choices['cloud_id'])) {
      $this->_choices['cloud_id'] = array_column(
        $this->_getEndpoint('Cloud')
          ->list(['status' => 'active'])
          ->toArray(true),
        null,
        'cloud_id'
      );
    }
    $clouds = $this->_choices['cloud_id'];

    if ($format) {
      $max = max(array_map('strlen', array_column($clouds, 'location')));
      foreach ($clouds as $id => $cloud) {
        $cloud['location'] = ' ' . str_pad($cloud['location'], $max) . ' ';
        $clouds[$id] = $this->getPhrase('cloud_desc', $cloud);
      }
      return $clouds;
    }

    foreach ($clouds as $id => $cloud) {
      $clouds[$id] = $cloud['location_code'];
    }
    return $clouds;
  }

  protected function _getPackageChoices(bool $format) : array {
    if (empty($this->_choices['package_id'])) {
      $this->_choices['package_id'] = array_column(
        $this->_getEndpoint('Package')
          ->list([
            'type' => 'virt-guest-cloud',
            'environment_type' => 'production'
          ])
          ->toArray(true),
        null,
        'package_id'
      );
    }
    $packages = $this->_choices['package_id'];

    if ($format) {
      $max = max(array_map('strlen', array_column($packages, 'name')));
      foreach ($packages as $id => $package) {
        $package['name'] = ' ' . str_pad($package['name'], $max) . ' ';
        $packages[$id] = $this->getPhrase('package_desc', $package);
      }
      return $packages;
    }

    foreach ($packages as $id => $package) {
      $packages[$id] = $package['name'];
    }
    return $packages;
  }

  /**
   * {@inheritDoc}
   */
  protected function _getSummary(array $details) : array {
    return [
      'status' => $details['status'],
      'domain' => $details['cloud_account_domain'],
      'temp_domain' => $details['cloud_account_temp_domain'],
      'app' => $details['cloud_account_app']->get('name'),
      'service_level' => $details['description'],
      'cloud' => "{$details['location']->get('location')} " .
        "({$details['location']->get('location_code')})"
    ];
  }
}
