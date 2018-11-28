<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Command\CloudAccount\Backup;

use Nexcess\Sdk\Cli\ {
  Command\CloudAccount\CloudAccountException,
  Console
};

use Nexcess\Sdk\ {
  Resource\CloudAccount\Entity as CloudAccount,
  Resource\CloudAccount\Endpoint,
  Resource\Readable
};

trait GetsBackupChoices {

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
  protected function _getBackupChoices(bool $format = true) : array {
    if (empty($this->_choices['backup'])) {
      $id = $this->getInput('cloud_account_id', false);
      $endpoint = $this->_getEndpoint();
      assert($endpoint instanceof Endpoint);
      $cloudaccount = $endpoint->retrieve($id);
      assert($cloudaccount instanceof CloudAccount);

      $this->_choices['backup'] = array_column(
        $endpoint->listBackups($cloudaccount)->toArray(),
        'filename',
        'filename'
      );
      if (empty($this->_choices['backup'])) {
        throw new CloudAccountException(
          CloudAccountException::NO_BACKUP_CHOICES,
          ['cloud_account_id' => $id, 'domain' => $cloudaccount->get('domain')]
        );
      }
    }

    $choices = $this->_choices['backup'];

    if ($format) {
      $console = $this->getConsole();
      foreach ($choices as $filename) {
        $choices[$filename] = $console->translate(
          'console.cloud_account.choices.backup',
          ['filename' => $filename]
        );
      }
      return $choices;
    }

    return $choices;
  }
}
