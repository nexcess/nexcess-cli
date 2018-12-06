<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\Ssl;

use Nexcess\Sdk\Cli\ {
  Command\ChoiceException,
  Console
};

use Nexcess\Sdk\ {
  Resource\Package\Endpoint as Package,
  Resource\Readable
};

/**
 * @todo re-factor. Break into multiple methods.
 * @todo move abstract functions into their own traits.
 */
trait GetsPackageChoices {

  /**
   * {@inheritDoc} Command\Command::getConsole()
   * @todo move to it's own trait
   */
  abstract public function getConsole() : Console;

  /**
   * {@inheritDoc} Command\InputCommand::getInput()
   * @todo move to it's own trait
   */
  abstract public function getInput(
    string $name = null,
    bool $optional = true
  );

  /**
   * {@inheritDoc} Command\Command::_getEndpoint()
   * @todo move to it's own trait
   */
  abstract protected function _getEndpoint(string $endpoint = null) : Readable;

  /**
   * {@inheritDoc} Command\InputCommand::_padColumns()
   * @todo move to it's own trait
   */
  abstract protected function _padColumns(
    array $details,
    array $columns = null
  ) : array;

  /** @var array {@inheritDoc} InputCommand::$_choices */
  protected $_choices = [];

  /**
   * Gets a map of available ssl certificate types.
   *
   * @param bool $format Apply formatting?
   * @return string[] Map of id:description pairs
   */
  protected function _getPackageChoices(bool $format = true) : array {
    if (empty($this->_choices['package'])) {
      $this->_choices['package'] = array_column(
        $this->_getEndpoint(Package::class)
          ->list([
            'type' => 'ssl',
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
