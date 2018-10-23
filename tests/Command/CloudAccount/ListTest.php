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
  Command\Tests\ListTestCase
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
          '#1: nexcess-cli.example.com (203.0.113.1)',
          '#2: nexcess-cli-2.example.com (203.0.113.2)',
          '#3: nexcess-cli-3.example.com (203.0.113.3)'
        ]
      ]
    ];

    return $testcases;
  }
}
