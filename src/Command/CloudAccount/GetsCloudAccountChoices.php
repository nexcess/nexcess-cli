<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\CloudAccount;

use Nexcess\Sdk\Cli\Console;

use Nexcess\Sdk\ {
  Resource\CloudAccount\Endpoint,
  Resource\Readable
};

trait GetsCloudAccountChoices {

  /**
   * {@inheritDoc} Command\Command::getConsole()
   */
  abstract public function getConsole() : Console;

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
    if (empty($this->_choices['cloud_account'])) {
      $this->_choices['cloud_account'] = array_column(
        $this->_getEndpoint(Endpoint::class)->list()->toArray(),
        null,
        'id'
      );
      if (empty($this->_choices['cloud_account'])) {
        throw new CloudAccountException(
          CloudAccountException::NO_CLOUD_ACCOUNT_CHOICES
        );
      }
    }

    $choices = $this->_choices['cloud_account'];

    if ($format) {
      $console = $this->getConsole();
      foreach ($choices as $id => $cloudaccount) {
        $choices[$id] = $console->translate(
          'console.cloud_account.choices.cloud_account',
          $cloudaccount
        );
      }
      return $choices;
    }

    foreach ($choices as $id => $cloudaccount) {
      $choices[$id] = $cloudaccount['domain'];
    }
    return $choices;
  }
}
