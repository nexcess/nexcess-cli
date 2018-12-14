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
  Resource\Ssl\Endpoint,
  Resource\Readable
};

/**
 * @todo See notes in GetPackageChocies about moving abstract methods.
 */
trait GetsSslChoices {

  /**
   * {@inheritDoc} Command\Command::getConsole()
   */
  abstract public function getConsole() : Console;

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
   * Gets a map of available ssl certificates.
   *
   * @param bool $format Apply formatting?
   * @return string[] Map of id:description pairs
   FIX
   */
  protected function _getSslChoices(bool $format = true) : array {
    if (empty($this->_choices['ssl'])) {
      $this->_choices['ssl'] = array_column(
        $this->_getEndpoint(Endpoint::class)->list()->toArray(),
        null,
        'id'
      );
      if (empty($this->_choices['ssl'])) {
        throw new ChoiceException(
          ChoiceException::NO_CLOUD_ACCOUNT_CHOICES
        );
      }
    }

    $choices = $this->_choices['ssl'];

    if ($format) {
      $choices = $this->_padColumns($choices, ['common_name', 'id']);
      $console = $this->getConsole();
      foreach ($choices as $id => $certificate) {
        $choices[$id] = $console->translate(
          'console.cloud_account.choices.ssl',
          $certificate
        );
      }
      return $choices;
    }
    return array_column($choices, 'common_name', 'id');
  }
}
