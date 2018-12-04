<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Tests\Command;

use Throwable;

use Nexcess\Sdk\Cli\ {
  Command\ChoiceException,
  Command\CloudAccount\Backup\GetsBackupChoices,
  Command\InputCommand,
  Console,
  Tests\TestCase
};

abstract class GetsChoicesTestCase extends TestCase {

  /**
   * @var array[] Default testcases for formattedChoicesProvider().
   *
   * Each testcase is an argument list for testGetFormattedChoices(),
   * and must include:
   *  - array $0 List of [requestline, data] tuples to stage as responses
   *  - array $1 Expected choice:[details] pairs.
   *
   * @example
   * ```php
   *  [[
   *    [
   *      'choice-endpoint',
   *      [
   *        ['id' => 1, 'foo' => 'hello', 'bar' => 'world'],
   *        ['id' => 2, 'foo' => 'goodbye', 'bar' => 'sweet prince']
   *      ]
   *    ],
   *    [1 => ['hello', 'world'], 2 => '['goodbye', 'sweet prince']]
   *  ]];
   * ```
   */
  protected const _FORMATTED_CHOICES_TESTCASES = [];

  /**
   * @var array[] Default testcases for noChoicesProvider().
   *
   * Each testcase is an argument list for testGetNoChoices(),
   * and must include:
   *  - array $0 List of [requestline, data] tuples to stage as responses
   *  - Throwable|array $1 Expected exception, or map default choices (if any)
   *
   * @example
   * ```php
   *  return [
   *    ['choice-endpoint', []],
   *    new ChoiceException(ChoiceException::NO_EXAMPLE_CHOICES)
   *  ];
   * ```
   */
  protected const _NO_CHOICES_TESTCASES = [];

  /**
   * @var array[] Default testcases for unformattedChoicesProvider().
   *
   * Each testcase is an argument list for testGetUnformattedChoices(),
   * and must include:
   *  - array $0 List of [requestline, data] tuples to stage as responses
   *  - array $2 Expected choice:description pairs.
   *
   * @example
   * ```php
   *  return [
   *    [
   *      'choice-endpoint',
   *      [
   *        ['id' => 1, 'foo' => 'hello', 'bar' => 'world'],
   *        ['id' => 2, 'foo' => 'goodbye', 'bar' => 'sweet prince']
   *      ],
   *      [1 => 'hello', 2 => 'goodbye']
   *    ]
   *  ];
   * ```
   */
  protected const _UNFORMATTED_CHOICES_TESTCASES = [];

  /**
   * Utility to invoke the subject get**Choices method and return its results.
   *
   * @example
   * ```php
   *  $chooser = new class($console) extends InputCommand {
   *    use GetsSomeExampleChoices;
   *    const NAME = 'test:get-some-example-choices';
   *  };
   *  return $this->_invokeNonpublicMethod(
   *    $chooser,
   *    '_getSomeExampleChoices',
   *    $format
   *  );
   * ```
   *
   * @param Console $console The test console
   * @param bool $format Pass $format=true to subject get**Choices method?
   * @return array Choices returned from get**Choices method invocation
   */
  abstract protected function _getChoices(
    Console $console,
    bool $format
  ) : array;

  /**
   * @dataProvider unformattedChoicesProvider
   *
   * @param array $responses Map of request_line:[response_data] pairs to stage
   * @param array $expected Map of expected choice:description pairs
   */
  public function testGetUnformattedChoices(
    array $responses,
    array $expected
  ) {
    $console = $this->_getConsole();
    $console->getSandbox()->play(
      function ($api, $sandbox) use ($console, $responses, $expected) {
        foreach ($responses as [$request_line, $response]) {
          $sandbox->makeResponse($request_line, 200, $response);
        }

        $choices = $this->_getChoices($console, false);
        foreach ($expected as $choice => $description) {
          $this->assertArrayHasKey(
            $choice,
            $choices,
            "choice '{$choice}' must exist in choice index"
          );

          $this->assertEquals(
            $description,
            $choices[$choice],
            "value for choice '{$choice}' must be '{$description}'"
          );

          // reduce
          unset($choices[$choice]);
        }

        $this->assertEmpty($choices, 'does not return unexpected choices');
      }
    );
  }

  /**
   * DataProvider for testGetUnformattedChoices().
   *
   * @return array[] List of testcases
   */
  public function unformattedChoicesProvider() : array {
    return static::_UNFORMATTED_CHOICES_TESTCASES;
  }

  /**
   * @dataProvider formattedChoicesProvider
   *
   * @param array $responses Map of request_line:[response_data] pairs to stage
   * @param array $expected Map of expected choice:description pairs
   */
  public function testGetFormattedChoices(
    array $responses,
    array $expected
  ) {
    $console = $this->_getConsole();
    $console->getSandbox()->play(
      function ($api, $sandbox) use ($console, $responses, $expected) {
        foreach ($responses as [$request_line, $response]) {
          $sandbox->makeResponse($request_line, 200, $response);
        }

        $choices = $this->_getChoices($console, true);
        foreach ($expected as $choice => $details) {
          $this->assertArrayHasKey(
            $choice,
            $choices,
            "choice '{$choice}' must exist in choice index"
          );

          foreach ($details as $detail) {
            $this->assertContains(
              $detail,
              $choices[$choice],
              "detail '{$detail}' must be found in description for '{$choice}'"
            );
          }

          // reduce
          unset($choices[$choice]);
        }

        $this->assertEmpty($choices, 'does not return unexpected choices');
      }
    );
  }

  /**
   * DataProvider for testGetFormattedChoices().
   *
   * @return array[] List of testcases
   */
  public function formattedChoicesProvider() : array {
    return static::_FORMATTED_CHOICES_TESTCASES;
  }

  /**
   * @dataProvider noChoicesProvider
   *
   * @param array $responses Map of request_line:[response_data] pairs to stage
   * @param int $expected Expected ChoiceException code
   */
  public function testGetNoChoices(array $responses, int $expected) {
    $console = $this->_getConsole();
    $console->getSandbox()->play(
      function ($api, $sandbox) use ($console, $responses, $expected) {
        foreach ($responses as [$request_line, $response]) {
          $sandbox->makeResponse($request_line, 200, $response);
        }

        $this->setExpectedException(new ChoiceException($expected));
        $this->_getChoices($console, false);
      }
    );
  }

  /**
   * DataProvider for testGetFormattedChoices().
   *
   * @return array[] List of testcases
   */
  public function noChoicesProvider() : array {
    return static::_NO_CHOICES_TESTCASES;
  }
}
