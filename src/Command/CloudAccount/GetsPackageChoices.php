<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\CloudAccount;

use Nexcess\Sdk\Cli\ {
  Command\ChoiceException,
  Console
};

use Nexcess\Sdk\ {
  Resource\Package\Endpoint as Package,
  Resource\Readable
};

trait GetsPackageChoices {

  /**
   * {@inheritDoc} Command\Command::getConsole()
   */
  abstract public function getConsole() : Console;

  /**
   * {@inheritDoc} Command\InputCommand::getInput()
   */
  abstract public function getInput(
    string $name = null,
    bool $optional = true
  );

  /**
   * {@inheritDoc} Command\Command::_getEndpoint()
   */
  abstract protected function _getEndpoint(string $endpoint = null) : Readable;

  /**
   * {@inheritDoc} Command\InputCommand::_padColumns()
   */
  abstract protected function _padColumns(
    array $details,
    array $columns = null
  ) : array;

  /** @var array {@inheritDoc} InputCommand::$_choices */
  protected $_choices = [];

  /**
   * Gets a map of available cloud accounts.
   *
   * @param bool $format Apply formatting?
   * @return string[] Map of id:description pairs
   */
  protected function _getPackageChoices(bool $format = true) : array {
    if (empty($this->_choices['package'])) {
      $this->_choices['package'] = array_column(
        $this->_getEndpoint(Package::class)
          ->list([
            'type' => 'virt-guest-cloud',
            'environment_type' => 'production'
          ])
          ->toArray(true),
        null,
        'id'
      );
      if (empty($this->_choices['package'])) {
        throw new ChoiceException(
          ChoiceException::NO_CLOUD_ACCOUNT_PACKAGE_CHOICES
        );
      }
    }

    $choices = $this->_choices['package'];

    if ($format) {
      $choices = $this->_padColumns($choices, ['name', 'monthly_fee']);
      $console = $this->getConsole();
      foreach ($choices as $id => $package) {
        $choices[$id] = $console->translate(
          'console.cloud_account.choices.package',
          $package
        );
      }
      return $choices;
    }

    return array_column($choices, 'name', 'id');
  }
}
