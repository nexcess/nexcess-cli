<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\CloudAccount;

use Nexcess\Sdk\Cli\Command\CloudAccount\CloudAccountException;

use Nexcess\Sdk\ {
  Resource\Package\Endpoint as Package,
  Resource\Readable
};

trait GetsPackageChoices {

  /**
   * {@inheritDoc} Command\Command::_getEndpoint()
   */
  abstract protected function _getEndpoint(string $endpoint = null) : Readable;

  /** @var array {@inheritDoc} InputCommand::$_choices */
  protected $_choices = [];

  /**
   * Gets a map of available cloud accounts.
   *
   * @param bool $format Apply formatting?
   * @return string[] Map of id:description pairs
   */
  protected function _getPackageChoices(bool $format = true) : array {
    if (empty($this->_choices['package_id'])) {
      $this->_choices['package_id'] = array_column(
        $this->_getEndpoint(Package::class)
          ->list([
            'type' => 'virt-guest-cloud',
            'environment_type' => 'production'
          ])
          ->toArray(true),
        null,
        'id'
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









    if (empty($this->_choices['cloud_account_id'])) {
      $choices = array_column(
        $this->_getEndpoint()->list()->toArray(),
        null,
        'id'
      );
      if (empty($choices)) {
        throw new CloudAccountException(
          CloudAccountException::NO_CLOUD_ACCOUNT_CHOICES
        );
      }

      foreach ($choices as $id => $cloudaccount) {
        $this->_choices['cloud_account_id'][$id] =
          "{$cloudaccount['ip']} {$cloudaccount['domain']}";
      }
    }
    return $this->_choices['cloud_account_id'];
  }
}
