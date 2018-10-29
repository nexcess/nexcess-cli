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

  /**
   * {@inheritDoc}
   */
  public function runProvider() : array {
    $testcases = parent::runProvider();
    $testcases[] = [
      [],
      [],
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
