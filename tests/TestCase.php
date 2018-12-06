<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Tests;

use Nexcess\Sdk\ {
  Tests\TestCase as SdkTestCase
};

use Nexcess\Sdk\Cli\Console;

use PhpUnit\Framework\ExpectationFailedException as PhpUnitException;

use Symfony\Component\Console\ {
  Helper\QuestionHelper,
  Input\InputInterface as Input,
  Output\OutputInterface as Output,
  Question\Question
};

/**
 * Base class for nexcess-cli testcases.
 */
abstract class TestCase extends SdkTestCase {

  /** @var string Path to test resources. */
  protected const _RESOURCE_PATH = __DIR__;

  /**
   * Gets a Console applciation instance for testing.
   *
   * @param array $options Config option overrides
   * @return Console A sandboxed console instance
   */
  protected function _getConsole(array $options = []) : Console {
    $options['sandboxed'] = true;
    $console = new Console($options);
    return $console;
  }

  /**
   * Builds a question helper to handle questions during interactive tests.
   *
   * For each interaction,
   *  - asserts that a question is expected
   *  - asserts that the question matches the next expected question
   *  - enters the provided response
   *
   * @param SymfonyApplication|SymfonyCommand $on Object to mock interaction on
   * @param array[] $interactions Ordered list of expected interactions:
   *  - string $0 Literal string or PCRE to match asked question against
   *  - string $1 Response to provide
   */
  protected function _mockInteractions($on, array $interactions) {
    // phpcs:disable
    $interactor = new class ($this, $interactions) extends QuestionHelper {
      protected $_interactions = [];
      protected $_testcase;

      public function __construct($testcase, $interactions) {
        $this->_testcase = $testcase;
        $this->_interactions = $interactions;
      }

      public function ask(Input $input, Output $output, Question $question) {
        $asked = $question->getQuestion();

        if (empty($this->_interactions)) {
          $this->_testcase->fail("Unexpected interaction: {$asked}");
        }
        [$expected, $response] = array_shift($this->_interactions);

        try {
          $this->_testcase->assertEquals($expected, $asked);
        } catch (PhpUnitException $e) {
          if (@preg_match($expected, '') === false) {
            throw $e;
          }
          $this->_testcase->assertRegExp($expected, $asked);
        }

        // from here on is more-or-less what doAsk() normally does
        $response = (strlen($response) > 0) ?
          $response :
          $question->getDefault();
        $normalizer = $question->getNormalizer();
        return is_callable($normalizer) ? $normalizer($response) : $response;
      }
    };
    // phpcs:enable

    $on->getHelperSet()->set($interactor, 'question');
  }
}
