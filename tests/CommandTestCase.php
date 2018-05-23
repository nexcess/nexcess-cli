<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Tests;

use Nexcess\Sdk\Cli\ {
  Console,
  Command\Command,
  Exception\CommandException
};

use PhpUnit\Framework\TestCase;

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
class CommandTestCase extends TestCase {

  /**
   * Gets a Console applciation instance for testing.
   *
   * @param array $options Config option overrides
   * @return Console A sandboxed console instance
   */
  protected function _getConsole(array $options = []) : Console {
    $options['sandboxed'] = true;
    return new Console($options);
  }

  /**
   * Intercepts and responds to questions during Command::interact.
   *
   * @param Command $command The command to mock interactions on
   * @param array[] $interactions List of expected interactions:
   *  - string $0 PCRE to match asked question against
   *  - string $1 Response to provide
   */
  protected function _mockInteractions(Command $command, array $interactions) {
    $interactor = new class ($this, $interactions) extends QuestionHelper {
      protected $_interactions;
      protected $_testcase;

      public function __construct($testcase, $interactions) {
        $this->_testcase = $testcase;
        $this->_interactions = $interactions;
      }

      public function doAsk(Output $output, Question $question) {
        $asked = $question->getQuestion();

        if (empty($this->_interactions)) {
          $this->_testcase->fail("Unexpected interaction: {$asked}");
        }
        [$expected, $response] = array_shift($this->_interactions);
        $this->_testcase->assertRegExp(
          "({$expected})",
          $asked,
          'Interaction must be correctly translated and formatted'
        );

        // from here on is more-or-less what doAsk() normally does
        $this->writePrompt($output, $question);
        $response = (strlen($response) > 0) ?
          $response :
          $question->getDefault();
        $normalizer = $question->getNormalizer();
        return is_callable($normalizer) ? $normalizer($response) : $response;
      }
    };

    $command->getHelperSet()->set($interactor, 'question');
  }

  /**
   * Executes a command and returns the CommandTester for making assertions.
   *
   * @param Command $command The command to execute (use Console::find()!)
   * @param array $invocation The args/opts to invoke the command with
   * @param array $interactions Responses for interactions
   * @return CommandTester The tester instance
   */
  protected function _testRun(
    Command $command,
    array $invocation,
    array $interactions = []
  ) : CommandTester {
    $tester = new CommandTester($command);

    if (! empty($interactions)) {
      // old and busted
      //$tester->setInputs($interactions);

      // new hotness
      $this->_mockInteractions($command, $interactions);
    }

    $invocation['command'] = $command->getName();
    $tester->execute($invocation);

    return $tester;
  }
}
