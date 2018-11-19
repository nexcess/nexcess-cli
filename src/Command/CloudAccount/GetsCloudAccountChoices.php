<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\CloudAccount;

use Nexcess\Sdk\Cli\Command\CloudAccount\CloudAccountException;

use Nexcess\Sdk\Resource\Readable;

trait GetsCloudAccountChoices {

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
  protected function _getCloudAccountChoices(bool $format = true) : array {
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
