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
  Console,
  Command\Command,
  Tests\TestCase
};

use PhpUnit\Framework\ExpectationFailedException as PhpUnitException;

use Symfony\Component\Console\ {
  Helper\QuestionHelper,
  Input\InputInterface as Input,
  Output\OutputInterface as Output,
  Question\Question,
  Tester\CommandTester
};

/**
 * Base class for nexcess-cli command testcases.
 */
abstract class CommandTestCase extends TestCase {

  /**
   * @covers Command::_formatSummary
   */
  public function testFormatSummary() {
    $stub = new class() extends Command {
      public function __construct() {}
      public function getPhrase(string $key, array $context = []) : string {
        return [
          'summary_key.a' => 'A',
          'summary_key.b' => 'B',
          'summary_title' => 'Test Summary'
        ][$key];
      }
    };

    $summary = $this->_invokeNonpublicMethod(
      $stub,
      '_formatSummary',
      ['a' => 'foo', 'b' => 'bar']
    );
    $this->assertEquals(
      "Test Summary\n<info>  A</info>: foo\n<info>  B</info>: bar",
      $summary,
      'properly translates and indents summary items'
    );
  }

  /**
   * @group integration
   * @dataProvider runProvider
   *
   * @param array $invocation Map of args/opts to invoke the command with
   * @param array $interactions List of [expected, response]s for interactions
   * @param array $responses List of [request_line, status, data]s
   *  to stage on the sandbox for api calls
   * @param array|Throwable $expected Expected [code, [output]] or exception
   */
  public function testRun(
    array $invocation,
    array $interactions,
    array $responses,
    $expected
  ) {
    if ($expected instanceof Throwable) {
      $this->setExpectedException($expected);
    }

    $console = $this->_getConsole();
    $sandbox = $console->getSandbox();
    foreach ($responses as [$request_line, $status, $response]) {
      $sandbox->makeResponse($request_line, $status, $response);
    }
    $command = static::_SUBJECT_FQCN;
    $actual = $this->_testRun(
      new $command($console),
      $invocation,
      $interactions
    );

    $this->assertEquals(
      $expected['exit_code'] ?? Console::EXIT_SUCCESS,
      $actual->getStatusCode()
    );

    $output = $actual->getDisplay();
    foreach ($expected['output'] ?? [] as $content) {
      $this->assertContains($content, $output);
    }
  }

  /**
   * @return array[] List of testcases
   */
  abstract public function runProvider() : array;

  /**
   * Executes a command and returns the CommandTester for making assertions.
   *
   * @param Command $command The command to execute (use Console::find()!)
   * @param array $invocation Map of args/opts to invoke the command with
   * @param array $interactions List of [expected, response]s for interactions
   * @return CommandTester The tester instance
   */
  protected function _testRun(
    Command $command,
    array $invocation,
    array $interactions = []
  ) : CommandTester {
    $tester = new CommandTester($command);

    if (! empty($interactions)) {
      $this->_mockInteractions($command, $interactions);
    }

    $invocation['command'] = $command->getName();
    $tester->execute($invocation);

    return $tester;
  }
}
