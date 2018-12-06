<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Tests\Command\CloudAccount;

use Nexcess\Sdk\Cli\ {
  Command\CloudAccount\ShowList,
  Tests\Command\ListTestCase
};

/**
 * Base class for "list" command tests.
 */
class ListTest extends ListTestCase {

  /** {@inheritDoc} */
  const _SUBJECT_FQCN = ShowList::class;

  /** {@inheritDoc} */
  protected const _RESOURCE_PATH = __DIR__ . '/resources';

  /** @var string Path to "GET /cloud-account" response resource. */
  const _RESOURCE_GET_LIST = 'GET-%2Fcloud-account%3F.json';

  /**
   * {@inheritDoc}
   */
  public function runProvider() : array {
    $testcases = parent::runProvider();
    $testcases[] = [
      [],
      [],
      [
        [
          'GET /cloud-account',
          200,
          $this->_getResource(self::_RESOURCE_GET_LIST)
        ]
      ],
      [
        'output' => [
          'nexcess-cli.example.com',
          '203.0.113.1',
          'nexcess-cli-2.example.com',
          '203.0.113.2',
          'nexcess-cli-3.example.com',
          '203.0.113.3'
        ]
      ]
    ];

    return $testcases;
  }
}
