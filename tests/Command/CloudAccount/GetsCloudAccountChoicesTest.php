<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Tests\Command\CloudAccount;

use Throwable;

use Nexcess\Sdk\Cli\ {
  Command\ChoiceException,
  Command\CloudAccount\GetsCloudAccountChoices,
  Command\InputCommand,
  Console,
  Tests\Command\GetsChoicesTestCase
};

class GetsCloudAccountChoicesTest extends GetsChoicesTestCase {

  /** {@inheritDoc} */
  protected const _CHOICE_REQUEST_LINE = 'GET /cloud-account';

  /** @var array[] Map of data to build testcase responses. */
  protected const _CHOICE_RESPONSE_DATA = [
    ['id' => 1, 'domain' => 'test-1.example.com', 'ip' => '203.0.113.1'],
    ['id' => 2, 'domain' => 'test-2.example.com', 'ip' => '203.0.113.2'],
    ['id' => 3, 'domain' => 'test-3.example.com', 'ip' => '203.0.113.3']
  ];

  /** {@inheritDoc} */
  protected const _FORMATTED_CHOICES_TESTCASES = [[
    [['GET /cloud-account', self::_CHOICE_RESPONSE_DATA]],
    [
      1 => ['test-1.example.com', '203.0.113.1'],
      2 => ['test-2.example.com', '203.0.113.2'],
      3 => ['test-3.example.com', '203.0.113.3']
    ]
  ]];

  /** {@inheritDoc} */
  protected const _NO_CHOICES_TESTCASES = [[
    [['GET /cloud-account', []]],
    ChoiceException::NO_CLOUD_ACCOUNT_CHOICES
  ]];

  /** {@inheritDoc} */
  protected const _UNFORMATTED_CHOICES_TESTCASES = [[
    [['GET /cloud-account', self::_CHOICE_RESPONSE_DATA]],
    [
      1 => 'test-1.example.com',
      2 => 'test-2.example.com',
      3 => 'test-3.example.com'
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
      use GetsCloudAccountChoices;
      const NAME = 'test:get-cloud-account-choices';
    };

    return $this->_invokeNonpublicMethod(
      $chooser,
      '_getCloudAccountChoices',
      $format
    );
  }
}
