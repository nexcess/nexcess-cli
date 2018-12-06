<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Tests\Command\ApiToken;

use Nexcess\Sdk\Cli\ {
  Command\ApiToken\ShowList,
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

  /** @var string Path to "GET /api-token" response resource. */
  const _RESOURCE_GET_LIST = 'GET-%2Fapi-token%3F.json';

  /**
   * {@inheritDoc}
   */
  public function runProvider() : array {
    $testcases = parent::runProvider();
    $testcases[] = [
      [],
      [],
      [['GET /api-token', 200, $this->_getResource(self::_RESOURCE_GET_LIST)]],
      [
        'output' => [
          'Example API Token',
          'Another Example API Token',
          'And One More Example API Token'
        ]
      ]
    ];

    return $testcases;
  }
}
