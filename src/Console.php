<?php
/**
 * @package Nexcess-CLI
 * @license https://opensource.org/licenses/MIT
 * @copyright 2018 Nexcess.net, LLC
 */

declare(strict_types = 1);

namespace Nexcess\Sdk\Cli;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use Nexcess\Sdk\ {
  Util\Config,
  Util\Language
};

use Nexcess\Sdk\Cli\ {
  Command,
  Exception\ConsoleException
};

use Symfony\Component\Console\ {
  Application as SymfonyApplication,
  Helper\QuestionHelper,
  Input\ArgvInput,
  Input\InputInterface as Input,
  Output\OutputInterface as Output,
  Output\ConsoleOutput,
  Question\ConfirmationQuestion,
  Question\Question
};

/**
 * Cli API client for nexcess.net / thermo.io.
 */
class Console extends SymfonyApplication {

  /** @var string Name of application. */
  const NAME = 'Nexcess-CLI';

  /** @var string Version of application. */
  const VERSION = '0.1-alpha';

  /** @var string Application root directory. */
  const DIR = __DIR__;

  /** @var string Application root namespace. */
  const NAMESPACE = __NAMESPACE__;

  /** @var Guzzle The guzzle http client. */
  protected $_client;

  /** @var string[] FQCN's of available commands. */
  protected $_commands = [];

  /** @var Config The SDK configuration object. */
  protected $_config;

  /** @var Handler Application error/exception handler. */
  protected $_error_handler;

  /** @var Input Application input object. */
  protected $input;

  /** @var Language Language object. */
  protected $_language;

  /** @var Output Application output object. */
  protected $output;

  /**
   * @param Config $config The SDK configuration object
   */
  public function __construct(Config $config) {
    // hide args from top/ps (ignore if fails)
    @cli_set_process_title(static::NAME . ' v' . static::VERSION);

    $this->_config = $config;
    $this->_setErrorHandler();
    $this->setLanguageHandler();

    parent::__construct(static::NAME, static::VERSION);

    $this->_bootstrapCommands();
  }

  /**
   * Queries the user and gets response.
   *
   * @param string $message The question (key) to ask
   * @param array $context Map of question placeholder:replacement pairs
   * @param string $default A default answer
   * @return string The user's response
   * @throws ConsoleException If no QuestionHelper is available
   */
  public function ask(
    string $message,
    array $context,
    string $default
  ) : string {
    $helper = $this->getHelperSet()->get('question');
    if (! $helper instanceof QuestionHelper) {
      throw new ConsoleException(ConsoleException::QUESTIONHELPERLESS);
    }

    return $helper->ask(
      $this->_input,
      $this->_output,
      new Question($this->_translate($message, $context), $default)
    );
  }

  /**
   * Asks the user to choose an option.
   *
   * @param string $message The question (key) to ask
   * @param array $context Map of question placeholder:replacement pairs
   * @param array $choices Options for user to choose from
   * @param int|string|null $default A default response
   * @return string The user's response
   * @throws ConsoleException If no QuestionHelper is available
   */
  public function choice(
    string $message,
    array $context,
    array $choices,
    $default = null
  ) : string {
    $helper = $this->getHelperSet()->get('question');
    if (! $helper instanceof QuestionHelper) {
      throw new ConsoleException(ConsoleException::QUESTIONHELPERLESS);
    }

    return $helper->ask(
      $this->_input,
      $this->_output,
      new ChoiceQuestion(
        $this->_translate($message, $context),
        $choices,
        $default
      )
    );
  }

  /**
   * Asks the user for a confirmation.
   *
   * @param string $message The question (key) to ask
   * @param array $context Map of question placeholder:replacement pairs
   * @param bool $default A default response
   * @return bool The user's response
   * @throws ConsoleException If no QuestionHelper is available
   */
  public function confirm(
    string $message,
    array $context,
    bool $default = false
  ) {
    $helper = $this->getHelperSet()->get('question');
    if (! $helper instanceof QuestionHelper) {
      throw new ConsoleException(ConsoleException::QUESTIONHELPERLESS);
    }

    return $helper->ask(
      $this->_input,
      $this->_output,
      new ConfirmationQuestion(
        $this->_translate($message, $context),
        $default,
        '(^y)i'
      )
    );
  }

  /**
   * {@inheritDoc}
   * @see SymfonyApplication::run
   * Use our own i/o objects instead of letting run() create them.
   */
  public function run(Input $input = null, Output $output = null) {
    $this->_input = $input ?? new ArgvInput();
    $this->_output = $output ?? new ConsoleOutput();

    return parent::run($this->_input, $this->_output);
  }

  /**
   * Translates a message and writes out to the console.
   *
   * If symfony's Output object is not yet available,
   * this method will attempt to simply echo to stdout.
   *
   * @param string $message The message or message key to output
   * @param array $context Map of message placeholder:replacement pairs
   * @param array $opts {
   *    @var bool SAY_OPT_NEWLINE Add a newline at the end?
   *    @var int SAY_OPT_OPTIONS @see OutputInterface::write() $options
   *  }
   * @return Console $this
   */
  public function say(
    string $message,
    array $context = [],
    array $options = []
  ) : Console {
    $message = $this->_translate($message, $context);

    $newline = $options[self::SAY_OPT_NEWLINE] ?? true;
    $opts = $options[self::SAY_OPT_OPTIONS] ?? 0;

    if ($this->_output) {
      $this->_output->write($message, $newline, $opts);
    } else {
      echo $newline ? "{$message}\n" : $message;
    }

    return $this;
  }

  /**
   * Looks for Command classes belonging to this application,
   * and adds them to the $_commands array.
   */
  protected function _autodiscoverCommands() {
    // make sure all php files below us are loaded
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator(static::DIR)
    );
    foreach ($iterator as $file => $info) {
      if ($info->getExtension() === 'php') {
        include_once $file;
      }
    }

    // find commands and add them to the $_commands list
    array_push(
      $this->_commands,
      ...array_filter(
        array_map(
          function (string $fqcn) {
            $rc = new ReflectionClass($fqcn);
            return (
              $rc->isInstantiable() &&
              $rc->isSubclassOf(Command::class)
            ) ?
              $fqcn :
              null;
          },
          get_declared_classes()
        )
      )
    );
  }

  /**
   * Instantiates and adds Commands to this application.
   *
   * @throws Exception If unable to create/add a registered Command
   */
  protected function _bootstrapCommands() {
    $this->_autodiscoverCommands();
    $this->addCommands($this->_commands);
  }

  /**
   * Are we debugging?
   *
   * @return boolean True if debugging; false otherwise
   */
  protected function _isDebug() : bool {
    return in_array('-vvv', $GLOBALS['argv']);
  }

  /**
   * Symfony/Console hides a lot of errors for some reason.
   */
  protected function _setErrorHandler() {
    // @todo May need more/less depending on how symfony's handler works out.
    $this->_error_handler = new Handler();
    $this->_error_handler->throw(E_ALL)->register();
  }

  /**
   * Sets the Language object for the application.
   */
  protected function _setLanguageHandler() {
    $this->_language = Language::getInstance();

    $lang = $this->_config->get('language.language');
    if ($lang) {
      $this->_language->setLanguage($lang);
    }

    $paths = $this->_config->get('language.paths') ?? [];
    $paths[] = static::DIR . '/util/lang';
    $this->_language->addPaths(...$paths);
  }

  /**
   * Translates and makes placeholder → context replacements in given message.
   *
   * @param string $message The string to make replacements on
   * @param array $context Map of message placeholder:replacement pairs
   * @return string Replaced message on success; original message otherwise
   */
  protected function _translate(string $message, array $context) : string {
    $message = $this->_language->getTranslation($message) ?? $message;

    if (empty($context)) {
      return $message;
    }

    preg_match_all('(\{(\w+)\})', $message, $matches);
    $placeholders = $matches[1];
    $replacements = [];

    foreach ($placeholders as $placeholder) {
      if (! isset($context[$placeholder])) {
        return $message;
      }

      $replacements["{{$placeholder}}"] = is_scalar($context[$placeholder]) ?
        $context[$placeholder] :
        json_encode(
          $context[$placeholder],
          JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    return strtr($message, $replacements);
  }
}
