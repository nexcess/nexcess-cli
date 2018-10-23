<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli\Tests;

use Closure,
  Throwable;
use Nexcess\Sdk\SandBox\Sandbox;
use Nexcess\Sdk\Cli\ {
  Console,
  ConsoleException,
  Command\Command,
  Tests\TestCase
};
use PhpUnit\Framework\ExpectationFailedException as PhpUnitException;
use Symfony\Component\Console\ {
  Command\Command as SymfonyCommand,
  Input\ArrayInput,
  Input\InputInterface as Input,
  Output\BufferedOutput,
  Output\OutputInterface as Output,
  Question\Question
};

/**
 * Tests for the console application class.
 */
class ConsoleTest extends TestCase {

  /** {@inheritDoc} */
  const _SUBJECT_FQCN = Console::class;

  /**
   * @covers Console::ask
   * @dataProvider askProvider
   *
   * @param array $question [question, default] for ask()
   * @param string $answer Answer to provide
   */
  public function testAsk(array $question, string $answer) {
    [$question, $default] = $question;
    $console = $this->_getSubject();
    $this->_mockInteractions($console, [[$question, $answer]]);

    $this->assertEquals(
      ((strlen($answer) > 0) ? $answer : ($default ?? '')),
      $console->ask($question, $default)
    );
  }

  /**
   * @return array{} List of testcases
   */
  public function askProvider() : array {
    return [
      [['what is your name?', null], 'Alice'],
      [['what is your name?', null], ''],
      [['what is your favorite color?', 'red'], 'blue'],
      [['what is your favorite color?', 'red'], '']
    ];
  }

  /**
   * @covers Console::choose
   * @dataProvider choiceProvider
   *
   * @param array $question [question, [choices], default] for ask()
   * @param string $answer Answer to provide
   * @param int|Throwable $expected Index of matching choice or throwable
   */
  public function testChoose(array $question, string $answer, $expected) {
    [$question, $choices, $default] = $question;
    $console = $this->_getSubject();
    $this->_mockInteractions($console, [[$question, $answer]]);

    $this->assertEquals(
      $choices[$expected],
      $console->choose($question, $choices, $default)
    );
  }

  /**
   * @return array{} List of testcases
   */
  public function choiceProvider() : array {
    $question = 'what is your favorite color?';
    $choices = ['red', 'green', 'blue'];

    $testcases = [];
    foreach ($choices as $key => $choice) {
      $testcases[] = [[$question, $choices, $choice], '', $key];
      $testcases[] = [[$question, $choices, $choices[0]], $choice, $key];
      $testcases[] = [[$question, $choices, $choices[1]], $choice, $key];
      $testcases[] = [[$question, $choices, $choices[2]], $choice, $key];
    }

    return $testcases;
  }

  /**
   * @covers Console::_buildConfig
   * @covers Console::_loadProfile
   * @covers Console::getConfig
   * @covers Console::isDebug
   * @covers Console::waits
   * @dataProvider configProvider
   *
   * @param Input $input Test input
   * @param array $expected Map of property:value pair to check in config
   */
  public function testConfigure(Input $input, array $expected) {
    $console = $this->_getSubject([], $input);
    $config = $console->getConfig();

    foreach ($expected as $property => $expect) {
      $this->assertEquals($expect, $config->get($property));
    }

    $this->assertEquals($expected['debug'] ?? false, $console->isDebug());
    $this->assertEquals($expected['wait.always'] ?? false, $console->waits());
  }

  /**
   * @return array{} List of testcases
   */
  public function configProvider() : array {
    return [
      [
        new ArrayInput([
          '--api-token' => 'abcd1234',
          '--sandboxed' => null,
          '--wait' => null,
          '-vvv' => null
        ]),
        [
          'api_token' => 'abcd1234',
          'sandboxed' => true,
          'wait.always' => true,
          'debug' => true
        ]
      ],
      [
        new ArrayInput(['--api-token' => 'abcd1234']),
        [
          'api_token' => 'abcd1234',
          'sandboxed' => false,
          'wait.always' => false,
          'debug' => false
        ]
      ]
    ];
  }

  /**
   * @covers Console::confirm
   * @dataProvider confirmProvider
   *
   * @param array $question [question, default] for ask()
   * @param string $answer Answer to provide
   */
  public function testConfirm(array $question, string $answer = '') {
    [$question, $default] = $question;
    $console = $this->_getSubject();
    $this->_mockInteractions($console, [[$question, $answer]]);

    $this->assertEquals(
      (strlen($answer) > 0) ? (strpos($answer, 'y') === 0) : $default,
      $console->confirm($question, $default)
    );
  }

  /**
   * @return array{} List of testcases
   */
  public function confirmProvider() : array {
    return [
      [['yes or no?', true], 'yes'],
      [['yes or no?', true], 'y'],
      [['yes or no?', true], 'no'],
      [['yes or no?', true], 'n'],
      [['yes or no?', false], 'yes'],
      [['yes or no?', false], 'y'],
      [['yes or no?', false], 'no'],
      [['yes or no?', false], 'n']
    ];
  }

  /**
   * @covers Console::getDefaultInputDefinition
   */
  public function testGetDefaultInputDefinition() {
    $definition = $this->_getSubject()->getDefaultInputDefinition();

    // has common application options
    $options = ['api-token', 'json', 'profile', 'sandboxed', 'wait'];
    foreach ($options as $option) {
      $this->assertTrue($definition->hasOption($option));
    }
  }

  /**
   * @covers Console::getSandbox
   */
  public function testGetSandbox() {
    // gets sandbox if sandboxed
    $this->assertInstanceOf(
      Sandbox::class,
      $this->_getSubject(['sandboxed' => true])->getSandbox()
    );

    // throws if not sandboxed
    $this->setExpectedException(
      new ConsoleException(ConsoleException::NOT_SANDBOXED)
    );
    $this->_getSubject()->getSandbox();
  }

  /**
   * @covers Console::run
   * @covers Console::doRunCommand
   */
  public function testRun() {
    $testcase = $this;
    $console = $this->_getSubject(
      [],
      new ArrayInput(['command' => 'test:command'])
    );
    [$console_input, $console_output] = $console->getIO();

    // phpcs:disable
    $command = $this->_makeCommand(
      $console,
      function ($input, $output)
        use ($console_input, $console_output, $testcase, $console) {
        // uses console i/o if none provided
        $testcase->assertEquals($console_input, $input);
        $testcase->assertEquals($console_output, $output);

        // sets console on command object
        $testcase->assertEquals($console, $this->getApplication());

        return 1;
      }
    );
    // phpcs:enable

    $this->assertEquals(1, $console->run());
    $this->assertContains(
      Console::NAME . ' ' . Console::VERSION,
      $console_output->fetch()
    );

    $console = $this->_getSubject();
    $command_input = new ArrayInput(['command' => 'test:command']);
    $command_output = new BufferedOutput();
    $command = $this->_makeCommand($console);

    // sets console i/o if provided
    $console->run($command_input, $command_output);
    [$console_input, $console_output] = $console->getIO();
    $this->assertEquals($command_input, $console_input);
    $this->assertEquals($command_output, $console_output);
  }

  /**
   * @covers Console::say
   * @covers Console::sayJson
   */
  public function testSay() {
    $console = $this->_getSubject();
    $output = $console->getIO()[Console::GET_IO_OUTPUT];

    // say() writes message to output
    $console->say('hello, world!');
    $this->assertContains('hello, world!', $output->fetch());

    // sayJson() encodes message as json
    $console->sayJson(['foo' => 'bar']);
    $this->assertContains("{\n    \"foo\": \"bar\"\n}", $output->fetch());

    // say() says nothing if --json
    $console = $this->_getSubject([], new ArrayInput(['--json' => null]));
    $output = $console->getIO()[Console::GET_IO_OUTPUT];

    // say() writes message to output
    $console->say('hello, world!');
    $this->assertNotContains('hello, world!', $output->fetch());
  }

  /**
   * @covers Console::translate
   */
  public function testTranslate() {
    $console = $this->_getSubject();

    // returns key if translation no available
    $this->assertEquals('foo', $console->translate('foo'));

    // returns translated message if no context provided
    $this->assertEquals(
      'This is a test',
      $console->translate('console.tests.test_phrase')
    );

    // returns unreplaced message if any context is missing
    $this->assertEquals(
      'This is a {test}',
      $console->translate('console.tests.test_phrase_with_replacement')
    );

    // does context replacement if context provided
    $this->assertEquals(
      'This is a load of barnacles',
      $console->translate(
        'console.tests.test_phrase_with_replacement',
        ['test' => 'load of barnacles']
      )
    );

    // json-encodes non-scalar context
    $this->assertEquals(
      'This is a {"wild":"ride"}',
      $console->translate(
        'console.tests.test_phrase_with_replacement',
        ['test' => ['wild' => 'ride']]
      )
    );
  }

  /**
   * Builds a "stub" command for tests.
   *
   * @param Console $applciation The console to register the command on
   * @param callable|null $execute Code for command to execute (omit for noop)
   */
  protected function _makeCommand(
    Console $application,
    callable $execute = null
  ) {
    // phpcs:disable
    $execute = $execute ?? function () { return 0; };
    $command = new class($execute) extends SymfonyCommand {
      protected $_execute;

      public function __construct($execute) {
        if (! $execute instanceof Closure) {
          $execute = Closure::fromCallable($execute);
        }
        $this->_execute = $execute->bindTo($this, $this);
        parent::__construct('test:command');
      }

      public function execute(Input $input, Output $output) {
        return ($this->_execute)($input, $output);
      }
    };
    // phpcs:enable

    $application->add($command);
  }

  /**
   * {@inheritDoc}
   */
  protected function _getSubject(...$constructor_args) {
    $options = array_shift($constructor_args) ?? [];
    $input = array_shift($constructor_args) ?? new ArrayInput([]);
    $output = array_shift($constructor_args) ?? new BufferedOutput();

    $console = parent::_getSubject($options, $input, $output);
    $console->setAutoExit(false);

    return $console;
  }
}
