<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Tests\Command\CloudAccount\Backup;

use Throwable;

use Nexcess\Sdk\Resource\CloudAccount\Endpoint;

use Nexcess\Sdk\Cli\ {
  Command\ChoiceException,
  Command\CloudAccount\Backup\GetsBackupChoices,
  Command\InputCommand,
  Console,
  Tests\Command\GetsChoicesTestCase
};

class GetsBackupChoicesTest extends GetsChoicesTestCase {

  /** {@inheritDoc} */
  protected const _CHOICE_REQUEST_LINE = 'GET /cloud-account/1/backup';

  /** {@inheritDoc} */
  protected const _EMPTY_CHOICES_CODE =
    ChoiceException::NO_BACKUP_CHOICES;

  /** @var array[] Data to build testcase responses. */
  protected const _CHOICE_RESPONSE_DATA = [
    ['filename' => 'example-filename-1.tar.gz'],
    ['filename' => 'example-filename-2.tar.gz'],
    ['filename' => 'example-filename-3.tar.gz']
  ];

  /** {@inheritDoc} */
  protected const _FORMATTED_CHOICES_TESTCASES = [[
    [
      ['GET /cloud-account/1', ['id' => 1]],
      ['GET /cloud-account/1/backup', self::_CHOICE_RESPONSE_DATA]
    ],
    [
      'example-filename-1.tar.gz' => ['example-filename-1.tar.gz'],
      'example-filename-2.tar.gz' => ['example-filename-2.tar.gz'],
      'example-filename-3.tar.gz' => ['example-filename-3.tar.gz']
    ]
  ]];

  /** {@inheritDoc} */
  protected const _NO_CHOICES_TESTCASES = [[
    [
      ['GET /cloud-account', [['id' => 1]]],
      ['GET /cloud-account/1', ['id' => 1]],
      ['GET /cloud-account/1/backup', []]
    ],
    ChoiceException::NO_BACKUP_CHOICES
  ]];

  /** {@inheritDoc} */
  protected const _UNFORMATTED_CHOICES_TESTCASES = [[
    [
      ['GET /cloud-account/1', ['id' => 1]],
      ['GET /cloud-account/1/backup', self::_CHOICE_RESPONSE_DATA]
    ],
    [
      'example-filename-1.tar.gz' => 'example-filename-1.tar.gz',
      'example-filename-2.tar.gz' => 'example-filename-2.tar.gz',
      'example-filename-3.tar.gz' => 'example-filename-3.tar.gz'
    ]
  ]];

  /**
   * {@inheritDoc}
   */
  protected function _getChoices(
    Console $console,
    bool $format
  ) : array {
    $chooser = new class($console) extends InputCommand {
      use GetsBackupChoices;
      public const NAME = 'test:get-backup-choices';
      public const ENDPOINT = Endpoint::class;
      public function getInput(string $name = null, bool $optional = true) {
        return 1;
      }
    };

    return $this->_invokeNonpublicMethod(
      $chooser,
      '_getBackupChoices',
      $format
    );
  }
}
